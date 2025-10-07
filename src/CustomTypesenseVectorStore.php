<?php

namespace KatalysisProAi;

use NeuronAI\RAG\VectorStore\TypesenseVectorStore;
use NeuronAI\RAG\Document;
use NeuronAI\RAG\VectorStore\VectorSimilarity;
use Typesense\Client;
use Typesense\Exceptions\ObjectNotFound;

/**
 * Custom TypesenseVectorStore that fixes field type mapping and response parsing
 * Extends the neuron-ai TypesenseVectorStore to fix Typesense compatibility issues
 */
class CustomTypesenseVectorStore extends TypesenseVectorStore
{
    private ?string $currentQuery = null;
    /**
     * Override addDocument to implement proper upsert functionality
     */
    public function addDocument(Document $document): void
    {
        if ($document->getEmbedding() === []) {
            throw new \Exception('document embedding must be set before adding a document');
        }

        // Fix metadata types before processing
        $originalMetadata = $document->metadata;
        $document->metadata = $this->fixMetadataTypes($document->metadata);
        
        try {
            // Ensure collection exists first
            $this->checkIndexStatus($document);
            
            // Use reflection to access protected properties
            $reflection = new \ReflectionClass(get_parent_class($this));
            $clientProperty = $reflection->getProperty('client');
            $clientProperty->setAccessible(true);
            $client = $clientProperty->getValue($this);
            
            $collectionProperty = $reflection->getProperty('collection');
            $collectionProperty->setAccessible(true);
            $collectionName = $collectionProperty->getValue($this);
            
            // Access the document ID using reflection (since it was set via reflection)
            $docReflection = new \ReflectionClass($document);
            $idProperty = $docReflection->getProperty('id');
            $idProperty->setAccessible(true);
            $documentId = $idProperty->getValue($document);
            
            // Prepare document data for Typesense upsert
            $documentData = [
                'id' => $documentId,  // Use the deterministic ID
                'content' => $document->content,
                'sourceType' => $document->getSourceType(),
                'sourceName' => $document->getSourceName(),
                'embedding' => $document->getEmbedding()
            ];
            
            // Add all metadata fields
            foreach ($document->metadata as $key => $value) {
                $documentData[$key] = $value;
            }
            
            // Use upsert to replace existing document or create new one
            error_log("Upserting document with ID: {$documentId} in collection: {$collectionName}");
            $response = $client->collections[$collectionName]->documents->upsert($documentData);
            error_log("Upsert response: " . json_encode($response));
            
        } finally {
            // Restore original metadata
            $document->metadata = $originalMetadata;
        }
    }
    
    /**
     * Fix metadata types to be compatible with Typesense
     */
    private function fixMetadataTypes(array $metadata): array
    {
        $fixed = [];
        foreach ($metadata as $name => $value) {
            // Convert types that Typesense doesn't understand
            switch (gettype($value)) {
                case 'integer':
                    $fixed[$name] = (string)$value; // Convert to string to avoid int type issues
                    break;
                case 'boolean':
                    $fixed[$name] = $value ? 'true' : 'false'; // Convert to string
                    break;
                case 'double':
                case 'float':
                    $fixed[$name] = (string)$value; // Convert to string
                    break;
                case 'array':
                    $fixed[$name] = is_string($value) ? $value : json_encode($value); // Ensure it's a string
                    break;
                default:
                    $fixed[$name] = (string)$value; // Convert everything else to string
            }
        }
        return $fixed;
    }

    public function similaritySearch(array $embedding, ?string $query = null): array
    {
        // Store query for title matching calculations
        $this->currentQuery = $query;
        
        // Enhanced Typesense search with hybrid approach
        $params = [
            'collection' => $this->collection,
            'vector_query' => 'embedding:(' . \json_encode($embedding) . ')',
            'exclude_fields' => 'embedding',
            'per_page' => $this->topK,
            'num_candidates' => \max(100, \intval($this->topK) * 8), // More candidates for better quality
        ];

        // FOCUSED HYBRID SEARCH: Prioritize vector similarity with targeted text boosting
        if ($query && is_string($query) && trim($query) !== '') {
            // Use light text search to boost relevant matches without overwhelming vector results
            $params['q'] = $query;
            $params['query_by'] = 'sourceName,content';
            
            // CONSERVATIVE SORTING: Primarily vector-based with light text boost
            $params['sort_by'] = '_vector_distance:asc';
            
            // MODERATE FIELD WEIGHTING: Light preference for title matches
            $params['query_by_weights'] = '3,1'; // Moderate title preference vs content
            
            // Basic matching settings
            $params['prefix'] = 'true,false'; // Prefix only on titles
            $params['typo_tokens_threshold'] = 2; // Be more conservative with typos
            $params['num_typos'] = 1; // Limit typos to prevent false matches
            
        } else {
            // Pure vector search when no text query available
            $params['q'] = '*';
            $params['sort_by'] = '_vector_distance:asc';
        }

        // MINIMAL CONTENT BOOSTING: Only boost when query terms actually match
        if ($query && is_string($query) && strpos($this->collection, 'legal_service') !== false) {
            // Only boost if query contains terms that would logically match this collection
            $queryLower = strtolower($query);
            $collectionTerms = [];
            
            // Extract relevant terms based on collection name
            if (strpos($this->collection, 'legal_service') !== false) {
                $collectionTerms = ['legal', 'solicitor', 'lawyer', 'claim', 'injury', 'accident', 'compensation'];
            }
            
            // Only apply boosting if query actually contains relevant terms
            $hasRelevantTerms = false;
            foreach ($collectionTerms as $term) {
                if (strpos($queryLower, $term) !== false) {
                    $hasRelevantTerms = true;
                    break;
                }
            }
            
            // Conservative boosting only for genuinely relevant queries
            if ($hasRelevantTerms) {
                $params['boost_fields'] = 'sourceName:2'; // Light boost for title matches only
            }
        }

        $searchRequests = ['searches' => [$params]];

        try {
            $response = $this->client->multiSearch->perform($searchRequests);
            
            // Debug: Log the response structure
            // Raw response received from Typesense
            
            // Check if response has the expected structure
            if (!isset($response['results']) || !is_array($response['results']) || empty($response['results'])) {
                error_log("TYPESENSE ERROR - No 'results' array in response");
                return [];
            }
            
            $firstResult = $response['results'][0] ?? null;
            if (!$firstResult || !is_array($firstResult)) {
                error_log("TYPESENSE ERROR - First result is null or not array");
                return [];
            }
            
            // Handle different possible response formats
            $hits = null;
            
            // Try standard format first
            if (isset($firstResult['hits'])) {
                $hits = $firstResult['hits'];
            }
            // Try alternative format - sometimes the hits are directly in results
            elseif (isset($firstResult['documents'])) {
                // Map documents to hits format
                $hits = array_map(function($doc) {
                    return ['document' => $doc, 'vector_distance' => 0.5]; // Default distance
                }, $firstResult['documents']);
            }
            // Try if the results are directly the documents
            elseif (is_array($firstResult) && isset($firstResult[0]) && is_array($firstResult[0])) {
                $hits = array_map(function($doc) {
                    return ['document' => $doc, 'vector_distance' => 0.5];
                }, $firstResult);
            }
            
            if (!$hits || !is_array($hits)) {
                error_log("TYPESENSE ERROR - No hits found in response. First result keys: " . implode(', ', array_keys($firstResult)));
                return [];
            }
            
            // Processing hits from Typesense search
            
            return \array_map(function (array $hit): Document {
                $item = $hit['document'] ?? $hit; // Handle both formats
                
                if (!is_array($item) || !isset($item['content'])) {
                    error_log("TYPESENSE WARNING - Invalid document format: " . json_encode($item));
                    return new Document(''); // Return empty document as fallback
                }
                
                $document = new Document($item['content']);
                $document->sourceType = $item['sourceType'] ?? 'unknown';
                $document->sourceName = $item['sourceName'] ?? 'Unknown';
                
                // CONSERVATIVE SCORING: Primarily use vector similarity with light text boost
                $vectorScore = 0.5;
                
                // Get vector similarity score (primary ranking factor)
                if (isset($hit['vector_distance'])) {
                    $vectorScore = VectorSimilarity::similarityFromDistance($hit['vector_distance']);
                }
                
                // Start with vector score as base
                $document->score = $vectorScore;
                
                // LIGHT TEXT BOOST: Only apply small boost for exact query term matches in title
                if (isset($this->currentQuery) && $this->currentQuery && is_string($this->currentQuery) && $document->sourceName) {
                    $queryLower = strtolower($this->currentQuery);
                    $titleLower = strtolower($document->sourceName);
                    
                    // Only boost if the full query phrase appears in title (exact relevance)
                    if (strpos($titleLower, $queryLower) !== false) {
                        $document->score = min($document->score + 0.1, 1.0); // Small 10% boost for exact phrase
                    } else {
                        // Check for individual query terms but smaller boost
                        $queryTerms = explode(' ', $queryLower);
                        $titleMatches = 0;
                        foreach ($queryTerms as $term) {
                            if (strlen($term) > 3 && strpos($titleLower, $term) !== false) {
                                $titleMatches++;
                            }
                        }
                        if ($titleMatches >= 2) { // Need at least 2 terms to boost
                            $titleBonus = min($titleMatches / count($queryTerms) * 0.05, 0.05); // Max 5% bonus
                            $document->score = min($document->score + $titleBonus, 1.0);
                        }
                    }
                }

                // Add metadata, excluding system fields
                foreach ($item as $name => $value) {
                    if (!\in_array($name, ['content', 'sourceType', 'sourceName', 'score', 'embedding', 'id', 'vector_distance', 'text_match', 'text_match_info'])) {
                        $document->addMetadata($name, $value);
                    }
                }

                return $document;
            }, $hits);
            
        } catch (\Exception $e) {
            error_log("TYPESENSE SEARCH ERROR: " . $e->getMessage());
            error_log("TYPESENSE SEARCH TRACE: " . $e->getTraceAsString());
            return [];
        }
    }
}
