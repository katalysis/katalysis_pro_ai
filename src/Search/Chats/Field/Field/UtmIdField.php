<?php

namespace KatalysisProAi\Search\Chats\Field\Field;

use Concrete\Core\Search\Field\AbstractField;
use Concrete\Core\Search\ItemList\ItemList;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Form\Service\Form;

class UtmIdField extends AbstractField
{
    protected $requestVariables = ['utmId'];

    public function getKey()
    {
        return 'utmId';
    }

    public function getDisplayName()
    {
        return t('UTM ID');
    }

    public function filterList(ItemList $list)
    {
        if (isset($this->data['utmId']) && $this->data['utmId'] !== '') {
            $list->filterByUtmId($this->data['utmId']);
        }
    }

    public function renderSearchField()
    {
        $app = Application::getFacadeApplication();
        /** @var Form $form */
        $form = $app->make(Form::class);
        return $form->text('utmId', isset($this->data['utmId']) ? $this->data['utmId'] : '');
    }
} 