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

class StartedField extends AbstractField
{
    protected $requestVariables = [
        'started'
    ];
    
    public function getKey()
    {
        return 'started';
    }
    
    public function getDisplayName()
    {
        return t('Started Date');
    }
    
    /**
     * @param ChatsList $list
     * @noinspection PhpDocSignatureInspection
     */
    public function filterList(ItemList $list)
    {
        if (isset($this->data['started']) && $this->data['started'] !== '') {
            $list->filterByStarted($this->data['started']);
        }
    }
    
    public function renderSearchField()
    {
        $app = Application::getFacadeApplication();
        /** @var Form $form */
        $form = $app->make(Form::class);
        return $form->text('started', isset($this->data['started']));
    }
} 