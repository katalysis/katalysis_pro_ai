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

namespace KatalysisProAi;

use Concrete\Core\Foundation\Service\Provider as ServiceProvider;
use KatalysisProAi\Search\Actions\Field\ManagerServiceProvider;

class ActionsServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->initializeSearchProvider();
    }
    
    public function initializeSearchProvider()
    {
        /** @var ManagerServiceProvider $searchProvider */
        $searchProvider = $this->app->make(ManagerServiceProvider::class);
        $searchProvider->register();
    }
}