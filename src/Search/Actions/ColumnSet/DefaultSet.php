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


use Concrete\Core\Validation\Response;
use KatalysisProAi\Search\Actions\ColumnSet\Column\IdColumn;
use KatalysisProAi\Search\Actions\ColumnSet\Column\NameColumn;
use KatalysisProAi\Search\Actions\ColumnSet\Column\ActionTypeColumn;
use KatalysisProAi\Search\Actions\ColumnSet\Column\TriggerInstructionColumn;
use KatalysisProAi\Search\Actions\ColumnSet\Column\ResponseInstructionColumn;


class DefaultSet extends ColumnSet
{
    protected $attributeClass = 'CollectionAttributeKey';
    
    public function __construct()
    {
        $this->addColumn(new IdColumn());
        $this->addColumn(new NameColumn());
        $this->addColumn(new ActionTypeColumn());
        $this->addColumn(new TriggerInstructionColumn());
        //$this->addColumn(new ResponseInstructionColumn());
        $id = $this->getColumnByKey('a.id');
        $this->setDefaultSortColumn($id, 'desc');
    }
} 