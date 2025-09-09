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

namespace KatalysisProAi\Search\Actions;

use KatalysisProAi\Entity\Search\SavedActionsSearch;
use KatalysisProAi\ActionList;
use KatalysisProAi\Search\Actions\ColumnSet\DefaultSet;
use KatalysisProAi\Search\Actions\ColumnSet\Available;
use KatalysisProAi\Search\Actions\ColumnSet\ColumnSet;
use KatalysisProAi\Search\Actions\Result\Result;
use Concrete\Core\Search\AbstractSearchProvider;
use Concrete\Core\Search\Field\ManagerFactory;

class SearchProvider extends AbstractSearchProvider
{
    public function getFieldManager()
    {
        return ManagerFactory::get('actions');
    }
    
    public function getSessionNamespace()
    {
        return 'actions';
    }
    
    public function getCustomAttributeKeys()
    {
        return [];
    }
    
    public function getBaseColumnSet()
    {
        return new ColumnSet();
    }
    
    public function getAvailableColumnSet()
    {
        return new Available();
    }
    
    public function getCurrentColumnSet()
    {
        return ColumnSet::getCurrent();
    }
    
    public function createSearchResultObject($columns, $list)
    {
        return new Result($columns, $list);
    }
    
    public function getItemList()
    {
        return new ActionList();
    }
    
    public function getDefaultColumnSet()
    {
        return new DefaultSet();
    }
    
    public function getSavedSearch()
    {
        return new SavedActionsSearch();
    }
} 