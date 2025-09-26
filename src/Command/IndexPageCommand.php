<?php

namespace KatalysisProAi\Command;

use Concrete\Core\Foundation\Command\Command;

class IndexPageCommand extends Command
{
    private int $pageId;
    private ?string $storeType;

    public function __construct(int $pageId, ?string $storeType = null)
    {
        $this->pageId = $pageId;
        $this->storeType = $storeType;
    }

    public function getPageId(): int
    {
        return $this->pageId;
    }

    public function getStoreType(): ?string
    {
        return $this->storeType;
    }
}