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
use KatalysisProAi\ChatsList;

class LocationColumn extends Column implements PagerColumnInterface
{
    public function getColumnKey()
    {
        return 'c.location';
    }
    
    public function getColumnName()
    {
        return t('Location');
    }
    
    public function getColumnCallback()
    {
        return 'getLocation';
    }
    
    /**
    * @param ChatsList $itemList
    * @param $mixed Chat
    * @noinspection PhpDocSignatureInspection
    */
    public function filterListAtOffset(PagerProviderInterface $itemList, $mixed)
    {
        $query = $itemList->getQueryObject();
        $sort = $this->getColumnSortDirection() == 'desc' ? '<' : '>';
        $where = sprintf('c.location %s :location', $sort);
        $query->setParameter('location', $mixed->getLocation());
        $query->andWhere($where);
    }
}
