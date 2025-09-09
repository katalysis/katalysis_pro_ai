<?php

namespace KatalysisProAi\Command;

defined('C5_EXECUTE') or die("Access Denied.");

use KatalysisProAi\RagBuildIndex;
use Concrete\Core\Support\Facade\Log;
use Page;
use NeuronAI\RAG\DataLoader\StringDataLoader;

class IndexSinglePageCommandHandler
{
    private RagBuildIndex $ragBuildIndex;

    public function __construct()
    {
        $this->ragBuildIndex = new RagBuildIndex();
    }

    public function __invoke(IndexSinglePageCommand $command)
    {
        try {
            $pageId = $command->getPageId();
            $page = Page::getByID($pageId);
            
            if (!$page || $page->isError()) {
                Log::addWarning("Skipping invalid page ID: {$pageId}");
                return false;
            }

            $content = $page->getPageIndexContent();
            
            // Skip pages with empty or invalid content
            if (!$this->isValidContent($content)) {
                Log::addInfo("Skipping page '{$page->getCollectionName()}' - insufficient content");
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
            return true;
            
        } catch (\Exception $e) {
            Log::addError("Failed to index page ID {$command->getPageId()}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Index a single page using the RagBuildIndex logic
     */
    private function indexSinglePage(array $pageData): void
    {
        $embeddingProvider = $this->ragBuildIndex->getEmbeddingProvider();
        $vectorStore = $this->ragBuildIndex->getVectorStore();
        
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
    }

    /**
     * Validate if content is suitable for processing
     */
    private function isValidContent($content): bool
    {
        return is_string($content) && !empty($content) && strlen($content) >= 50;
    }
}