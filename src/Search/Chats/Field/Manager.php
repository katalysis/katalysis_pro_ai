<?php

/**
 *
 * This file was build with the Entity Designer add-on.
 *
 * https://www.concrete5.org/marketplace/addons/entity-designer
 *
 */

/** @noinspection DuplicatedCode */

namespace KatalysisProAi\Search\Chats\Field;

use Concrete\Core\Search\Field\Manager as FieldManager;
use KatalysisProAi\Entity\Chat;
use KatalysisProAi\Search\Chats\Field\Field\LocationField;
use KatalysisProAi\Search\Chats\Field\Field\LlmField;
use KatalysisProAi\Search\Chats\Field\Field\StartedField;
use KatalysisProAi\Search\Chats\Field\Field\NameField;
use KatalysisProAi\Search\Chats\Field\Field\EmailField;
use KatalysisProAi\Search\Chats\Field\Field\PhoneField;
use KatalysisProAi\Search\Chats\Field\Field\LaunchPageTitleField;
use KatalysisProAi\Search\Chats\Field\Field\UtmSourceField;
use KatalysisProAi\Search\Chats\Field\Field\LaunchPageUrlField;
use KatalysisProAi\Search\Chats\Field\Field\LaunchPageTypeField;
use KatalysisProAi\Search\Chats\Field\Field\FirstMessageField;
use KatalysisProAi\Search\Chats\Field\Field\LastMessageField;
use KatalysisProAi\Search\Chats\Field\Field\UserMessageCountField;
use KatalysisProAi\Search\Chats\Field\Field\UtmIdField;
use KatalysisProAi\Search\Chats\Field\Field\UtmMediumField;
use KatalysisProAi\Search\Chats\Field\Field\UtmCampaignField;
use KatalysisProAi\Search\Chats\Field\Field\UtmTermField;
use KatalysisProAi\Search\Chats\Field\Field\UtmContentField;

class Manager extends FieldManager
{
    
    public function __construct()
    {
        $coreProperties = [
            new LocationField(),
            new LlmField(),
            new StartedField(),
        ];
        
        $contactProperties = [
            new NameField(),
            new EmailField(),
            new PhoneField(),
        ];
        
        $pageProperties = [
            new LaunchPageTitleField(),
            new LaunchPageUrlField(),
            new LaunchPageTypeField(),
        ];
        
        $messageProperties = [
            new FirstMessageField(),
            new LastMessageField(),
            new UserMessageCountField(),
        ];
        
        $utmProperties = [
            new UtmSourceField(),
            new UtmIdField(),
            new UtmMediumField(),
            new UtmCampaignField(),
            new UtmTermField(),
            new UtmContentField(),
        ];
        
        $this->addGroup(t('Core Properties'), $coreProperties);
        $this->addGroup(t('Contact Information'), $contactProperties);
        $this->addGroup(t('Page Information'), $pageProperties);
        $this->addGroup(t('Message Content'), $messageProperties);
        $this->addGroup(t('UTM Parameters'), $utmProperties);
    }
}
