<?php

namespace KatalysisProAi\Command\Task\Controller;

use Concrete\Core\Command\Task\Input\InputInterface;
use Concrete\Core\Command\Task\Runner\BatchProcessTaskRunner;
use Concrete\Core\Command\Task\Runner\TaskRunnerInterface;
use Concrete\Core\Command\Task\TaskInterface;
use Concrete\Core\Command\Task\Controller\AbstractController;
use Concrete\Core\Application\Application;

use KatalysisProAi\Command\BatchBuildRagIndexCommand;
use KatalysisProAi\Command\BatchBuildRagIndexCommandHandler;


class BuildRagIndexController extends AbstractController
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function getName(): string
    {
        return t("Build RAG Index");
    }

    public function getDescription(): string
    {
        return t("Builds the RAG index using batch processing to prevent timeouts on large sites.");
    }

    public function getTaskRunner(TaskInterface $task, InputInterface $input): TaskRunnerInterface
    {
        $command = new BatchBuildRagIndexCommand();
        
        // Execute the batch preparation command to get the batch
        $handler = $this->app->make(BatchBuildRagIndexCommandHandler::class);
        $batch = $handler($command);
        
        return new BatchProcessTaskRunner(
            $task,
            $batch,
            $input,
            t('Building RAG index with batch processing...')
        );
    }

}
