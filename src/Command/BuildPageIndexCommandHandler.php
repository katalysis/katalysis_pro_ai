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
            $selectedStores = $command->getSelectedStores();
            $storeInfo = empty($selectedStores) ? 'all stores' : implode(', ', $selectedStores);
            
            // Add info about page filtering
            $pageInfo = '';
            if ($command->hasSpecificPages()) {
                $pageIds = $command->getPageIds();
                $pageCount = count($pageIds);
                $pageInfo = " (filtering {$pageCount} specific pages: " . implode(', ', array_slice($pageIds, 0, 10)) . 
                           ($pageCount > 10 ? ', ...' : '') . ")";
            }
            
            Log::addInfo("Starting batch page index rebuild preparation for: {$storeInfo}{$pageInfo}");
            
            $pageIndexService = new PageIndexService();
            
            // Clear existing indexes if requested
            if ($command->shouldClearExistingIndex()) {
                Log::addInfo('Clearing existing indexes...');
                $pageIndexService->clearIndex($selectedStores); // Pass selected stores to clear
            }
            
            // Get pages organized by store type
            $pageIds = $command->getPageIds();
            $pagesByType = $pageIndexService->buildIndex($selectedStores, $pageIds);
            
            // Create batch with individual page indexing commands for each relevant page
            $batch = Batch::create();
            $totalValidPages = 0;
            
            foreach ($pagesByType as $storeType => $pages) {
                Log::addInfo("Store '{$storeType}': Found " . count($pages) . " valid pages");
                
                foreach ($pages as $page) {
                    // Create IndexPageCommand with store type info
                    $batch->add(new IndexPageCommand($page['page_id'], $storeType));
                    $totalValidPages++;
                }
            }
            
            if ($totalValidPages === 0) {
                Log::addWarning('No valid pages found for selected store types.');
                return Batch::create(); // Return empty batch
            }
            
            Log::addInfo("Batch preparation completed. Ready to process {$totalValidPages} pages across " . count($pagesByType) . " store types.");
            
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