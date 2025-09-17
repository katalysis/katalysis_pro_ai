<?php

namespace KatalysisProAi\Command;

use Concrete\Core\Foundation\Command\Command;

class IndexKatalysisProEntityCommand extends Command
{
    private string $entityType;
    private bool $clearExisting;

    public function __construct(string $entityType, bool $clearExisting = false)
    {
        $this->entityType = $entityType;
        $this->clearExisting = $clearExisting;
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function shouldClearExisting(): bool
    {
        return $this->clearExisting;
    }
}
