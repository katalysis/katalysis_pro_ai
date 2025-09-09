<?php

/**
 *
 * This file was build with the Entity Designer add-on.
 *
 * https://www.concrete5.org/marketplace/addons/entity-designer
 *
 */

/** @noinspection DuplicatedCode */

namespace KatalysisProAi\Search\Actions\Result;

use Concrete\Core\Search\Result\Result as SearchResult;

class Result extends SearchResult
{
    public function getItemDetails($item)
    {
        return new Item($this, $this->listColumns, $item);
    }
    
    public function getColumnDetails($column)
    {
        return new Column($this, $column);
    }
} 