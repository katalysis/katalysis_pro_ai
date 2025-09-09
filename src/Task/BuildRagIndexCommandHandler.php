<?php

namespace KatalysisProAi\Task;

use Concrete\Core\Command\Task\Output\OutputAwareInterface;
use Concrete\Core\Command\Task\Output\OutputInterface;
use Concrete\Core\Support\Facade\Log;
use KatalysisProAi\RagBuildIndex;

class BuildRagIndexCommandHandler implements OutputAwareInterface
{
    protected $output;

    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function __invoke()
    {
        try {
            $this->output->write('Starting RAG index rebuild...');
            Log::addInfo('Starting automated RAG index rebuild...');
            
            $ragBuildIndex = new RagBuildIndex();
            
            // Clear existing index first
            $this->output->write('Clearing existing index...');
            Log::addInfo('Clearing existing index...');
            $ragBuildIndex->clearIndex();
            
            // Build new index
            $this->output->write('Building new index...');
            Log::addInfo('Building new index...');
            $results = $ragBuildIndex->buildIndex();
            $ragBuildIndex->addDocuments($results);
            
            $message = 'RAG index rebuild completed successfully. Indexed ' . count($results) . ' pages.';
            $this->output->write($message);
            Log::addInfo($message);
            
        } catch (\Exception $e) {
            $errorMessage = 'RAG index rebuild failed: ' . $e->getMessage();
            $this->output->writeError($errorMessage);
            Log::addError($errorMessage);
            throw $e;
        }
    }
} 