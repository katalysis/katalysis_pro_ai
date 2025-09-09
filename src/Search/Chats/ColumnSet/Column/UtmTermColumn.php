<?php

namespace KatalysisProAi\Search\Chats\ColumnSet\Column;

use Concrete\Core\Search\Column\Column;
use Concrete\Core\Search\Column\PagerColumnInterface;
use Concrete\Core\Search\ItemList\Pager\PagerProviderInterface;

class UtmTermColumn extends Column implements PagerColumnInterface
{
    public function getColumnKey()
    {
        return 'c.utmTerm';
    }

    public function getColumnName()
    {
        return t('UTM Term');
    }

    public function getColumnCallback()
    {
        return 'getUtmTerm';
    }

    public function filterListAtOffset(PagerProviderInterface $itemList, $mixed)
    {
        $query = $itemList->getQueryObject();
        $sort = $this->getColumnSortDirection() == 'desc' ? '<' : '>';
        $where = sprintf('c.utmTerm %s :utmTerm', $sort);
        $query->setParameter('utmTerm', $mixed->getUtmTerm());
        $query->andWhere($where);
    }
} 