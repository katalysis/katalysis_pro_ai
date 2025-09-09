<?php

/** @noinspection DuplicatedCode */
/** @noinspection PhpUnused */

namespace Concrete\Package\KatalysisProAi\Controller\Search;

use KatalysisProAi\Entity\Search\SavedChatsSearch;
use Concrete\Core\Search\Field\Field\KeywordsField;
use Doctrine\ORM\EntityManagerInterface;
use Concrete\Controller\Search\Standard;
use Concrete\Core\Permission\Key\Key;
use Concrete\Package\KatalysisProAi\Controller\Dialog\Chats\AdvancedSearch;

class Chats extends Standard
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
     * @return SavedCsSearch|null
     */
    protected function getSavedSearchPreset($presetID)
    {
        $em = $this->app->make(EntityManagerInterface::class);
        return $em->find(SavedChatsSearch::class, $presetID);
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
        $permissionKey = Key::getByHandle("read_katalysis_chats");
        return $permissionKey->validate();
    }
}
