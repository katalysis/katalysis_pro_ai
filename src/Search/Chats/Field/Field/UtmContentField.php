<?php

namespace KatalysisProAi\Search\Chats\Field\Field;

use Concrete\Core\Search\Field\AbstractField;
use Concrete\Core\Search\ItemList\ItemList;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Form\Service\Form;

class UtmContentField extends AbstractField
{
    protected $requestVariables = ['utmContent'];

    public function getKey()
    {
        return 'utmContent';
    }

    public function getDisplayName()
    {
        return t('UTM Content');
    }

    public function filterList(ItemList $list)
    {
        if (isset($this->data['utmContent']) && $this->data['utmContent'] !== '') {
            $list->filterByUtmContent($this->data['utmContent']);
        }
    }

    public function renderSearchField()
    {
        $app = Application::getFacadeApplication();
        /** @var Form $form */
        $form = $app->make(Form::class);
        return $form->text('utmContent', isset($this->data['utmContent']) ? $this->data['utmContent'] : '');
    }
} 