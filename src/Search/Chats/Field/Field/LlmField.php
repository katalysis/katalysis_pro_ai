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

class LlmField extends AbstractField
{
    protected $requestVariables = [
        'llm'
    ];
    
    public function getKey()
    {
        return 'llm';
    }
    
    public function getDisplayName()
    {
        return t('LLM');
    }
    
    /**
     * @param ChatsList $list
     * @noinspection PhpDocSignatureInspection
     */
    public function filterList(ItemList $list)
    {
        if (isset($this->data['llm']) && $this->data['llm'] !== '') {
            $list->filterByLlm($this->data['llm']);
        }
    }
    
    public function renderSearchField()
    {
        $app = Application::getFacadeApplication();
        /** @var Form $form */
        $form = $app->make(Form::class);
        return $form->text('llm', isset($this->data['llm']));
    }
}
