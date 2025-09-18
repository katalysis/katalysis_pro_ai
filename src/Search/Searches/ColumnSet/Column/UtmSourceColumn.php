<?php

namespace KatalysisProAi\Search\Searches\ColumnSet\Column;

use Concrete\Core\Search\Column\Column;
use Concrete\Core\Search\Column\PagerColumnInterface;
use Concrete\Core\Search\ItemList\Pager\PagerProviderInterface;

class UtmSourceColumn extends Column implements PagerColumnInterface
{
    public function getColumnKey()
    {
        return 's.utmSource';
    }

    public function getColumnName()
    {
        return t('UTM Source');
    }

    public function getColumnCallback()
    {
        return 'getUtmSource';
    }

    public function filterListAtOffset(PagerProviderInterface $itemList, $mixed)
    {
        $query = $itemList->getQueryObject();
        $sort = $this->getColumnSortDirection() == 'desc' ? '<' : '>';
        $where = sprintf('s.utmSource %s :utmSource', $sort);
        $query->setParameter('utmSource', $mixed->getUtmSource());
        $query->andWhere($where);
    }
}
