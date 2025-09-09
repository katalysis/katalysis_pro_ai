<?php

namespace KatalysisProAi\Search\Chats\ColumnSet\Column;

use Concrete\Core\Search\Column\Column;
use Concrete\Core\Search\Column\PagerColumnInterface;
use Concrete\Core\Search\ItemList\Pager\PagerProviderInterface;

class UtmIdColumn extends Column implements PagerColumnInterface
{
    public function getColumnKey()
    {
        return 'c.utmId';
    }

    public function getColumnName()
    {
        return t('UTM ID');
    }

    public function getColumnCallback()
    {
        return 'getUtmId';
    }

    public function filterListAtOffset(PagerProviderInterface $itemList, $mixed)
    {
        $query = $itemList->getQueryObject();
        $sort = $this->getColumnSortDirection() == 'desc' ? '<' : '>';
        $where = sprintf('c.utmId %s :utmId', $sort);
        $query->setParameter('utmId', $mixed->getUtmId());
        $query->andWhere($where);
    }
} 