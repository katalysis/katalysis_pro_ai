<?php

namespace KatalysisProAi\Search\Chats\Field\Field;

use Concrete\Core\Search\Field\AbstractField;
use Concrete\Core\Search\ItemList\ItemList;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Form\Service\Form;

class UserMessageCountField extends AbstractField
{
    protected $requestVariables = ['userMessageCount'];

    public function getKey()
    {
        return 'userMessageCount';
    }

    public function getDisplayName()
    {
        return t('User Message Count');
    }

    public function filterList(ItemList $list)
    {
        if (isset($this->data['userMessageCount']) && $this->data['userMessageCount'] !== '') {
            $list->filterByUserMessageCount($this->data['userMessageCount']);
        }
    }

    public function renderSearchField()
    {
        $app = Application::getFacadeApplication();
        /** @var Form $form */
        $form = $app->make(Form::class);
        return $form->text('userMessageCount', isset($this->data['userMessageCount']) ? $this->data['userMessageCount'] : '');
    }
} 