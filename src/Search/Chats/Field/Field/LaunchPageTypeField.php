<?php

namespace KatalysisProAi\Search\Chats\Field\Field;

use Concrete\Core\Search\Field\AbstractField;
use Concrete\Core\Search\ItemList\ItemList;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Form\Service\Form;

class LaunchPageTypeField extends AbstractField
{
    protected $requestVariables = ['launchPageType'];

    public function getKey()
    {
        return 'launchPageType';
    }

    public function getDisplayName()
    {
        return t('Launch Page Type');
    }

    public function filterList(ItemList $list)
    {
        if (isset($this->data['launchPageType']) && $this->data['launchPageType'] !== '') {
            $list->filterByLaunchPageType($this->data['launchPageType']);
        }
    }

    public function renderSearchField()
    {
        $app = Application::getFacadeApplication();
        /** @var Form $form */
        $form = $app->make(Form::class);
        return $form->text('launchPageType', isset($this->data['launchPageType']) ? $this->data['launchPageType'] : '');
    }
} 