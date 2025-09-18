<?php

/** @noinspection DuplicatedCode */

namespace KatalysisProAi\Search\Searches\Field;

use Concrete\Core\Foundation\Service\Provider as ServiceProvider;

class ManagerServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app['manager/search_field/searches'] = function ($app) {
            return $app->make(Manager::class);
        };
    }
}
