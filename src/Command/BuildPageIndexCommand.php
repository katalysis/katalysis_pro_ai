<?php

namespace KatalysisProAi\Command;

use Concrete\Core\Foundation\Command\Command;

class BuildPageIndexCommand extends Command
{
    private bool $clearExistingIndex;
    private array $selectedStores;

    public function __construct(bool $clearExistingIndex = true, array $selectedStores = [])
    {
        $this->clearExistingIndex = $clearExistingIndex;
        $this->selectedStores = $selectedStores;
    }

    public function shouldClearExistingIndex(): bool
    {
        return $this->clearExistingIndex;
    }

    public function getSelectedStores(): array
    {
        return $this->selectedStores;
    }
}