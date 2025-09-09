<?php

namespace KatalysisProAi\Search\Chats\ColumnSet\Column;

use Concrete\Core\Search\Column\Column;
use Concrete\Core\Search\Column\PagerColumnInterface;
use Concrete\Core\Search\ItemList\Pager\PagerProviderInterface;

class LastMessageColumn extends Column implements PagerColumnInterface
{
    public function getColumnKey()
    {
        return 'c.lastMessage';
    }

    public function getColumnName()
    {
        return t('Last Message');
    }

    public function getColumnCallback()
    {
        return 'getLastMessage';
    }

    public function filterListAtOffset(PagerProviderInterface $itemList, $mixed)
    {
        $query = $itemList->getQueryObject();
        $sort = $this->getColumnSortDirection() == 'desc' ? '<' : '>';
        $where = sprintf('c.lastMessage %s :lastMessage', $sort);
        $query->setParameter('lastMessage', $mixed->getLastMessage());
        $query->andWhere($where);
    }
} 