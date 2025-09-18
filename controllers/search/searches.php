<?php

/** @noinspection DuplicatedCode */
/** @noinspection PhpUnused */

namespace Concrete\Package\KatalysisProAi\Controller\Search;

use KatalysisProAi\Entity\Search\SavedSearchesSearch;
use Concrete\Core\Search\Field\Field\KeywordsField;
use Doctrine\ORM\EntityManagerInterface;
use Concrete\Controller\Search\Standard;
use Concrete\Core\Permission\Key\Key;
use Concrete\Package\KatalysisProAi\Controller\Dialog\Searches\AdvancedSearch;

class Searches extends Standard
{
    /**
     * @return \Concrete\Controller\Dialog\Search\AdvancedSearch
     */
    protected function getAdvancedSearchDialogController()
    {
        return $this->app->make(AdvancedSearch::class);
    }
    
    /**
     * @param int $presetID
     *
     * @return SavedSearchesSearch|null
     */
    protected function getSavedSearchPreset($presetID)
    {
        $em = $this->app->make(EntityManagerInterface::class);
        return $em->find(SavedSearchesSearch::class, $presetID);
    }
    
    /**
     * @return KeywordsField[]
     */
    protected function getBasicSearchFieldsFromRequest()
    {
        $fields = parent::getBasicSearchFieldsFromRequest();
        $keywords = htmlentities($this->request->get('cKeywords'), ENT_QUOTES, APP_CHARSET);
        if ($keywords) {
            $fields[] = new KeywordsField($keywords);
        }
        
        return $fields;
    }
    
    /**
     * @return bool
     */
    protected function canAccess()
    {
        $permissionKey = Key::getByHandle("read_katalysis_searches");
        if ($permissionKey) {
            return $permissionKey->validate();
        }
        
        // Fallback to basic dashboard access if searches permission doesn't exist
        $permissionKey = Key::getByHandle("access_dashboard");
        if ($permissionKey) {
            return $permissionKey->validate();
        }
        
        // Final fallback
        return true;
    }
}
