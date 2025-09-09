<?php

namespace KatalysisProAi\Command;

use Concrete\Core\Foundation\Command\Command;

class BatchBuildRagIndexCommand extends Command
{
    private bool $clearExistingIndex;

    public function __construct(bool $clearExistingIndex = true)
    {
        $this->clearExistingIndex = $clearExistingIndex;
    }

    public function shouldClearExistingIndex(): bool
    {
        return $this->clearExistingIndex;
    }
}