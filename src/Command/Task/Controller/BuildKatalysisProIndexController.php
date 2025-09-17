<?php

namespace KatalysisProAi\Command\Task\Controller;

use Concrete\Core\Command\Task\Input\InputInterface;
use Concrete\Core\Command\Task\Runner\BatchProcessTaskRunner;
use Concrete\Core\Command\Task\Runner\TaskRunnerInterface;
use Concrete\Core\Command\Task\TaskInterface;
use Concrete\Core\Command\Task\Controller\AbstractController;
use Concrete\Core\Application\Application;

use KatalysisProAi\Command\BuildKatalysisProIndexCommand;
use KatalysisProAi\Command\BuildKatalysisProIndexCommandHandler;

class BuildKatalysisProIndexController extends AbstractController
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function getName(): string
    {
        return t("Build Katalysis Pro Indexes");
    }

    public function getDescription(): string
    {
        return t("Builds vector indexes for Katalysis Pro entities (People, Reviews, Places).");
    }

    public function getTaskRunner(TaskInterface $task, InputInterface $input): TaskRunnerInterface
    {
        $command = new BuildKatalysisProIndexCommand();
        
        // Execute the batch preparation command to get the batch (like RAG task does)
        $handler = new BuildKatalysisProIndexCommandHandler();
        $batch = $handler($command);
        
        return new BatchProcessTaskRunner(
            $task,
            $batch,
            $input,
            t('Building Katalysis Pro vector indexes with batch processing...')
        );
    }
}
