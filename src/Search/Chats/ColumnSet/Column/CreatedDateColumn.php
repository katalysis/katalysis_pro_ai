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

class CreatedDateColumn extends Column implements PagerColumnInterface
{
    public function getColumnKey()
    {
        return 'c.CreatedDate';
    }
    
    public function getColumnName()
    {
        return t('Created Date');
    }
    
    public function getColumnCallback()
    {
        return 'getDisplayCreatedDate';
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
        $where = sprintf('c.CreatedDate %s :CreatedDate', $sort);
        $query->setParameter('CreatedDate', $mixed->getCreatedDate());
        $query->andWhere($where);
    }
} 