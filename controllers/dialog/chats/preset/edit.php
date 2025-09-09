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

namespace Concrete\Package\KatalysisProAi\Controller\Dialog\Chats\Preset;

use KatalysisProAi\Entity\Search\SavedChatsSearch;
use Concrete\Core\Permission\Key\Key;
use Concrete\Controller\Dialog\Search\Preset\Edit as PresetEdit;
use Doctrine\ORM\EntityManager;
use Concrete\Core\Entity\Search\SavedSearch;
use Concrete\Core\Support\Facade\Url;

class Edit extends PresetEdit
{
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
    
    public function getSavedSearchEntity()
    {
        /** @var EntityManager $em */
        $em = $this->app->make(EntityManager::class);
        
        if (is_object($em)) {
            return $em->getRepository(SavedChatsSearch::class);
        }
        
        return null;
    }
    
    public function getSavedSearchBaseURL(SavedSearch $search)
    {
        return (string) Url::to('/ccm/system/search/chats/preset', $search->getID());
    }
}
