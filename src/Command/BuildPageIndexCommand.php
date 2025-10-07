<?php

namespace KatalysisProAi\Command;

use Concrete\Core\Foundation\Command\Command;

class BuildPageIndexCommand extends Command
{
    private bool $clearExistingIndex;
    private array $selectedStores;
    private ?array $pageIds;

    public function __construct(bool $clearExistingIndex = true, array $selectedStores = [], ?array $pageIds = null)
    {
        $this->clearExistingIndex = $clearExistingIndex;
        $this->selectedStores = $selectedStores;
        $this->pageIds = $pageIds;
    }

    public function shouldClearExistingIndex(): bool
    {
        return $this->clearExistingIndex;
    }

    public function getSelectedStores(): array
    {
        return $this->selectedStores;
    }

    public function getPageIds(): ?array
    {
        return $this->pageIds;
    }

    public function hasSpecificPages(): bool
    {
        return $this->pageIds !== null && !empty($this->pageIds);
    }
}