<?php

namespace KatalysisProAi\Search\Actions\ColumnSet\Column;

use KatalysisProAi\Entity\Action;
use Concrete\Core\Search\Column\Column;
use Concrete\Core\Search\Column\PagerColumnInterface;
use Concrete\Core\Search\ItemList\Pager\PagerProviderInterface;

class ActionTypeColumn extends Column implements PagerColumnInterface
{
    public function getColumnKey()
    {
        return 'actionType';
    }

    public function getColumnName()
    {
        return t('Type');
    }

    public function getColumnCallback()
    {
        return [$this, 'getValue'];
    }

    public function getValue(Action $action)
    {
        $actionType = $action->getActionType() ?: 'basic';
        
        switch ($actionType) {
            case 'form':
                return '<span class="badge bg-info"><i class="fas fa-i-cursor me-2"></i> Step Form</span>';
            case 'simple_form':
                return '<span class="badge bg-success"><i class="fas fa-list me-2"></i> Simple Form</span>';
            case 'dynamic_form':
                return '<span class="badge bg-primary"><i class="fas fa-magic me-2"></i> AI Form</span>';
            case 'basic':
            default:
                return '<span class="badge bg-secondary"><i class="fas fa-comment me-2"></i> Basic</span>';
        }
    }

    public function filterListAtOffset(PagerProviderInterface $itemList, $mixed)
    {
        $query = $itemList->getQueryObject();
        $sort = $this->getColumnSortDirection() == 'desc' ? '<' : '>';
        $where = sprintf('actionType %s :actionType', $sort);
        $query->setParameter('actionType', $mixed->getActionType());
        $query->andWhere($where);
    }
}