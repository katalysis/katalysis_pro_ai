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
use Concrete\Core\Routing\Router;
use KatalysisProAi\Search\Searches\Field\ManagerServiceProvider;
use KatalysisProAi\Routing\SearchesRouteList;

class SearchesServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->initializeSearchProvider();
        $this->initializeRoutes();
    }
    
    public function initializeSearchProvider()
    {
        /** @var ManagerServiceProvider $searchProvider */
        $searchProvider = $this->app->make(ManagerServiceProvider::class);
        $searchProvider->register();
    }
    
    public function initializeRoutes()
    {
        /** @var Router $router */
        $router = $this->app->make(Router::class);
        /** @var SearchesRouteList $routeList */
        $routeList = $this->app->make(SearchesRouteList::class);
        $routeList->loadRoutes($router);
    }
}
