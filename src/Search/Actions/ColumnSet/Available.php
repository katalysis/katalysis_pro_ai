<?php

/**
 *
 * This file was build with the Entity Designer add-on.
 *
 * https://www.concrete5.org/marketplace/addons/entity-designer
 *
 */

/** @noinspection DuplicatedCode */

namespace KatalysisProAi\Search\Actions\ColumnSet;

use KatalysisProAi\Search\Actions\ColumnSet\Column\IdColumn;
use KatalysisProAi\Search\Actions\ColumnSet\Column\NameColumn;
use KatalysisProAi\Search\Actions\ColumnSet\Column\IconColumn;
use KatalysisProAi\Search\Actions\ColumnSet\Column\TriggerInstructionColumn;
use KatalysisProAi\Search\Actions\ColumnSet\Column\ResponseInstructionColumn;
use KatalysisProAi\Search\Actions\ColumnSet\Column\CreatedByColumn;
use KatalysisProAi\Search\Actions\ColumnSet\Column\CreatedDateColumn;
use Concrete\Core\Search\Column\Set;

class Available extends Set
{
    protected $attributeClass = 'CollectionAttributeKey';

    public function __construct()
    {
        $this->addColumn(new IdColumn());
        $this->addColumn(new NameColumn());
        $this->addColumn(new IconColumn());
        $this->addColumn(new TriggerInstructionColumn());
        $this->addColumn(new ResponseInstructionColumn());
        $this->addColumn(new CreatedByColumn());
        $this->addColumn(new CreatedDateColumn());
        
        $id = $this->getColumnByKey('a.id');
        $this->setDefaultSortColumn($id, 'desc');
    }
} 