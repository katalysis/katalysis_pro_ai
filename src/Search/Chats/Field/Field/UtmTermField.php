<?php

namespace KatalysisProAi\Search\Chats\Field\Field;

use Concrete\Core\Search\Field\AbstractField;
use Concrete\Core\Search\ItemList\ItemList;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Form\Service\Form;

class UtmTermField extends AbstractField
{
    protected $requestVariables = ['utmTerm'];

    public function getKey()
    {
        return 'utmTerm';
    }

    public function getDisplayName()
    {
        return t('UTM Term');
    }

    public function filterList(ItemList $list)
    {
        if (isset($this->data['utmTerm']) && $this->data['utmTerm'] !== '') {
            $list->filterByUtmTerm($this->data['utmTerm']);
        }
    }

    public function renderSearchField()
    {
        $app = Application::getFacadeApplication();
        /** @var Form $form */
        $form = $app->make(Form::class);
        return $form->text('utmTerm', isset($this->data['utmTerm']) ? $this->data['utmTerm'] : '');
    }
} 