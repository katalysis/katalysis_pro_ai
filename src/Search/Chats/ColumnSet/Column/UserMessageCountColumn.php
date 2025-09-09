<?php

namespace KatalysisProAi\Search\Chats\ColumnSet\Column;

use Concrete\Core\Search\Column\Column;
use Concrete\Core\Search\Column\PagerColumnInterface;
use Concrete\Core\Search\ItemList\Pager\PagerProviderInterface;

class UserMessageCountColumn extends Column implements PagerColumnInterface
{
    public function getColumnKey()
    {
        return 'c.userMessageCount';
    }

    public function getColumnName()
    {
        return t('Message Count');
    }

    public function getColumnCallback()
    {
        return 'getUserMessageCount';
    }

    public function filterListAtOffset(PagerProviderInterface $itemList, $mixed)
    {
        $query = $itemList->getQueryObject();
        $sort = $this->getColumnSortDirection() == 'desc' ? '<' : '>';
        $where = sprintf('c.userMessageCount %s :userMessageCount', $sort);
        $query->setParameter('userMessageCount', $mixed->getUserMessageCount());
        $query->andWhere($where);
    }
} 