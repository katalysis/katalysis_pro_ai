<?php

/**
 *
 * This file was build with the Entity Designer add-on.
 *
 * https://www.concrete5.org/marketplace/addons/entity-designer
 *
 */

/** @noinspection DuplicatedCode */

namespace KatalysisProAi\Search\Chats\ColumnSet;

use Concrete\Core\Support\Facade\Facade;
use Concrete\Core\Search\Column\Set;
use KatalysisProAi\Search\Chats\SearchProvider;


class ColumnSet extends Set
{
    protected $attributeClass = 'CollectionAttributeKey';
    
    public static function getCurrent()
    {
        $app = Facade::getFacadeApplication();
        /** @var $provider SearchProvider */
        $provider = $app->make(SearchProvider::class);
        $query = $provider->getSessionCurrentQuery();
        
        if ($query) {
            return $query->getColumns();
        }
        
        return $provider->getDefaultColumnSet();
    }
}
