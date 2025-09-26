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
        return t("Build Page Index (By Page Type)");
    }

    public function getDescription(): string
    {
        return t("Builds vector indexes organized by page type. Select specific page types to rebuild or choose 'Select All' for all types. Each page type creates its own .store file named by the page type handle.");
    }

    public function getInputDefinition(): ?Definition
    {
        $definition = new Definition();
        
        // Get available page types from PageIndexService
        $pageIndexService = new PageIndexService();
        $pageTypes = $pageIndexService->getAvailableStores();
        
        // Add compact checkboxes for page types
        $definition->addField(new BooleanField('select_all_page_types', t('All Page Types'), ''));
        
        foreach ($pageTypes as $handle => $name) {
            $definition->addField(new BooleanField("page_type_{$handle}", $name, ''));
        }
        
        $definition->addField(new BooleanField('clear_existing', t('Clear Existing'), ''));
        
        return $definition;
    }

    public function getTaskRunner(TaskInterface $task, InputInterface $input): TaskRunnerInterface
    {
        // Get available page types to know what checkboxes to look for
        $pageIndexService = new PageIndexService();
        $pageTypes = $pageIndexService->getAvailableStores();
        
        $selectedStores = [];
        
        // Check if "Select All" is checked
        $selectAll = false;
        if ($input->hasField('select_all_page_types')) {
            $selectAll = (bool) $input->getField('select_all_page_types')->getValue();
        }
        
        if ($selectAll) {
            // If "Select All" is checked, use empty array to indicate all stores
            $selectedStores = [];
        } else {
            // Check individual page type checkboxes
            foreach ($pageTypes as $handle => $name) {
                $fieldName = "page_type_{$handle}";
                if ($input->hasField($fieldName)) {
                    $isSelected = (bool) $input->getField($fieldName)->getValue();
                    if ($isSelected) {
                        $selectedStores[] = $handle;
                    }
                }
            }
            
            // If no individual page types are selected, default to all
            if (empty($selectedStores)) {
                $selectedStores = [];
            }
        }
        
        // Parse clear existing flag from BooleanField
        $clearExisting = false; // default value
        if ($input->hasField('clear_existing')) {
            $clearExisting = (bool) $input->getField('clear_existing')->getValue();
        }
        
        // Create the command with proper parameter order (clearExisting first, selectedStores second)
        $command = new BuildPageIndexCommand($clearExisting, $selectedStores);
        
        // Execute the batch preparation command to get the batch
        $handler = new BuildPageIndexCommandHandler();
        $batch = $handler($command);
        
        // Build dynamic task title with selected page types
        $taskTitle = $this->buildTaskTitle($selectedStores, $selectAll, $pageTypes);
        
        return new BatchProcessTaskRunner(
            $task,
            $batch,
            $input,
            $taskTitle
        );
    }

    /**
     * Build dynamic task title with selected page types
     */
    private function buildTaskTitle(array $selectedStores, bool $selectAll, array $pageTypes): string
    {
        $baseTitle = 'Building page index';
        
        if ($selectAll || empty($selectedStores)) {
            // All page types selected
            return $baseTitle . ' [All Page Types]';
        }
        
        // Build list of selected page type names
        $selectedNames = [];
        foreach ($selectedStores as $handle) {
            if (isset($pageTypes[$handle])) {
                $selectedNames[] = $pageTypes[$handle];
            }
        }
        
        if (empty($selectedNames)) {
            return $baseTitle . ' [No Page Types Selected]';
        }
        
        // Limit the display to avoid overly long titles
        if (count($selectedNames) > 3) {
            $displayNames = array_slice($selectedNames, 0, 3);
            $remaining = count($selectedNames) - 3;
            $typesList = implode(', ', $displayNames) . " + {$remaining} more";
        } else {
            $typesList = implode(', ', $selectedNames);
        }
        
        return $baseTitle . " [{$typesList}]";
    }
}
