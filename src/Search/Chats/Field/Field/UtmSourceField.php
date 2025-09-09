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

class UtmSourceField extends AbstractField
{
    protected $requestVariables = [
        'utmSource'
    ];
    
    public function getKey()
    {
        return 'utmSource';
    }
    
    public function getDisplayName()
    {
        return t('UTM Source');
    }
    
    /**
     * @param ChatsList $list
     * @noinspection PhpDocSignatureInspection
     */
    public function filterList(ItemList $list)
    {
        if (isset($this->data['utmSource']) && $this->data['utmSource'] !== '') {
            $list->filterByUtmSource($this->data['utmSource']);
        }
    }
    
    public function renderSearchField()
    {
        $app = Application::getFacadeApplication();
        /** @var Form $form */
        $form = $app->make(Form::class);
        return $form->text('utmSource', isset($this->data['utmSource']));
    }
} 