<?php

namespace KatalysisProAi\Search\Chats\Field\Field;

use Concrete\Core\Search\Field\AbstractField;
use Concrete\Core\Search\ItemList\ItemList;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Form\Service\Form;

class LaunchPageUrlField extends AbstractField
{
    protected $requestVariables = ['launchPageUrl'];

    public function getKey()
    {
        return 'launchPageUrl';
    }

    public function getDisplayName()
    {
        return t('Launch Page URL');
    }

    public function filterList(ItemList $list)
    {
        if (isset($this->data['launchPageUrl']) && $this->data['launchPageUrl'] !== '') {
            $list->filterByLaunchPageUrl($this->data['launchPageUrl']);
        }
    }

    public function renderSearchField()
    {
        $app = Application::getFacadeApplication();
        /** @var Form $form */
        $form = $app->make(Form::class);
        return $form->text('launchPageUrl', isset($this->data['launchPageUrl']) ? $this->data['launchPageUrl'] : '');
    }
} 