<?php

namespace KatalysisProAi;


use KatalysisProAi\KatalysisProVectorStore;
use KatalysisProAi\TypesenseVectorStoreFactory;
use NeuronAI\RAG\Document;
use NeuronAI\RAG\VectorStore\VectorStoreInterface;
use Concrete\Core\Support\Facade\Database;
use NeuronAI\RAG\Embeddings\OpenAIEmbeddingsProvider;
use Concrete\Package\KatalysisPro\Src\KatalysisPro\People\Person;
use Concrete\Core\Support\Facade\Config;

class KatalysisProIndexService
{
    private VectorStoreInterface $peopleStore;
    private VectorStoreInterface $reviewsStore;
    private VectorStoreInterface $placesStore;
    private OpenAIEmbeddingsProvider $embeddingsProvider;

    public function __construct()
    {
        $apiKey = Config::get('app.api.openai.api_key');
        $this->embeddingsProvider = new OpenAIEmbeddingsProvider(
            $apiKey,
            'text-embedding-3-small'
        );
        
        // Detect actual embedding dimensions from OpenAI
        $vectorDimensions = $this->detectEmbeddingDimensions();
        
        // Create separate vector stores for each entity type using detected dimensions
        $this->peopleStore = TypesenseVectorStoreFactory::create('people', 5, $vectorDimensions);
        $this->reviewsStore = TypesenseVectorStoreFactory::create('reviews', 5, $vectorDimensions);
        $this->placesStore = TypesenseVectorStoreFactory::create('places', 3, $vectorDimensions);
    }
    
    /**
     * Detect the actual embedding dimensions from OpenAI
     */
    private function detectEmbeddingDimensions(): int
    {
        try {
            // Generate a test embedding to detect dimensions
            $testEmbedding = $this->embeddingsProvider->embedText("Test text for dimension detection");
            return count($testEmbedding);
        } catch (\Exception $e) {
            error_log("Warning: Could not detect embedding dimensions, using default 1536: " . $e->getMessage());
            return 1536; // Fallback to default
        }
    }
    
    /**
     * Clear vector store by deleting all documents
     * Helper method to work with VectorStoreInterface
     */
    private function clearVectorStore(VectorStoreInterface $store): void
    {
        // For compatibility with both FileVectorStore and TypesenseVectorStore
        if ($store instanceof KatalysisProVectorStore) {
            $store->clearStore();
        } else {
            // For TypesenseVectorStore, we need to handle clearing differently
            // Since deleteBySource requires specific source types, we'll skip clearing
            // and rely on document IDs to prevent duplicates
            // Vector store clearing skipped for Typesense - using document ID deduplication
        }
    }
    
    /**
     * Build vector database for all people/specialists
     */
    public function buildPeopleIndex(): void
    {
        echo "Building People Vector Index...\n";
        
        // Clear existing index to prevent duplicates
        // Note: VectorStoreInterface doesn't have clearStore(), but we can recreate it
        $this->clearVectorStore($this->peopleStore);
        
        $db = Database::get();
        $people = $db->GetAll("SELECT sID, name, jobTitle, email, phone, page, featured, active, biography, shortBiography FROM KatalysisPeople WHERE active = 1");
        
        $documents = [];
        foreach ($people as $person) {
            // Create rich text content for embedding
            $content = $this->buildPersonContent($person);
            
            if (!empty($content)) {
                $document = new Document($content);
                $document->sourceType = 'person';
                $document->sourceName = $person['name'];
                $document->id = 'person_' . $person['sID'];
                
                // Add metadata for retrieval (convert all to strings for Typesense compatibility)
                $document->addMetadata('person_id', (string)$person['sID']);
                $document->addMetadata('name', (string)($person['name'] ?? ''));
                $document->addMetadata('job_title', (string)($person['jobTitle'] ?? ''));
                $document->addMetadata('email', (string)($person['email'] ?? ''));
                $document->addMetadata('phone', (string)($person['phone'] ?? ''));
                $document->addMetadata('page', (string)($person['page'] ?? ''));
                $document->addMetadata('featured', $person['featured'] ? 'true' : 'false');
                $document->addMetadata('biography', (string)($person['biography'] ?? ''));
                $document->addMetadata('short_biography', (string)($person['shortBiography'] ?? ''));
                
                // Try to add additional metadata from Person object
                try {
                    $personObj = Person::getByID($person['sID']);
                    if ($personObj) {
                        try {
                            $specialisms = $personObj->getSpecialisms($person['sID']);
                            $document->addMetadata('specialisms', json_encode($specialisms));
                        } catch (\Exception $e) {
                            // Skip specialisms metadata if error
                            $document->addMetadata('specialisms', json_encode([]));
                        }
                        
                        try {
                            $topics = $personObj->getTopics($person['sID']);
                            $document->addMetadata('topics', json_encode($topics));
                        } catch (\Exception $e) {
                            // Skip topics metadata if error
                            $document->addMetadata('topics', json_encode([]));
                        }
                        
                        try {
                            $places = $personObj->getPlaces($person['sID']);
                            $document->addMetadata('places', json_encode($places));
                        } catch (\Exception $e) {
                            // Skip places metadata if error
                            $document->addMetadata('places', json_encode([]));
                        }
                    }
                } catch (\Exception $e) {
                    // Skip person metadata if error creating object
                }
                
                $documents[] = $document;
            }
        }
        
        if (!empty($documents)) {
            try {
                // Generate embeddings for documents before adding
                foreach ($documents as $document) {
                    if (empty($document->getEmbedding())) {
                        $embedding = $this->embeddingsProvider->embedText($document->getContent());
                        $document->embedding = $embedding; // Set directly as it's a public property
                    }
                }
                
                echo "Adding " . count($documents) . " people to Typesense...\n";
                $this->peopleStore->addDocuments($documents);
                echo "Successfully indexed " . count($documents) . " people.\n";
            } catch (\Exception $e) {
                echo "ERROR indexing people: " . $e->getMessage() . "\n";
                error_log("TYPESENSE PEOPLE INDEX ERROR: " . $e->getMessage());
                error_log("TYPESENSE PEOPLE INDEX TRACE: " . $e->getTraceAsString());
                throw $e; // Re-throw to stop the task
            }
        } else {
            echo "No people found to index.\n";
        }
    }
    
    /**
     * Build vector database for all reviews
     */
    public function buildReviewsIndex(): void
    {
        echo "Building Reviews Vector Index...\n";
        
        // Clear existing index to prevent duplicates
        $this->clearVectorStore($this->reviewsStore);
        
        $db = Database::get();
        $reviews = $db->GetAll("SELECT sID, author, organization, rating, extract, review, source, featured, active FROM KatalysisReviews WHERE active = 1");
        
        $documents = [];
        foreach ($reviews as $review) {
            // Use extract if available, otherwise full review
            $content = $review['extract'] ?: $review['review'];
            
            if (!empty($content)) {
                $document = new Document($content);
                $document->sourceType = 'review';
                $document->sourceName = $review['author'] ?: 'Client Review';
                $document->id = 'review_' . $review['sID'];
                
                // Add metadata (convert all to strings for Typesense compatibility)
                $document->addMetadata('review_id', (string)$review['sID']);
                $document->addMetadata('author', (string)($review['author'] ?? ''));
                $document->addMetadata('organization', (string)($review['organization'] ?? ''));
                $document->addMetadata('rating', (string)$review['rating']);
                $document->addMetadata('source', (string)($review['source'] ?? ''));
                $document->addMetadata('featured', $review['featured'] ? 'true' : 'false');
                $document->addMetadata('full_review', (string)($review['review'] ?? ''));
                
                $documents[] = $document;
            }
        }
        
        if (!empty($documents)) {
            try {
                // Generate embeddings for documents before adding
                foreach ($documents as $document) {
                    if (empty($document->getEmbedding())) {
                        $embedding = $this->embeddingsProvider->embedText($document->getContent());
                        $document->embedding = $embedding;
                    }
                }
                
                echo "Adding " . count($documents) . " reviews to Typesense...\n";
                $this->reviewsStore->addDocuments($documents);
                echo "Successfully indexed " . count($documents) . " reviews.\n";
            } catch (\Exception $e) {
                echo "ERROR indexing reviews: " . $e->getMessage() . "\n";
                error_log("TYPESENSE REVIEWS INDEX ERROR: " . $e->getMessage());
                error_log("TYPESENSE REVIEWS INDEX TRACE: " . $e->getTraceAsString());
                throw $e;
            }
        } else {
            echo "No reviews found to index.\n";
        }
    }
    
    /**
     * Build vector database for places/locations
     */
    public function buildPlacesIndex(): void
    {
        echo "Building Places Vector Index...\n";
        
        // Clear existing index to prevent duplicates
        $this->clearVectorStore($this->placesStore);
        
        $db = Database::get();
        $places = $db->GetAll("SELECT sID, name, address1, address2, town, county, postcode, description, phone, email FROM KatalysisPlaces WHERE active = 1");
        
        $documents = [];
        foreach ($places as $place) {
            // Build rich content for place embedding
            $content = $place['name'];
            if (!empty($place['description'])) {
                $content .= ". " . $place['description'];
            }
            
            // Add location information for better matching
            $locationParts = array_filter([
                $place['town'],
                $place['county'],
                $place['address1'],
                $place['address2']
            ]);
            if (!empty($locationParts)) {
                $content .= ". Located in " . implode(', ', $locationParts);
            }
            
            if (!empty($content)) {
                $document = new Document($content);
                $document->sourceType = 'place';
                $document->sourceName = $place['name'];
                $document->id = 'place_' . $place['sID'];
                
                $document->addMetadata('place_id', (string)$place['sID']);
                $document->addMetadata('name', (string)($place['name'] ?? ''));
                $document->addMetadata('description', (string)($place['description'] ?? ''));
                $document->addMetadata('address', (string)trim(($place['address1'] ?? '') . ' ' . ($place['address2'] ?? '')));
                $document->addMetadata('town', (string)($place['town'] ?? ''));
                $document->addMetadata('county', (string)($place['county'] ?? ''));
                $document->addMetadata('postcode', (string)($place['postcode'] ?? ''));
                $document->addMetadata('phone', (string)($place['phone'] ?? ''));
                $document->addMetadata('email', (string)($place['email'] ?? ''));
                
                $documents[] = $document;
            }
        }
        
        if (!empty($documents)) {
            try {
                // Generate embeddings for documents before adding
                echo "Generating embeddings for " . count($documents) . " places...\n";
                foreach ($documents as $document) {
                    if (empty($document->getEmbedding())) {
                        $embedding = $this->embeddingsProvider->embedText($document->getContent());
                        $document->embedding = $embedding;
                    }
                }
                
                echo "Adding " . count($documents) . " places to Typesense...\n";
                $this->placesStore->addDocuments($documents);
                echo "Successfully indexed " . count($documents) . " places.\n";
                // Successfully added place documents to vector store
            } catch (\Exception $e) {
                echo "ERROR indexing places: " . $e->getMessage() . "\n";
                error_log("TYPESENSE PLACES INDEX ERROR: " . $e->getMessage());
                error_log("TYPESENSE PLACES INDEX TRACE: " . $e->getTraceAsString());
                throw $e;
            }
        } else {
            echo "No places found to index.\n";
        }
    }
    
    /**
     * Search for relevant people using AI similarity
     */
    public function searchPeople(string $query, int $limit = 3): array
    {
        try {
            $embedding = $this->embeddingsProvider->embedText($query);
            $results = $this->peopleStore->similaritySearch($embedding);
            
            $specialists = [];
            foreach (array_slice($results, 0, $limit) as $result) {
                // Skip documents without embeddings
                if (!$result || !isset($result->metadata)) {
                    continue;
                }
                
                $metadata = $result->metadata;
                $specialists[] = [
                    'id' => $metadata['person_id'] ?? 0,
                    'name' => $metadata['name'] ?? 'Unknown',
                    'title' => $metadata['job_title'] ?? 'Specialist',
                    'expertise' => $this->deriveExpertiseFromContent($result->getContent()),
                    'contact' => $metadata['email'] ?: $metadata['phone'] ?: 'Contact Available',
                    'email' => $metadata['email'] ?? '',
                    'phone' => $metadata['phone'] ?? '',
                    'featured' => false, // Remove featured key access since it doesn't exist in vector metadata
                    'relevance_score' => round($result->score * 10, 2), // Scale to 0-10
                    'ai_match' => true
                ];
            }
            
            return $specialists;
        } catch (\Exception $e) {
            error_log("Error in searchPeople: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Search for relevant reviews using AI similarity
     */
    public function searchReviews(string $query, int $limit = 3): array
    {
        try {
            $embedding = $this->embeddingsProvider->embedText($query);
            $results = $this->reviewsStore->similaritySearch($embedding);
            
            $reviews = [];
            foreach (array_slice($results, 0, $limit) as $result) {
                // Skip documents without embeddings
                if (!$result || !isset($result->metadata)) {
                    continue;
                }
                
                $metadata = $result->metadata;
                $reviews[] = [
                    'id' => $metadata['review_id'] ?? 0,
                    'client_name' => $metadata['author'] ?? 'Client',
                    'organization' => $metadata['organization'] ?? '',
                    'rating' => $metadata['rating'] ?? 5,
                    'review' => $result->getContent(),
                    'source' => $metadata['source'] ?? 'Client Review',
                    'featured' => false, // Remove featured key access since it doesn't exist in vector metadata
                    'relevance_score' => round($result->score * 10, 2),
                    'ai_match' => true
                ];
            }
            
            return $reviews;
        } catch (\Exception $e) {
            error_log("Error in searchReviews: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Build rich content for person embedding
     */
    private function buildPersonContent(array $person): string
    {
        $content = [];
        
        // Basic info
        $content[] = $person['name'] . " is a " . ($person['jobTitle'] ?: 'legal specialist');
        
        // Add biography information
        if (!empty($person['biography'])) {
            $content[] = $person['biography'];
        } elseif (!empty($person['shortBiography'])) {
            $content[] = $person['shortBiography'];
        }
        
        // Try to get specialisms and add them to content
        try {
            $personObj = Person::getByID($person['sID']);
            if ($personObj) {
                try {
                    $specialisms = $personObj->getSpecialisms($person['sID']);
                    if (!empty($specialisms)) {
                        $specialismNames = array_column($specialisms, 'treeNodeName');
                        $content[] = "Specializes in: " . implode(', ', $specialismNames);
                    }
                } catch (\Exception $e) {
                    // Skip specialisms if error
                }
                
                try {
                    $places = $personObj->getPlaces($person['sID']);
                    if (!empty($places)) {
                        $placeNames = array_column($places, 'name');
                        $content[] = "Serves locations: " . implode(', ', $placeNames);
                    }
                } catch (\Exception $e) {
                    // Skip places if error
                }
            } else {
                // Person object is null
            }
        } catch (\Exception $e) {
            // Error creating Person object
        }
        
        return implode('. ', array_filter($content));
    }
    
    /**
     * Derive expertise area from content for display
     */
    private function deriveExpertiseFromContent(string $content): string
    {
        $content = strtolower($content);
        
        if (strpos($content, 'injury') !== false || strpos($content, 'accident') !== false || strpos($content, 'compensation') !== false) {
            return 'Personal Injury & Medical Negligence';
        } elseif (strpos($content, 'conveyancing') !== false || strpos($content, 'property') !== false) {
            return 'Conveyancing & Property Law';
        } elseif (strpos($content, 'family') !== false || strpos($content, 'divorce') !== false) {
            return 'Family Law';
        } elseif (strpos($content, 'employment') !== false || strpos($content, 'workplace') !== false) {
            return 'Employment Law';
        } elseif (strpos($content, 'will') !== false || strpos($content, 'probate') !== false || strpos($content, 'estate') !== false) {
            return 'Wills & Probate';
        } elseif (strpos($content, 'commercial') !== false || strpos($content, 'business') !== false) {
            return 'Commercial Law';
        } else {
            return 'Legal Services';
        }
    }
    
    /**
     * Search for relevant places using AI similarity
     */
    public function searchPlaces(string $query, int $limit = 3): array
    {
        try {
            $embedding = $this->embeddingsProvider->embedText($query);
            $results = $this->placesStore->similaritySearch($embedding);
            
            error_log("Places vector search - Query: $query, Results count: " . count($results));
            
            $places = [];
            foreach (array_slice($results, 0, $limit) as $result) {
                // Skip documents without embeddings or metadata
                if (!$result || !isset($result->metadata)) {
                    error_log("Skipping place document without metadata");
                    continue;
                }
                
                // Skip documents without embeddings
                if (!$result->getContent()) {
                    error_log("Skipping place document without content: " . json_encode($result->metadata));
                    continue;
                }
                
                $metadata = $result->metadata;
                $places[] = [
                    'id' => $metadata['place_id'] ?? 0,
                    'name' => $metadata['name'] ?? 'Location',
                    'address' => $metadata['address'] ?? '',
                    'town' => $metadata['town'] ?? '',
                    'county' => $metadata['county'] ?? '',
                    'postcode' => $metadata['postcode'] ?? '',
                    'phone' => $metadata['phone'] ?? '',
                    'email' => $metadata['email'] ?? '',
                    'relevance_score' => round($result->score * 10, 2),
                    'ai_match' => true
                ];
            }
            
            return $places;
        } catch (\Exception $e) {
            error_log("Error in searchPlaces: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Build all vector indexes
     */
    public function buildAllIndexes(): void
    {
        echo "Building all Katalysis vector indexes...\n";
        $this->buildPeopleIndex();
        $this->buildReviewsIndex();
        $this->buildPlacesIndex();
        echo "All indexes built successfully!\n";
    }
}
