<?php

namespace KatalysisProAi\Command\Task\Controller;

use Concrete\Core\Command\Task\Input\InputInterface;
use Concrete\Core\Command\Task\Runner\BatchProcessTaskRunner;
use Concrete\Core\Command\Task\Runner\TaskRunnerInterface;
use Concrete\Core\Command\Task\TaskInterface;
use Concrete\Core\Command\Task\Controller\AbstractController;
use Concrete\Core\Application\Application;

use KatalysisProAi\Command\BuildPageIndexCommand;
use KatalysisProAi\Command\BuildPageIndexCommandHandler;


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
        return t("Builds vector index for Pages.");
    }

    public function getTaskRunner(TaskInterface $task, InputInterface $input): TaskRunnerInterface
    {
        $command = new BuildPageIndexCommand();
        
        // Execute the batch preparation command to get the batch
        $handler = $this->app->make(BuildPageIndexCommandHandler::class);
        $batch = $handler($command);
        
        return new BatchProcessTaskRunner(
            $task,
            $batch,
            $input,
            t('Building page index with batch processing...')
        );
    }

}
