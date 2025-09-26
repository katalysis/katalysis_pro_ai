<?php

namespace KatalysisProAi\Command;

defined('C5_EXECUTE') or die("Access Denied.");

use KatalysisProAi\PageIndexService;
use Concrete\Core\Support\Facade\Log;
use Concrete\Core\Page\Page;
use NeuronAI\RAG\DataLoader\StringDataLoader;

class IndexPageCommandHandler
{
    private PageIndexService $pageIndexService;

    public function __construct()
    {
        $this->pageIndexService = new PageIndexService();
    }

    public function __invoke(IndexPageCommand $command)
    {
        // Start output buffering to capture any echo statements
        ob_start();
        
        try {
            $pageId = $command->getPageId();
            $targetStoreType = $command->getStoreType();
            $page = Page::getByID($pageId);
            
            if (!$page || $page->isError()) {
                Log::addWarning("Skipping invalid page ID: {$pageId}");
                // Clean output buffer
                ob_get_clean();
                return false;
            }

            $content = $page->getPageIndexContent();
            
            // Skip pages with empty or invalid content
            if (!$this->isValidContent($content)) {
                Log::addInfo("Skipping page '{$page->getCollectionName()}' - insufficient content");
                // Clean output buffer
                ob_get_clean();
                return false;
            }

            // Get meta title if available
            $metaTitle = '';
            try {
                $metaTitleAttr = $page->getAttribute('meta_title');
                $metaTitle = $metaTitleAttr ?: $page->getCollectionName();
            } catch (\Exception $e) {
                $metaTitle = $page->getCollectionName();
            }

            $pageData = [
                'title' => $metaTitle,
                'description' => $page->getCollectionDescription(),
                'content' => $content,
                'link' => $page->getCollectionLink(),
                'pagetype' => $page->getPageTypeHandle()
            ];

            // Index this single page to the appropriate store
            $this->indexSinglePage($pageData, $targetStoreType);
            
            $storeInfo = $targetStoreType ?: $this->determineStoreType($page->getPageTypeHandle());
            Log::addInfo("Successfully indexed page: {$metaTitle} (ID: {$pageId}) to {$storeInfo} store");
            
            // Clean output buffer and return success
            ob_get_clean();
            return true;
            
        } catch (\Exception $e) {
            Log::addError("Failed to index page ID {$command->getPageId()}: " . $e->getMessage());
            // Clean output buffer on error
            ob_get_clean();
            return false;
        }
    }

    /**
     * Index a single page using the new multi-store architecture
     */
    private function indexSinglePage(array $pageData, ?string $targetStoreType = null): void
    {
        try {
            $embeddingProvider = $this->pageIndexService->getEmbeddingProvider();
            
            // Determine which store this page should go to
            $storeType = $targetStoreType ?: $this->determineStoreType($pageData['pagetype']);
            
            // Get the appropriate vector store for this page type
            $vectorStore = $this->getVectorStoreForType($storeType);
            
            $documents = StringDataLoader::for($pageData['content'])->getDocuments();
            
            foreach ($documents as $document) {
                if (!($document instanceof \NeuronAI\RAG\Document)) {
                    throw new \RuntimeException('Expected Document, got: ' . (is_object($document) ? get_class($document) : gettype($document)));
                }
                
                // Add page metadata to document
                $document->sourceName = $pageData['title'];
                $document->sourceType = 'page';
                $document->addMetadata('url', $pageData['link']);
                $document->addMetadata('pagetype', $pageData['pagetype']);
                $document->addMetadata('store_type', $storeType);
                
                $document->embedding = $embeddingProvider->embedText($document->content);
                $vectorStore->addDocument($document);
            }
            
            Log::addInfo("Indexed page '{$pageData['title']}' to {$storeType} store");
            
        } catch (\Exception $e) {
            Log::addError("Error indexing page '{$pageData['title']}': " . $e->getMessage());
            throw $e; // Re-throw to be caught by main handler
        }
    }

    /**
     * Determine which store type a page belongs to
     */
    private function determineStoreType(string $pageType): string
    {
        $storeMap = [
            'legal_service_index' => 'legal_service_index',
            'legal_service' => 'legal_service', 
            'calculator_entry' => 'calculator_entry',
            'case_study' => 'case_study',
            'article' => 'article',
        ];
        
        return $storeMap[$pageType] ?? 'pages';
    }

    /**
     * Get vector store for specific page type
     */
    private function getVectorStoreForType(string $storeType): \NeuronAI\RAG\VectorStore\FileVectorStore
    {
        $storageDir = DIR_APPLICATION . '/files/neuron';
        
        if (!is_dir($storageDir)) {
            if (!mkdir($storageDir, 0775, true) && !is_dir($storageDir)) {
                throw new \RuntimeException("Failed to create directory: $storageDir");
            }
        }
        
        return new \NeuronAI\RAG\VectorStore\FileVectorStore(
            $storageDir,
            50, // Higher topK for batch operations
            $storeType
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