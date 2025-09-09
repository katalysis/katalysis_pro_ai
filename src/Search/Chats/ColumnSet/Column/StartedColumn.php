<?php

/** @noinspection DuplicatedCode */

namespace KatalysisProAi\Search\Chats\ColumnSet\Column;

use Concrete\Core\Search\Column\Column;
use Concrete\Core\Search\Column\PagerColumnInterface;
use Concrete\Core\Search\ItemList\Pager\PagerProviderInterface;
use KatalysisProAi\Entity\Chat;
use KatalysisProAi\ChatsList;

class StartedColumn extends Column implements PagerColumnInterface
{
    public function getColumnKey()
    {
        return 'c.Started';
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
    * @param ChatsList $itemList
    * @param $mixed Chat
    * @noinspection PhpDocSignatureInspection
    */
    public function filterListAtOffset(PagerProviderInterface $itemList, $mixed)
    {
        $query = $itemList->getQueryObject();
        $sort = $this->getColumnSortDirection() == 'desc' ? '<' : '>';
        $where = sprintf('c.Started %s :Started', $sort);
        $query->setParameter('Started', $mixed->getStarted());
        $query->andWhere($where);
    }
} 