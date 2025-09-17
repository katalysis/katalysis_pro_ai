<?php

namespace KatalysisProAi\Command;

use Concrete\Core\Foundation\Command\Command;

class BuildKatalysisProIndexCommand extends Command
{
    private bool $clearExistingIndex;
    private array $entityTypes;

    public function __construct(bool $clearExistingIndex = true, array $entityTypes = ['people', 'reviews', 'places'])
    {
        $this->clearExistingIndex = $clearExistingIndex;
        $this->entityTypes = $entityTypes;
    }

    public function shouldClearExistingIndex(): bool
    {
        return $this->clearExistingIndex;
    }

    public function getEntityTypes(): array
    {
        return $this->entityTypes;
    }
}
