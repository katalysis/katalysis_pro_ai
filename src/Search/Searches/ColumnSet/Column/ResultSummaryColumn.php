<?php

/** @noinspection DuplicatedCode */

namespace KatalysisProAi\Search\Searches\ColumnSet\Column;

use Concrete\Core\Search\Column\Column;
use Concrete\Core\Search\Column\PagerColumnInterface;
use Concrete\Core\Search\ItemList\Pager\PagerProviderInterface;
use KatalysisProAi\Entity\Search;
use KatalysisProAi\SearchList;

class ResultSummaryColumn extends Column implements PagerColumnInterface
{
    public function getColumnKey()
    {
        return 's.resultSummary';
    }
    
    public function getColumnName()
    {
        return t('Result Summary');
    }
    
    public function getColumnCallback()
    {
        return 'getTruncatedResultSummary';
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
        $where = sprintf('s.resultSummary %s :resultSummary', $sort);
        $query->setParameter('resultSummary', $mixed->getResultSummary());
        $query->andWhere($where);
    }
}
