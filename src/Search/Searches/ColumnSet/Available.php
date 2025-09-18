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
use KatalysisProAi\Search\Searches\ColumnSet\Column\CreatedDateColumn;
use KatalysisProAi\Search\Searches\ColumnSet\Column\NameColumn;
use KatalysisProAi\Search\Searches\ColumnSet\Column\UtmSourceColumn;

class Available extends ColumnSet
{
    protected $attributeClass = 'CollectionAttributeKey';
    
    public function __construct()
    {
        parent::__construct();
        
        $this->addColumn(new IdColumn());
        $this->addColumn(new StartedColumn());
        $this->addColumn(new QueryColumn());
        $this->addColumn(new ResultSummaryColumn());
        $this->addColumn(new LaunchPageTitleColumn());
        $this->addColumn(new LocationColumn());
        $this->addColumn(new CreatedDateColumn());
        $this->addColumn(new NameColumn());
        $this->addColumn(new UtmSourceColumn());
        
        // Additional columns can be added here as needed
    }
}
