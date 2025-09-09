<?php

/**
 *
 * This file was build with the Entity Designer add-on.
 *
 * https://www.concrete5.org/marketplace/addons/entity-designer
 *
 */

/** @noinspection DuplicatedCode */

namespace KatalysisProAi\Search\Chats\ColumnSet\Column;

use Concrete\Core\Search\Column\Column;
use Concrete\Core\Search\Column\PagerColumnInterface;
use Concrete\Core\Search\ItemList\Pager\PagerProviderInterface;
use KatalysisProAi\Entity\Chat;
use KatalysisProAi\ChatList;

class IdColumn extends Column implements PagerColumnInterface
{
    public function getColumnKey()
    {
        return 'c.id';
    }
    
    public function getColumnName()
    {
        return t('Id');
    }
    
    public function getColumnCallback()
    {
        return 'getId';
    }
    
    /**
    * @param ChatList $itemList
    * @param $mixed Chat
    * @noinspection PhpDocSignatureInspection
    */
    public function filterListAtOffset(PagerProviderInterface $itemList, $mixed)
    {
        $query = $itemList->getQueryObject();
        $sort = $this->getColumnSortDirection() == 'desc' ? '<' : '>';
        $where = sprintf('c.id %s :id', $sort);
        $query->setParameter('id', $mixed->getId());
        $query->andWhere($where);
    }
}
