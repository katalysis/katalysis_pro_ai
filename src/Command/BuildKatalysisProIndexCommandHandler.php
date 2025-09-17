<?php

namespace KatalysisProAi\Command;

defined('C5_EXECUTE') or die("Access Denied.");

use Concrete\Core\Command\Batch\Batch;
use Concrete\Core\Support\Facade\Log;

class BuildKatalysisProIndexCommandHandler
{
    public function __invoke(BuildKatalysisProIndexCommand $command)
    {
        try {
            Log::addInfo('Starting Katalysis Pro vector index rebuild preparation...');
            
            $entityTypes = $command->getEntityTypes();
            
            // Create batch with individual entity indexing commands
            $batch = Batch::create();
            
            foreach ($entityTypes as $entityType) {
                $batch->add(new IndexKatalysisProEntityCommand($entityType));
            }
            
            Log::addInfo('Batch preparation completed for entity types: ' . implode(', ', $entityTypes));
            
            return $batch;
            
        } catch (\Exception $e) {
            Log::addError('Katalysis Pro vector index rebuild preparation failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
