<?php

namespace KatalysisProAi\Search\Chats\Field\Field;

use Concrete\Core\Search\Field\AbstractField;
use Concrete\Core\Search\ItemList\ItemList;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Form\Service\Form;

class LastMessageField extends AbstractField
{
    protected $requestVariables = ['lastMessage'];

    public function getKey()
    {
        return 'lastMessage';
    }

    public function getDisplayName()
    {
        return t('Last Message');
    }

    public function filterList(ItemList $list)
    {
        if (isset($this->data['lastMessage']) && $this->data['lastMessage'] !== '') {
            $list->filterByLastMessage($this->data['lastMessage']);
        }
    }

    public function renderSearchField()
    {
        $app = Application::getFacadeApplication();
        /** @var Form $form */
        $form = $app->make(Form::class);
        return $form->text('lastMessage', isset($this->data['lastMessage']) ? $this->data['lastMessage'] : '');
    }
} 