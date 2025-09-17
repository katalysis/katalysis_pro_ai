<?php

namespace KatalysisProAi\Command;

defined('C5_EXECUTE') or die("Access Denied.");

use KatalysisProAi\PageIndexService;
use Concrete\Core\Support\Facade\Log;
use Concrete\Core\Page\PageList;
use Concrete\Core\Command\Batch\Batch;

class BuildPageIndexCommandHandler
{
    public function __invoke(BuildPageIndexCommand $command)
    {
        try {
            Log::addInfo('Starting batch page index rebuild preparation...');
            
            $pageIndexService = new PageIndexService();
            
            // Clear existing index if requested
            if ($command->shouldClearExistingIndex()) {
                Log::addInfo('Clearing existing index...');
                $pageIndexService->clearIndex();
            }
            
            // Get all pages to index
            $ipl = new PageList();
            $ipl->setSiteTreeToAll();
            $pages = $ipl->getResults();
            
            $validPageIds = [];
            foreach ($pages as $page) {
                // Pre-filter pages to avoid processing empty content in batch
                $content = $page->getPageIndexContent();
                if ($this->isValidContent($content)) {
                    $validPageIds[] = $page->getCollectionID();
                }
            }
            
            Log::addInfo('Prepared ' . count($validPageIds) . ' valid pages for batch indexing out of ' . count($pages) . ' total pages.');
            
            // Create batch with individual page indexing commands
            $batch = Batch::create();
            foreach ($validPageIds as $pageId) {
                $batch->add(new IndexPageCommand($pageId));
            }
            
            Log::addInfo('Batch preparation completed. Ready to process ' . count($validPageIds) . ' pages.');
            
            return $batch;
            
        } catch (\Exception $e) {
            Log::addError('Batch page index rebuild preparation failed: ' . $e->getMessage());
            throw $e;
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