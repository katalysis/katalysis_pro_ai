<?php

/**
 *
 * This file was build with the Entity Designer add-on.
 *
 * https://www.concrete5.org/marketplace/addons/entity-designer
 *
 */

/** @noinspection DuplicatedCode */

namespace KatalysisProAi\Search\Chats\ColumnSet;

use KatalysisProAi\Search\Chats\ColumnSet\Column\IdColumn;
use KatalysisProAi\Search\Chats\ColumnSet\Column\StartedColumn;
use KatalysisProAi\Search\Chats\ColumnSet\Column\FirstMessageColumn;
use KatalysisProAi\Search\Chats\ColumnSet\Column\LastMessageColumn;
use KatalysisProAi\Search\Chats\ColumnSet\Column\LocationColumn;
use KatalysisProAi\Search\Chats\ColumnSet\Column\UserMessageCountColumn;


class DefaultSet extends ColumnSet
{
    protected $attributeClass = 'CollectionAttributeKey';
    
    public function __construct()
    {
        $this->addColumn(new IdColumn());
        $this->addColumn(new StartedColumn());
        $this->addColumn(new UserMessageCountColumn());
        $this->addColumn(new FirstMessageColumn());
        $this->addColumn(new LastMessageColumn());
        $this->addColumn(new LocationColumn());
        $id = $this->getColumnByKey('c.id');
        $this->setDefaultSortColumn($id, 'desc');
    }
}
