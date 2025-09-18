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

namespace KatalysisProAi\Search\Searches;

use KatalysisProAi\Entity\Search\SavedSearchesSearch;
use KatalysisProAi\SearchList;
use KatalysisProAi\Search\Searches\ColumnSet\DefaultSet;
use KatalysisProAi\Search\Searches\ColumnSet\Available;
use KatalysisProAi\Search\Searches\ColumnSet\ColumnSet;
use KatalysisProAi\Search\Searches\Result\Result;
use Concrete\Core\Search\AbstractSearchProvider;
use Concrete\Core\Search\Field\ManagerFactory;

class SearchProvider extends AbstractSearchProvider
{
    public function getFieldManager()
    {
        return ManagerFactory::get('searches');
    }
    
    public function getSessionNamespace()
    {
        return 'searches';
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
    
    public function getDefaultColumnSet()
    {
        return new DefaultSet();
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
        return new SearchList();
    }
    
    public function getDefaultSortColumn()
    {
        return 's.id';
    }
    
    public function getDefaultSortDirection()
    {
        return 'desc';
    }
    
    protected function getAttributeKeyClassName()
    {
        return null;
    }
    
    public function getSavedSearch()
    {
        return new SavedSearchesSearch();
    }
}
