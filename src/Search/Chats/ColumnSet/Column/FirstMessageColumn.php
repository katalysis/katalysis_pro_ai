<?php

namespace KatalysisProAi\Search\Chats\ColumnSet\Column;

use Concrete\Core\Search\Column\Column;
use Concrete\Core\Search\Column\PagerColumnInterface;
use Concrete\Core\Search\ItemList\Pager\PagerProviderInterface;

class FirstMessageColumn extends Column implements PagerColumnInterface
{
    public function getColumnKey()
    {
        return 'c.firstMessage';
    }

    public function getColumnName()
    {
        return t('First Message');
    }

    public function getColumnCallback()
    {
        return 'getFirstMessage';
    }

    public function filterListAtOffset(PagerProviderInterface $itemList, $mixed)
    {
        $query = $itemList->getQueryObject();
        $sort = $this->getColumnSortDirection() == 'desc' ? '<' : '>';
        $where = sprintf('c.firstMessage %s :firstMessage', $sort);
        $query->setParameter('firstMessage', $mixed->getFirstMessage());
        $query->andWhere($where);
    }
} 