<?php

/**
 *
 * This file was build with the Entity Designer add-on.
 *
 * https://www.concrete5.org/marketplace/addons/entity-designer
 *
 */

/** @noinspection DuplicatedCode */

namespace KatalysisProAi\Routing;

use Concrete\Core\Routing\RouteListInterface;
use Concrete\Core\Routing\Router;

class SearchesRouteList implements RouteListInterface
{
    public function loadRoutes(Router $router)
    {
        $router->buildGroup()->setNamespace('Concrete\Package\KatalysisProAi\Controller\Dialog\Searches')
            ->setPrefix('/ccm/system/dialogs/searches')
            ->routes('dialogs/searches.php', 'katalysis_pro_ai');
    
        $router->buildGroup()->setNamespace('Concrete\Package\KatalysisProAi\Controller\Search')
            ->setPrefix('/ccm/system/search/searches')
            ->routes('search/searches.php', 'katalysis_pro_ai');
    }
}
