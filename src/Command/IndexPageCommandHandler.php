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
            Log::addInfo("INDEXING START: Processing page ID {$pageId}");
            
            $page = Page::getByID($pageId, 'APPROVED');
            
            if (!$page || $page->isError()) {
                Log::addWarning("INDEXING ERROR: Invalid page ID: {$pageId}");
                // Clean output buffer
                ob_get_clean();
                return false;
            }
            
            $content = $page->getPageIndexContent();
            
            // Skip pages with empty or invalid content
            if (!$this->isValidContent($content)) {
                Log::addInfo("INDEXING SKIP: Page '{$page->getCollectionName()}' - insufficient content (length: " . strlen($content) . ")");
                // Clean output buffer
                ob_get_clean();
                return false;
            }
            
            // Get collection name from the approved page
            $collectionName = '';
            $versionObject = $page->getVersionObject();
            if ($versionObject) {
                $collectionName = $versionObject->cvName ?: $versionObject->getVersionName();
            }
            
            // If approved version doesn't have name, try current version  
            if (empty($collectionName)) {
                $currentPage = Page::getByID($pageId);
                if ($currentPage && !$currentPage->isError()) {
                    $currentVersionObject = $currentPage->getVersionObject();
                    if ($currentVersionObject) {
                        $collectionName = $currentVersionObject->cvName ?: $currentVersionObject->getVersionName();
                    }
                    if (empty($collectionName)) {
                        $collectionName = $currentPage->getCollectionName();
                    }
                }
            }
            
            // Fallback methods
            if (empty($collectionName)) {
                $collectionName = $page->getCollectionName();
            }
            
            // Final fallback
            if (empty($collectionName)) {
                $collectionName = 'Untitled Page';
            }
            
            // Get meta title using proper Concrete CMS method
            $metaTitle = '';
            try {
                // Use the standard Concrete CMS method on the approved page
                $metaTitleAttr = $page->getCollectionAttributeValue('meta_title');
                
                // Fallback to current page if approved doesn't have it
                if (empty($metaTitleAttr) && isset($currentPage)) {
                    $metaTitleAttr = $currentPage->getCollectionAttributeValue('meta_title');
                } elseif (empty($metaTitleAttr)) {
                    $currentPage = Page::getByID($pageId);
                    if ($currentPage && !$currentPage->isError()) {
                        $metaTitleAttr = $currentPage->getCollectionAttributeValue('meta_title');
                    }
                }
                
                // Use meta title if found, otherwise fall back to collection name
                $metaTitle = $metaTitleAttr ?: $collectionName;
                
            } catch (\Exception $e) {
                $metaTitle = $collectionName;
            }
            
            // Ensure we never have empty meta title
            if (empty($metaTitle)) {
                $metaTitle = $collectionName;
            }

            $pageData = [
                'title' => (string)($metaTitle ?: 'Untitled Page'),
                'collection_name' => (string)($collectionName ?: 'Untitled Page'),
                'description' => (string)($page->getCollectionDescription() ?: ''),
                'content' => $content,
                'link' => (string)($page->getCollectionLink() ?: ''),
                'pagetype' => (string)($page->getPageTypeHandle() ?: ''),
                'page_id' => $page->getCollectionID()
            ];

            // Index this single page to the appropriate store
            $this->indexSinglePage($pageData, $targetStoreType);
            
            $storeInfo = $targetStoreType ?: 'all_pages';
            Log::addInfo("INDEXING SUCCESS: Page '{$metaTitle}' (ID: {$pageId}) indexed to {$storeInfo} collection");
            
            // Clean output buffer and return success
            ob_get_clean();
            return true;
            
        } catch (\Exception $e) {
            Log::addError("INDEXING FAILED: Page ID {$command->getPageId()} - " . $e->getMessage());
            Log::addError("INDEXING STACK TRACE: " . $e->getTraceAsString());
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
            
            // Use unified all_pages collection approach
            $storeType = 'all_pages';
            
            // Get the appropriate vector store for this page type
            $vectorStore = $this->getVectorStoreForType($storeType);
            
            $documents = StringDataLoader::for($pageData['content'])->getDocuments();
            
            $docIndex = 0;
            foreach ($documents as $document) {
                if (!($document instanceof \NeuronAI\RAG\Document)) {
                    throw new \RuntimeException('Expected Document, got: ' . (is_object($document) ? get_class($document) : gettype($document)));
                }
                
                // Generate deterministic ID based on page ID, document index, and content hash
                // This ensures the same page content gets the same ID for updates instead of duplicates
                $contentHash = md5($document->content);
                $deterministicId = "page_{$pageData['page_id']}_doc_{$docIndex}_{$contentHash}";
                $docIndex++; // Increment for each document from this page
                
                // Set the deterministic ID using reflection to override the auto-generated uniqid
                $reflection = new \ReflectionClass($document);
                $idProperty = $reflection->getProperty('id');
                $idProperty->setAccessible(true);
                $idProperty->setValue($document, $deterministicId);
                
                // Add page metadata to document (convert all to strings for Typesense compatibility)
                $document->sourceName = (string)($pageData['collection_name'] ?: 'Untitled Page'); // Use collection_name (page name) for better search result display, ensure not null
                $document->sourceType = 'page';
                $document->addMetadata('meta_title', (string)($pageData['title'] ?? 'Untitled Page')); // Store meta_title as metadata
                $document->addMetadata('url_path', (string)($this->pageIndexService->extractUrlPath($pageData['link'] ?? '') ?: '')); // Clean URL path for matching and reconstruction
                $document->addMetadata('pagetype', (string)($pageData['pagetype'] ?? 'page')); // Store the page type in metadata
                $document->addMetadata('specialisms', (string)($this->pageIndexService->extractSpecialisms($pageData) ?: '')); // Extract specialism hierarchy
                $document->addMetadata('parent_pages', (string)($this->pageIndexService->extractParentPages($pageData) ?: '')); // Extract parent page info
                $document->addMetadata('page_id', (string)($pageData['page_id'] ?? '0')); // Convert to string for Typesense compatibility
                $document->addMetadata('collection_version', (string)($this->pageIndexService->getPageVersion($pageData) ?: '1')); // Track page version for debugging
                $document->addMetadata('store_type', (string)($storeType ?: 'all_pages')); // Track which collection this belongs to
                
                // Add timestamp and update tracking information
                $document->addMetadata('indexed_date', date('Y-m-d H:i:s')); // Human-readable indexing date
                $document->addMetadata('page_modified_at', $this->pageIndexService->getPageModifiedTime($pageData)); // When the page was last modified (same format as indexed_date)
                $document->addMetadata('indexing_version', '2.0'); // Track indexing schema version
                
                $document->embedding = $embeddingProvider->embedText($document->content);
                $vectorStore->addDocument($document);
            }
            
        } catch (\Exception $e) {
            Log::addError("INDEXING ERROR in indexSinglePage for '{$pageData['title']}': " . $e->getMessage());
            Log::addError("INDEXING ERROR STACK: " . $e->getTraceAsString());
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
     * Get vector store for specific page type (Typesense or File-based)
     */
    private function getVectorStoreForType(string $storeType): \NeuronAI\RAG\VectorStore\VectorStoreInterface
    {
        // Detect actual embedding dimensions dynamically
        $vectorDimensions = $this->detectEmbeddingDimensions();
        
        // Use the factory to create either Typesense or File-based store
        return \KatalysisProAi\TypesenseVectorStoreFactory::create($storeType, 50, $vectorDimensions);
    }
    
    /**
     * Detect the actual embedding dimensions from OpenAI
     */
    private function detectEmbeddingDimensions(): int
    {
        try {
            $embeddingProvider = $this->pageIndexService->getEmbeddingProvider();
            $testEmbedding = $embeddingProvider->embedText("Test text for dimension detection");
            return count($testEmbedding);
        } catch (\Exception $e) {
            error_log("Warning: Could not detect embedding dimensions for pages, using default 1536: " . $e->getMessage());
            return 1536; // Fallback to default
        }
    }

    /**
     * Validate if content is suitable for processing
     */
    private function isValidContent($content): bool
    {
        return is_string($content) && !empty($content) && strlen($content) >= 50;
    }
}