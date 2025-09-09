<?php

namespace KatalysisProAi\Search\Chats\Field\Field;

use Concrete\Core\Search\Field\AbstractField;
use Concrete\Core\Search\ItemList\ItemList;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Form\Service\Form;

class UtmCampaignField extends AbstractField
{
    protected $requestVariables = ['utmCampaign'];

    public function getKey()
    {
        return 'utmCampaign';
    }

    public function getDisplayName()
    {
        return t('UTM Campaign');
    }

    public function filterList(ItemList $list)
    {
        if (isset($this->data['utmCampaign']) && $this->data['utmCampaign'] !== '') {
            $list->filterByUtmCampaign($this->data['utmCampaign']);
        }
    }

    public function renderSearchField()
    {
        $app = Application::getFacadeApplication();
        /** @var Form $form */
        $form = $app->make(Form::class);
        return $form->text('utmCampaign', isset($this->data['utmCampaign']) ? $this->data['utmCampaign'] : '');
    }
} 