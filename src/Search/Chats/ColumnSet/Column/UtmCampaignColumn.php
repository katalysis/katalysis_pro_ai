<?php

namespace KatalysisProAi\Search\Chats\ColumnSet\Column;

use Concrete\Core\Search\Column\Column;
use Concrete\Core\Search\Column\PagerColumnInterface;
use Concrete\Core\Search\ItemList\Pager\PagerProviderInterface;

class UtmCampaignColumn extends Column implements PagerColumnInterface
{
    public function getColumnKey()
    {
        return 'c.utmCampaign';
    }

    public function getColumnName()
    {
        return t('UTM Campaign');
    }

    public function getColumnCallback()
    {
        return 'getUtmCampaign';
    }

    public function filterListAtOffset(PagerProviderInterface $itemList, $mixed)
    {
        $query = $itemList->getQueryObject();
        $sort = $this->getColumnSortDirection() == 'desc' ? '<' : '>';
        $where = sprintf('c.utmCampaign %s :utmCampaign', $sort);
        $query->setParameter('utmCampaign', $mixed->getUtmCampaign());
        $query->andWhere($where);
    }
} 