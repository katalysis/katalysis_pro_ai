<?php

namespace KatalysisProAi\Search\Chats\Field\Field;

use Concrete\Core\Search\Field\AbstractField;
use Concrete\Core\Search\ItemList\ItemList;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Form\Service\Form;

class UtmMediumField extends AbstractField
{
    protected $requestVariables = ['utmMedium'];

    public function getKey()
    {
        return 'utmMedium';
    }

    public function getDisplayName()
    {
        return t('UTM Medium');
    }

    public function filterList(ItemList $list)
    {
        if (isset($this->data['utmMedium']) && $this->data['utmMedium'] !== '') {
            $list->filterByUtmMedium($this->data['utmMedium']);
        }
    }

    public function renderSearchField()
    {
        $app = Application::getFacadeApplication();
        /** @var Form $form */
        $form = $app->make(Form::class);
        return $form->text('utmMedium', isset($this->data['utmMedium']) ? $this->data['utmMedium'] : '');
    }
} 