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

class LaunchPageTitleField extends AbstractField
{
    protected $requestVariables = [
        'launchPageTitle'
    ];
    
    public function getKey()
    {
        return 'launchPageTitle';
    }
    
    public function getDisplayName()
    {
        return t('Launch Page Title');
    }
    
    /**
     * @param ChatsList $list
     * @noinspection PhpDocSignatureInspection
     */
    public function filterList(ItemList $list)
    {
        if (isset($this->data['launchPageTitle']) && $this->data['launchPageTitle'] !== '') {
            $list->filterByLaunchPageTitle($this->data['launchPageTitle']);
        }
    }
    
    public function renderSearchField()
    {
        $app = Application::getFacadeApplication();
        /** @var Form $form */
        $form = $app->make(Form::class);
        return $form->text('launchPageTitle', isset($this->data['launchPageTitle']));
    }
} 