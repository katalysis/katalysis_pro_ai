<?php

/** @noinspection DuplicatedCode */

namespace KatalysisProAi\Search\Searches\ColumnSet\Column;

use Concrete\Core\Search\Column\Column;
use Concrete\Core\Search\Column\PagerColumnInterface;
use Concrete\Core\Search\ItemList\Pager\PagerProviderInterface;
use KatalysisProAi\Entity\Search;
use KatalysisProAi\SearchList;

class StartedColumn extends Column implements PagerColumnInterface
{
    public function getColumnKey()
    {
        return 's.started';
    }
    
    public function getColumnName()
    {
        return t('Started');
    }
    
    public function getColumnCallback()
    {
        return 'getDisplayStarted';
    }
    
    /**
    * @param SearchList $itemList
    * @param $mixed Search
    * @noinspection PhpDocSignatureInspection
    */
    public function filterListAtOffset(PagerProviderInterface $itemList, $mixed)
    {
        $query = $itemList->getQueryObject();
        $sort = $this->getColumnSortDirection() == 'desc' ? '<' : '>';
        $where = sprintf('s.started %s :started', $sort);
        $query->setParameter('started', $mixed->getStarted());
        $query->andWhere($where);
    }
}
