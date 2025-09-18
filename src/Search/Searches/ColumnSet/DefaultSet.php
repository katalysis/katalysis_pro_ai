<?php

/**
 *
 * This file was build with the Entity Designer add-on.
 *
 * https://www.concrete5.org/marketplace/addons/entity-designer
 *
 */

/** @noinspection DuplicatedCode */

namespace KatalysisProAi\Search\Searches\ColumnSet;

use KatalysisProAi\Search\Searches\ColumnSet\Column\IdColumn;
use KatalysisProAi\Search\Searches\ColumnSet\Column\StartedColumn;
use KatalysisProAi\Search\Searches\ColumnSet\Column\QueryColumn;
use KatalysisProAi\Search\Searches\ColumnSet\Column\ResultSummaryColumn;
use KatalysisProAi\Search\Searches\ColumnSet\Column\LaunchPageTitleColumn;
use KatalysisProAi\Search\Searches\ColumnSet\Column\LocationColumn;

class DefaultSet extends ColumnSet
{
    protected $attributeClass = 'CollectionAttributeKey';
    
    public function __construct()
    {
        $this->addColumn(new IdColumn());
        $this->addColumn(new StartedColumn());
        $this->addColumn(new QueryColumn());
        $this->addColumn(new ResultSummaryColumn());
        $this->addColumn(new LaunchPageTitleColumn());
        $this->addColumn(new LocationColumn());
        
        $dateKey = $this->getColumnByKey('s.started');
        if ($dateKey) {
            $this->setDefaultSortColumn($dateKey, 'desc');
        }
    }
}
