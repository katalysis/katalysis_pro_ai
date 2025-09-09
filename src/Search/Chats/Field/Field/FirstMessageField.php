<?php

namespace KatalysisProAi\Search\Chats\Field\Field;

use Concrete\Core\Search\Field\AbstractField;
use Concrete\Core\Search\ItemList\ItemList;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Form\Service\Form;

class FirstMessageField extends AbstractField
{
    protected $requestVariables = ['firstMessage'];

    public function getKey()
    {
        return 'firstMessage';
    }

    public function getDisplayName()
    {
        return t('First Message');
    }

    public function filterList(ItemList $list)
    {
        if (isset($this->data['firstMessage']) && $this->data['firstMessage'] !== '') {
            $list->filterByFirstMessage($this->data['firstMessage']);
        }
    }

    public function renderSearchField()
    {
        $app = Application::getFacadeApplication();
        /** @var Form $form */
        $form = $app->make(Form::class);
        return $form->text('firstMessage', isset($this->data['firstMessage']) ? $this->data['firstMessage'] : '');
    }
} 