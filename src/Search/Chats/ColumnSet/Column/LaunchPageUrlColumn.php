<?php

namespace KatalysisProAi\Search\Chats\ColumnSet\Column;

use Concrete\Core\Search\Column\Column;
use Concrete\Core\Search\Column\PagerColumnInterface;
use Concrete\Core\Search\ItemList\Pager\PagerProviderInterface;

class LaunchPageUrlColumn extends Column implements PagerColumnInterface
{
    public function getColumnKey()
    {
        return 'c.launchPageUrl';
    }

    public function getColumnName()
    {
        return t('Launch Page URL');
    }

    public function getColumnCallback()
    {
        return 'getLaunchPageUrl';
    }

    public function filterListAtOffset(PagerProviderInterface $itemList, $mixed)
    {
        $query = $itemList->getQueryObject();
        $sort = $this->getColumnSortDirection() == 'desc' ? '<' : '>';
        $where = sprintf('c.launchPageUrl %s :launchPageUrl', $sort);
        $query->setParameter('launchPageUrl', $mixed->getLaunchPageUrl());
        $query->andWhere($where);
    }
} 