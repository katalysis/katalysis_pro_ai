<?php

/**
 *
 * This file was build with the Entity Designer add-on.
 *
 * https://www.concrete5.org/marketplace/addons/entity-designer
 *
 */

/** @noinspection DuplicatedCode */

namespace KatalysisProAi\Search\Searches\Field;

use Concrete\Core\Search\Field\Manager as FieldManager;
use KatalysisProAi\Entity\Search;

class Manager extends FieldManager
{
    
    public function __construct()
    {
        // For now, keep this simple - we can add specific field classes later if needed
        // The basic search functionality will work with the default keyword search
    }
}
