<?php

/**
 *
 * This file was build with the Entity Designer add-on.
 *
 * https://www.concrete5.org/marketplace/addons/entity-designer
 *
 */

/** @noinspection DuplicatedCode */

namespace KatalysisProAi\Search\Actions\ColumnSet\Column;

use Concrete\Core\Search\Column\Column;
use Concrete\Core\Search\Column\PagerColumnInterface;
use Concrete\Core\Search\ItemList\Pager\PagerProviderInterface;
use KatalysisProAi\Entity\Action;
use KatalysisProAi\ActionList;

class ResponseInstructionColumn extends Column implements PagerColumnInterface
{
    public function getColumnKey()
    {
        return 'a.responseInstruction';
    }
    
    public function getColumnName()
    {
        return t('Response Instruction');
    }
    
    public function getColumnCallback()
    {
        return 'getResponseInstruction';
    }
    
    /**
    * @param ActionList $itemList
    * @param $mixed Action
    * @noinspection PhpDocSignatureInspection
    */
    public function filterListAtOffset(PagerProviderInterface $itemList, $mixed)
    {
        $query = $itemList->getQueryObject();
        $sort = $this->getColumnSortDirection() == 'desc' ? '<' : '>';
        $where = sprintf('a.responseInstruction %s :responseInstruction', $sort);
        $query->setParameter('responseInstruction', $mixed->getResponseInstruction());
        $query->andWhere($where);
    }
} 