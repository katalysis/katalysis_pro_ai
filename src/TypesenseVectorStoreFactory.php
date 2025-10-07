<?php

namespace KatalysisProAi;

use Concrete\Core\Support\Facade\Config;
use NeuronAI\RAG\VectorStore\TypesenseVectorStore;
use NeuronAI\RAG\VectorStore\FileVectorStore;
use NeuronAI\RAG\VectorStore\VectorStoreInterface;
use Typesense\Client;

/**
 * Factory for creating vector stores (Typesense or File-based)
 * Uses configuration to determine which type to create
 */
class TypesenseVectorStoreFactory
{
    /**
     * Create a vector store instance based on configuration
     * 
     * @param string $storeType The type of store (pages, people, reviews, places)
     * @param int $topK Maximum number of results to return
     * @param int $vectorDimension Dimension of vectors (1536 for OpenAI text-embedding-3-small)
     * @return VectorStoreInterface
     */
    public static function create(string $storeType, int $topK = 4, int $vectorDimension = 1536): VectorStoreInterface
    {
        $useTypesense = Config::get('katalysis.search.use_typesense', false);
        
        if ($useTypesense) {
            return self::createTypesenseStore($storeType, $topK, $vectorDimension);
        } else {
            return self::createFileStore($storeType, $topK);
        }
    }
    
    /**
     * Create a Typesense vector store using our custom implementation
     * 
     * @param string $storeType
     * @param int $topK
     * @param int $vectorDimension
     * @return CustomTypesenseVectorStore
     * @throws \Exception
     */
    private static function createTypesenseStore(string $storeType, int $topK, int $vectorDimension): CustomTypesenseVectorStore
    {
        $apiKey = Config::get('katalysis.search.typesense_api_key', '');
        $host = Config::get('katalysis.search.typesense_host', '');
        $port = Config::get('katalysis.search.typesense_port', '443');
        $protocol = Config::get('katalysis.search.typesense_protocol', 'https');
        $collectionPrefix = Config::get('katalysis.search.typesense_collection_prefix', 'katalysis_');
        
        if (empty($apiKey) || empty($host)) {
            throw new \Exception('Typesense configuration is incomplete. Please configure API key and host.');
        }
        
        // Create Typesense client using neuron-ai compatible configuration
        $client = new Client([
            'api_key' => $apiKey,
            'nodes' => [
                [
                    'host' => $host,
                    'port' => $port,
                    'protocol' => $protocol,
                ],
            ],
            'connection_timeout_seconds' => 10,
            'healthcheck_interval_seconds' => 30,
            'num_retries' => 3,
            'retry_interval_seconds' => 0.1,
        ]);
        
        // Create collection name with appropriate prefix
        $collection = self::generateCollectionName($collectionPrefix, $storeType);
        
        return new CustomTypesenseVectorStore(
            $client,
            $collection,
            $vectorDimension,
            (string)$topK
        );
    }
    
    /**
     * Create a file-based vector store (fallback/default)
     * 
     * @param string $storeType
     * @param int $topK
     * @return FileVectorStore
     */
    private static function createFileStore(string $storeType, int $topK): FileVectorStore
    {
        $storageDir = DIR_APPLICATION . '/files/neuron';
        
        if (!is_dir($storageDir)) {
            if (!mkdir($storageDir, 0775, true) && !is_dir($storageDir)) {
                throw new \RuntimeException("Failed to create directory: $storageDir");
            }
        }
        
        return new FileVectorStore(
            $storageDir,
            $topK,
            $storeType
        );
    }
    
    /**
     * Generate appropriate collection name based on store type
     * 
     * @param string $prefix Base prefix (e.g., "katalysis_")
     * @param string $storeType The store type identifier
     * @return string Formatted collection name
     */
    private static function generateCollectionName(string $prefix, string $storeType): string
    {
        // Only handle collections that actually exist in Typesense:
        // - katalysis_all_pages (comprehensive page collection)
        // - katalysis_people (specialist profiles)
        // - katalysis_places (office locations)  
        // - katalysis_reviews (client reviews)
        
        $validCollections = ['all_pages', 'people', 'places', 'reviews'];
        
        if (in_array($storeType, $validCollections)) {
            return $prefix . $storeType;
        }
        
        // If someone tries to use a non-existent collection, throw an error
        throw new \Exception("Unknown store type '{$storeType}'. Valid collections: " . implode(', ', $validCollections));
    }
    
    /**
     * Test Typesense connection
     * 
     * @return array Status information
     */
    public static function testTypesenseConnection(): array
    {
        try {
            $apiKey = Config::get('katalysis.search.typesense_api_key', '');
            $host = Config::get('katalysis.search.typesense_host', '');
            $port = Config::get('katalysis.search.typesense_port', '443');
            $protocol = Config::get('katalysis.search.typesense_protocol', 'https');
            
            if (empty($apiKey) || empty($host)) {
                return [
                    'success' => false,
                    'message' => 'Typesense configuration is incomplete'
                ];
            }
            
            $client = new Client([
                'api_key' => $apiKey,
                'nodes' => [
                    [
                        'host' => $host,
                        'port' => $port,
                        'protocol' => $protocol,
                    ],
                ],
                'connection_timeout_seconds' => 5,
            ]);
            
            // Test connection by getting health status
            $health = $client->health->retrieve();
            
            return [
                'success' => true,
                'message' => 'Connection successful',
                'health' => $health
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage()
            ];
        }
    }
}
