<?php

/**
 *
 * This file was build with the Entity Designer add-on.
 *
 * https://www.concrete5.org/marketplace/addons/entity-designer
 *
 */

/** @noinspection DuplicatedCode */

namespace Concrete\Package\KatalysisProAi\Controller\Dialog\Chats\Preset;

use KatalysisProAi\Entity\Search\SavedChatsSearch;
use Concrete\Core\Permission\Key\Key;
use Concrete\Controller\Dialog\Search\Preset\Delete as PresetDelete;
use Doctrine\ORM\EntityManager;

class Delete extends PresetDelete
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
}
