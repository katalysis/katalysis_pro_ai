<?php

/** @noinspection DuplicatedCode */

namespace KatalysisProAi\Search\Searches\ColumnSet\Column;

use Concrete\Core\Search\Column\Column;
use Concrete\Core\Search\Column\PagerColumnInterface;
use Concrete\Core\Search\ItemList\Pager\PagerProviderInterface;
use KatalysisProAi\Entity\Search;
use KatalysisProAi\SearchList;

class QueryColumn extends Column implements PagerColumnInterface
{
    public function getColumnKey()
    {
        return 's.query';
    }
    
    public function getColumnName()
    {
        return t('Query');
    }
    
    public function getColumnCallback()
    {
        return 'getTruncatedQuery';
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
        $where = sprintf('s.query %s :query', $sort);
        $query->setParameter('query', $mixed->getQuery());
        $query->andWhere($where);
    }
}
