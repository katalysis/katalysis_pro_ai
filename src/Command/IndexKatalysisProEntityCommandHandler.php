<?php

namespace KatalysisProAi\Command;

defined('C5_EXECUTE') or die("Access Denied.");

use KatalysisProAi\KatalysisProIndexService;
use Concrete\Core\Support\Facade\Log;

class IndexKatalysisProEntityCommandHandler
{
    public function __invoke(IndexKatalysisProEntityCommand $command)
    {
        try {
            $entityType = $command->getEntityType();
            Log::addInfo("Starting {$entityType} vector index build...");
            
            $vectorBuilder = new KatalysisProIndexService();
            
            // Capture echo output to prevent JSON interference
            ob_start();
            
            // Build the specific entity index
            switch ($entityType) {
                case 'people':
                    $vectorBuilder->buildPeopleIndex();
                    break;
                    
                case 'reviews':
                    $vectorBuilder->buildReviewsIndex();
                    break;
                    
                case 'places':
                    $vectorBuilder->buildPlacesIndex();
                    break;
                    
                default:
                    throw new \InvalidArgumentException("Unknown entity type: {$entityType}");
            }
            
            // Capture the echo output and extract useful information
            $buildOutput = ob_get_clean();
            
            // Parse output to get count and log it properly (instead of echoing)
            if (preg_match('/Indexed (\d+) ' . $entityType . '/', $buildOutput, $matches)) {
                $count = $matches[1];
                Log::addInfo("Successfully indexed {$count} {$entityType}");
            } else {
                Log::addInfo("Successfully completed {$entityType} vector index build");
            }
            
            return true;
            
        } catch (\Exception $e) {
            // Clean any remaining output buffer
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            Log::addError("Failed to build {$entityType} vector index: " . $e->getMessage());
            throw $e;
        }
    }
}
