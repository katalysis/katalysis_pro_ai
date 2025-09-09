<?php

namespace KatalysisProAi\Search\Chats\ColumnSet\Column;

use Concrete\Core\Search\Column\Column;
use Concrete\Core\Search\Column\PagerColumnInterface;
use Concrete\Core\Search\ItemList\Pager\PagerProviderInterface;

class UtmContentColumn extends Column implements PagerColumnInterface
{
    public function getColumnKey()
    {
        return 'c.utmContent';
    }

    public function getColumnName()
    {
        return t('UTM Content');
    }

    public function getColumnCallback()
    {
        return 'getUtmContent';
    }

    public function filterListAtOffset(PagerProviderInterface $itemList, $mixed)
    {
        $query = $itemList->getQueryObject();
        $sort = $this->getColumnSortDirection() == 'desc' ? '<' : '>';
        $where = sprintf('c.utmContent %s :utmContent', $sort);
        $query->setParameter('utmContent', $mixed->getUtmContent());
        $query->andWhere($where);
    }
} 