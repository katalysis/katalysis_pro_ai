<?php

/**
 *
 * This file was build with the Entity Designer add-on.
 *
 * https://www.concrete5.org/marketplace/addons/entity-designer
 *
 */

/** @noinspection DuplicatedCode */

namespace KatalysisProAi\Search\Chats\Field\Field;

use Concrete\Core\Form\Service\Form;
use Concrete\Core\Search\Field\AbstractField;
use Concrete\Core\Search\ItemList\ItemList;
use Concrete\Core\Support\Facade\Application;
use KatalysisProAi\ChatsList;

class PhoneField extends AbstractField
{
    protected $requestVariables = [
        'phone'
    ];
    
    public function getKey()
    {
        return 'phone';
    }
    
    public function getDisplayName()
    {
        return t('Phone');
    }
    
    /**
     * @param ChatsList $list
     * @noinspection PhpDocSignatureInspection
     */
    public function filterList(ItemList $list)
    {
        if (isset($this->data['phone']) && $this->data['phone'] !== '') {
            $list->filterByPhone($this->data['phone']);
        }
    }
    
    public function renderSearchField()
    {
        $app = Application::getFacadeApplication();
        /** @var Form $form */
        $form = $app->make(Form::class);
        return $form->text('phone', isset($this->data['phone']));
    }
} 