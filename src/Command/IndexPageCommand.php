<?php

namespace KatalysisProAi\Command;

use Concrete\Core\Foundation\Command\Command;

class IndexPageCommand extends Command
{
    private int $pageId;

    public function __construct(int $pageId)
    {
        $this->pageId = $pageId;
    }

    public function getPageId(): int
    {
        return $this->pageId;
    }
}