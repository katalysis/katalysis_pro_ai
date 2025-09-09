<?php

namespace KatalysisProAi\Search\Chats\ColumnSet\Column;

use Concrete\Core\Search\Column\Column;
use Concrete\Core\Search\Column\PagerColumnInterface;
use Concrete\Core\Search\ItemList\Pager\PagerProviderInterface;

class UtmMediumColumn extends Column implements PagerColumnInterface
{
    public function getColumnKey()
    {
        return 'c.utmMedium';
    }

    public function getColumnName()
    {
        return t('UTM Medium');
    }

    public function getColumnCallback()
    {
        return 'getUtmMedium';
    }

    public function filterListAtOffset(PagerProviderInterface $itemList, $mixed)
    {
        $query = $itemList->getQueryObject();
        $sort = $this->getColumnSortDirection() == 'desc' ? '<' : '>';
        $where = sprintf('c.utmMedium %s :utmMedium', $sort);
        $query->setParameter('utmMedium', $mixed->getUtmMedium());
        $query->andWhere($where);
    }
} 