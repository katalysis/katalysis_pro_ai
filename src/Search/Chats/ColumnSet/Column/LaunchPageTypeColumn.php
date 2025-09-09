<?php

namespace KatalysisProAi\Search\Chats\ColumnSet\Column;

use Concrete\Core\Search\Column\Column;
use Concrete\Core\Search\Column\PagerColumnInterface;
use Concrete\Core\Search\ItemList\Pager\PagerProviderInterface;

class LaunchPageTypeColumn extends Column implements PagerColumnInterface
{
    public function getColumnKey()
    {
        return 'c.launchPageType';
    }

    public function getColumnName()
    {
        return t('Launch Page Type');
    }

    public function getColumnCallback()
    {
        return 'getLaunchPageType';
    }

    public function filterListAtOffset(PagerProviderInterface $itemList, $mixed)
    {
        $query = $itemList->getQueryObject();
        $sort = $this->getColumnSortDirection() == 'desc' ? '<' : '>';
        $where = sprintf('c.launchPageType %s :launchPageType', $sort);
        $query->setParameter('launchPageType', $mixed->getLaunchPageType());
        $query->andWhere($where);
    }
} 