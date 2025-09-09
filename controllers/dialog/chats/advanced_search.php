<?php

/** @noinspection DuplicatedCode */
/** @noinspection PhpUnused */

namespace Concrete\Package\KatalysisProAi\Controller\Dialog\Chats;


use Concrete\Controller\Dialog\Search\AdvancedSearch as AdvancedSearchController;
use Concrete\Core\Support\Facade\Url;
use Concrete\Core\Search\Field\ManagerFactory;
use Concrete\Core\Permission\Key\Key;
use Concrete\Core\Entity\Search\SavedSearch;
use Doctrine\ORM\EntityManager;
use KatalysisProAi\Entity\Search\SavedChatsSearch;
use KatalysisProAi\Search\Chats\SearchProvider;

class AdvancedSearch extends AdvancedSearchController
{
    protected $supportsSavedSearch = true;
    
    protected function canAccess()
    {
        $permissionKey = Key::getByHandle("read_katalysis_chats");
        return $permissionKey->validate();
    }
    
    public function on_before_render()
    {
        parent::on_before_render();
        
        // use core views (remove package handle)
        $viewObject = $this->getViewObject();
        $viewObject->setInnerContentFile(null);
        $viewObject->setPackageHandle(null);
        $viewObject->setupRender();
    }
    
    public function getSearchProvider()
    {
        return $this->app->make(SearchProvider::class);
    }
    
    public function getSavedSearchEntity()
    {
        $em = $this->app->make(EntityManager::class);
        if (is_object($em)) {
            return $em->getRepository(SavedChatsSearch::class);
        }
        
        return null;
    }
    
    public function getFieldManager()
    {
        return ManagerFactory::get('chats');
    }
    
    public function getCurrentSearchBaseURL()
    {
        return Url::to('/ccm/system/search/chats/current');
    }
    
    public function getSearchPresets()
    {
        $em = $this->app->make(EntityManager::class);
        if (is_object($em)) {
            return $em->getRepository(SavedChatsSearch::class)->findAll();
        }
    }
    
    public function getSubmitMethod()
    {
        return 'get';
    }
    
    public function getSubmitAction()
    {
        return $this->app->make('url')->to('/dashboard/katalysis_pro_ai/chats', 'advanced_search');
    }
    
    public function getSavedSearchBaseURL(SavedSearch $search)
    {
        return $this->app->make('url')->to('/dashboard/katalysis_pro_ai/chats', 'preset', $search->getID());
    }
    
    public function getBasicSearchBaseURL()
    {
        return Url::to('/ccm/system/search/chats/basic');
    }
    
    public function getSavedSearchDeleteURL(SavedSearch $search)
    {
        return (string) Url::to('/ccm/system/dialogs/chats/advanced_search/preset/delete?presetID=' . $search->getID());
    }
    
    public function getSavedSearchEditURL(SavedSearch $search)
    {
        return (string) Url::to('/ccm/system/dialogs/chats/advanced_search/preset/edit?presetID=' . $search->getID());
    }
}
