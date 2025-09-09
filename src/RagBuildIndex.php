<?php
namespace KatalysisProAi;

use CollectionAttributeKey;
use Concrete\Core\Attribute\Key\CollectionKey;
use Concrete\Core\Block\BlockController;
use Concrete\Core\Feature\Features;
use Concrete\Core\Feature\UsesFeatureInterface;
use Concrete\Core\Page\PageList;
use Concrete\Core\Support\Facade\Core;
use Page;

use NeuronAI\RAG\DataLoader\StringDataLoader;
use NeuronAI\RAG\Embeddings\OpenAIEmbeddingsProvider;
use NeuronAI\RAG\VectorStore\FileVectorStore;
use Concrete\Core\Support\Facade\Config;

class RagBuildIndex {

    public function clearIndex(): void
    {
        // Use the correct path for your environment
        $storeFile = DIR_APPLICATION . '/files/neuron/neuron.store';
        
        if (file_exists($storeFile)) {
            unlink($storeFile);
        }
    }

    public function buildIndex()
    {
        $ipl = new PageList();
        $ipl->setSiteTreeToAll();
        
        $pages = [];
        $results = $ipl->getResults();

        foreach ($results as $r) {
            $content = $r->getPageIndexContent();
            
            // Skip pages with empty content
            if (!$this->isValidContent($content)) {
                continue;
            }

            $pages[] = [
                'title' => $r->getCollectionName(),
                'description' => $r->getCollectionDescription(),
                'content' => $content,
                'link' => $r->getCollectionLink(),
                'pagetype' => $r->getPageTypeHandle()
            ];
        }
        return $pages;
    }

    public function addDocuments(array $pages): void
    {
        $embeddingProvider = $this->getEmbeddingProvider();
        $vectorStore = $this->getVectorStore();
        
        $processedCount = 0;
        $skippedCount = 0;
        
        foreach ($pages as $page) {
            // Validate content before processing
            if (!$this->isValidContent($page['content'])) {
                $skippedCount++;
                continue;
            }
            
            try {
                $documents = StringDataLoader::for($page['content'])->getDocuments();
                
                foreach ($documents as $document) {
                    if (!($document instanceof \NeuronAI\RAG\Document)) {
                        throw new \RuntimeException('Expected Document, got: ' . (is_object($document) ? get_class($document) : gettype($document)));
                    }
                    
                    // Add page metadata to document
                    $document->sourceName = $page['title'];
                    $document->sourceType = 'page';
                    $document->addMetadata('url', $page['link']); // Store the page URL in metadata
                    $document->addMetadata('pagetype', $page['pagetype']); // Store the page type in metadata
                    
                    $document->embedding = $embeddingProvider->embedText($document->content);
                    $vectorStore->addDocument($document);
                    $processedCount++;
                }
            } catch (\Exception $e) {
                $skippedCount++;
            }
        }
    }

    public function getRelevantDocuments(string $query, int $topK = 12): array
    {
        $embeddingProvider = $this->getEmbeddingProvider();
        $vectorStore = $this->getVectorStore($topK);
        $queryEmbedding = $embeddingProvider->embedText($query);
        
        return $vectorStore->similaritySearch($queryEmbedding);
    }

    /**
     * Get the OpenAI embeddings provider instance
     */
    public function getEmbeddingProvider(): OpenAIEmbeddingsProvider
    {
        return new OpenAIEmbeddingsProvider(
            key: Config::get('katalysis.ai.open_ai_key'),
            model: 'text-embedding-3-small'
        );
    }

    /**
     * Get the file vector store instance
     */
    public function getVectorStore(int $topK = 4): FileVectorStore
    {

        $storageDir = DIR_APPLICATION . '/files/neuron';

        if (!is_dir($storageDir)) {
            if (!mkdir($storageDir, 0775, true) && !is_dir($storageDir)) {
                throw new \RuntimeException("Failed to create directory: $storageDir");
            }
        }
        return new FileVectorStore(
            directory: $storageDir,
            topK: $topK
        );
    }

    /**
     * Validate if content is suitable for processing
     */
    private function isValidContent($content): bool
    {
        return is_string($content) && !empty($content) && strlen($content) >= 50;
    }
}
