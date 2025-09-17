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

            $pageData = [
                'title' => $page->getCollectionName(),
                'description' => $page->getCollectionDescription(),
                'content' => $content,
                'link' => $page->getCollectionLink(),
                'pagetype' => $page->getPageTypeHandle()
            ];

            // Index this single page
            $this->indexSinglePage($pageData);
            
            Log::addInfo("Successfully indexed page: {$page->getCollectionName()} (ID: {$pageId})");
            
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
     * Index a single page using the PageIndexService logic
     */
    private function indexSinglePage(array $pageData): void
    {
        try {
            $embeddingProvider = $this->pageIndexService->getEmbeddingProvider();
            $vectorStore = $this->pageIndexService->getVectorStore();
            
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
                
                $document->embedding = $embeddingProvider->embedText($document->content);
                $vectorStore->addDocument($document);
            }
        } catch (\Exception $e) {
            Log::addError("Error indexing page '{$pageData['title']}': " . $e->getMessage());
            throw $e; // Re-throw to be caught by main handler
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