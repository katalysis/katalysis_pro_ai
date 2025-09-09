<?php

/** @noinspection DuplicatedCode */

namespace KatalysisProAi\Search\Chats\Field;

use Concrete\Core\Foundation\Service\Provider as ServiceProvider;

class ManagerServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app['manager/search_field/chats'] = function ($app) {
            return $app->make(Manager::class);
        };
    }
}
