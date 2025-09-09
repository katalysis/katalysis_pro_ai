<?php

/**
 *
 * This file was build with the Entity Designer add-on.
 *
 * https://www.concrete5.org/marketplace/addons/entity-designer
 *
 */

/** @noinspection DuplicatedCode */
/** @noinspection PhpUnused */

namespace KatalysisProAi\Search\Actions\Field;

use Concrete\Core\Search\Field\Manager as CoreFieldManager;

class Manager extends CoreFieldManager
{
    public function __construct()
    {
        $this->addGroup(t('Core Properties'), [
            'name',
            'icon', 
            'action_type',
            'trigger_instruction',
            'response_instruction',
            'created_date',
            'created_by'
        ])->setDefaultToKeywords(false);
    }
}