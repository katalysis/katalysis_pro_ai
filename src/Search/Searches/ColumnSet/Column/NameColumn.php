<?php

/**
 *
 * This file was build with the Entity Designer add-on.
 *
 * https://www.concrete5.org/marketplace/addons/entity-designer
 *
 */

/** @noinspection DuplicatedCode */

namespace KatalysisProAi\Search\Searches\ColumnSet\Column;

use Concrete\Core\Search\Column\Column;
use Concrete\Core\Search\Column\PagerColumnInterface;
use Concrete\Core\Search\ItemList\Pager\PagerProviderInterface;
use KatalysisProAi\Entity\Search;
use KatalysisProAi\SearchList;

class NameColumn extends Column implements PagerColumnInterface
{
    public function getColumnKey()
    {
        return 's.Name';
    }
    
    public function getColumnName()
    {
        return t('Name');
    }
    
    public function getColumnCallback()
    {
        return 'getName';
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
        $where = sprintf('s.Name %s :name', $sort);
        $query->setParameter('name', $mixed->getName());
        $query->andWhere($where);
    }
}
