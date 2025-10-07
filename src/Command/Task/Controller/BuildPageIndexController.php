<?php

namespace KatalysisProAi\Command\Task\Controller;

use Concrete\Core\Command\Task\Input\Definition\Definition;
use Concrete\Core\Command\Task\Input\Definition\Field;
use Concrete\Core\Command\Task\Input\Definition\SelectField;
use Concrete\Core\Command\Task\Input\Definition\BooleanField;
use Concrete\Core\Command\Task\Input\InputInterface;
use Concrete\Core\Command\Task\Runner\BatchProcessTaskRunner;
use Concrete\Core\Command\Task\Runner\TaskRunnerInterface;
use Concrete\Core\Command\Task\TaskInterface;
use Concrete\Core\Command\Task\Controller\AbstractController;
use Concrete\Core\Application\Application;

use KatalysisProAi\Command\BuildPageIndexCommand;
use KatalysisProAi\Command\BuildPageIndexCommandHandler;
use KatalysisProAi\PageIndexService;

class BuildPageIndexController extends AbstractController
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function getName(): string
    {
        return t("Build Page Index");
    }

    public function getDescription(): string
    {
        return t("Builds a unified vector index for AI search and chat bot, including all page types for maximum intelligence and comprehensive search coverage.");
    }

    public function getInputDefinition(): ?Definition
    {
        $definition = new Definition();
        
        // Since we now use a single unified store, we don't need the index type selection
        // Just provide the clear existing option
        $definition->addField(new BooleanField(
            'clear_existing', 
            t('Clear Existing Index'), 
            t('Remove existing index data before building new index. Recommended for clean rebuild.')
        ));
        
        // Add page ID field for rebuilding specific pages or ranges
        $definition->addField(new Field(
            'page_ids', 
            t('Page IDs (Optional)'), 
            t('Leave empty to rebuild all pages, or specify: single page (e.g., "1234"), range (e.g., "1234-5678"), or comma-separated list (e.g., "1234,5678,9012")')
        ));
        
        return $definition;
    }

    public function getTaskRunner(TaskInterface $task, InputInterface $input): TaskRunnerInterface
    {
        // Since we use a single unified store, always use 'all_pages'
        $selectedStores = ['all_pages'];
        
        // Parse clear existing flag from BooleanField
        $clearExisting = false; // default value
        if ($input->hasField('clear_existing')) {
            $clearExisting = (bool) $input->getField('clear_existing')->getValue();
        }
        
        // Parse page IDs field
        $pageIds = null;
        if ($input->hasField('page_ids')) {
            $pageIdsValue = trim((string) $input->getField('page_ids')->getValue());
            if (!empty($pageIdsValue)) {
                $pageIds = $this->parsePageIds($pageIdsValue);
            }
        }
        
        // Create the command with proper parameter order (clearExisting first, selectedStores second, pageIds third)
        $command = new BuildPageIndexCommand($clearExisting, $selectedStores, $pageIds);
        
        // Execute the batch preparation command to get the batch
        $handler = new BuildPageIndexCommandHandler();
        $batch = $handler($command);
        
        // Dynamic task title based on whether specific pages are being rebuilt
        if ($pageIds !== null && !empty($pageIds)) {
            $pageCount = count($pageIds);
            $taskTitle = "Building page index [{$pageCount} specific pages]";
        } else {
            $taskTitle = 'Building unified page index [All Pages]';
        }
        
        return new BatchProcessTaskRunner(
            $task,
            $batch,
            $input,
            $taskTitle
        );
    }

    /**
     * Parse page IDs input into array of integers
     * Supports: single ID (1234), ranges (1234-5678), comma-separated (1234,5678,9012)
     */
    private function parsePageIds(string $input): array
    {
        $pageIds = [];
        
        // Split by commas first to handle multiple entries
        $entries = array_map('trim', explode(',', $input));
        
        foreach ($entries as $entry) {
            if (empty($entry)) {
                continue;
            }
            
            // Check if it's a range (contains hyphen)
            if (strpos($entry, '-') !== false) {
                $rangeParts = explode('-', $entry, 2);
                if (count($rangeParts) === 2) {
                    $start = (int) trim($rangeParts[0]);
                    $end = (int) trim($rangeParts[1]);
                    
                    if ($start > 0 && $end > 0 && $start <= $end) {
                        for ($i = $start; $i <= $end; $i++) {
                            $pageIds[] = $i;
                        }
                    }
                }
            } else {
                // Single page ID
                $pageId = (int) $entry;
                if ($pageId > 0) {
                    $pageIds[] = $pageId;
                }
            }
        }
        
        // Remove duplicates and return sorted
        return array_unique($pageIds);
    }
}
