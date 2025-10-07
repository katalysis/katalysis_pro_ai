<?php

namespace KatalysisProAi;

use NeuronAI\RAG\VectorStore\VectorStoreInterface;
use NeuronAI\RAG\VectorStore\FileVectorStore;
use Concrete\Core\Support\Facade\Config;

/**
 * Factory for creating vector store instances
 * Supports both file-based and Typesense vector stores
 * Automatically selects based on configuration and availability
 */
class TypesenseVectorStoreFactory
{
    /**
     * Create a vector store instance
     * 
     * @param string $name Store name (e.g., 'pages', 'people', 'reviews', 'places')
     * @param int $topK Number of top results to return
     * @param string $type Type of store ('file' or 'typesense')
     * @return VectorStoreInterface
     */
    public static function create(string $name = 'pages', int $topK = 4, string $type = null): VectorStoreInterface
    {
        // Auto-detect type if not specified
        if ($type === null) {
            $type = self::detectVectorStoreType();
        }

        switch ($type) {
            case 'typesense':
                return self::createTypesenseStore($name, $topK);
            
            case 'file':
            default:
                return self::createFileStore($name, $topK);
        }
    }

    /**
     * Detect which vector store type to use based on configuration
     * 
     * @return string 'typesense' or 'file'
     */
    private static function detectVectorStoreType(): string
    {
        // Check if Typesense is configured and available
        $typesenseEnabled = Config::get('katalysis.ai.typesense.enabled', false);
        $typesenseHost = Config::get('katalysis.ai.typesense.host');
        
        if ($typesenseEnabled && !empty($typesenseHost) && class_exists('\\KatalysisProAi\\CustomTypesenseVectorStore')) {
            return 'typesense';
        }

        // Default to file-based storage
        return 'file';
    }

    /**
     * Create a file-based vector store
     * 
     * @param string $name
     * @param int $topK
     * @return FileVectorStore
     */
    private static function createFileStore(string $name, int $topK): FileVectorStore
    {
        $storageDir = DIR_APPLICATION . '/files/neuron';

        if (!is_dir($storageDir)) {
            if (!mkdir($storageDir, 0775, true) && !is_dir($storageDir)) {
                throw new \RuntimeException("Failed to create directory: $storageDir");
            }
        }

        return new FileVectorStore($storageDir, $topK, $name);
    }

    /**
     * Create a Typesense vector store
     * 
     * @param string $name
     * @param int $topK
     * @return VectorStoreInterface
     */
    private static function createTypesenseStore(string $name, int $topK): VectorStoreInterface
    {
        // Check if CustomTypesenseVectorStore class exists
        if (!class_exists('\\KatalysisProAi\\CustomTypesenseVectorStore')) {
            // Fall back to file store if Typesense not available
            return self::createFileStore($name, $topK);
        }

        $host = Config::get('katalysis.ai.typesense.host');
        $apiKey = Config::get('katalysis.ai.typesense.api_key');
        $port = Config::get('katalysis.ai.typesense.port', 443);
        $protocol = Config::get('katalysis.ai.typesense.protocol', 'https');

        return new CustomTypesenseVectorStore($host, $apiKey, $port, $protocol, $name, $topK);
    }
}
