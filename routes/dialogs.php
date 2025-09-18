<?php

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @var Concrete\Core\Application\Application $app
 * @var Concrete\Core\Routing\Router $router
 */

// Register chats dialog routes
$chatsGroup = $router->buildGroup();
$chatsGroup->setNamespace('Concrete\Package\KatalysisProAi\Controller\Dialog\Chats');
$chatsGroup->setPrefix('/chats');
$chatsGroup->routes('dialogs/chats');

// Register searches dialog routes
$searchesGroup = $router->buildGroup();
$searchesGroup->setNamespace('Concrete\Package\KatalysisProAi\Controller\Dialog\Searches');
$searchesGroup->setPrefix('/searches');
$searchesGroup->routes('dialogs/searches'); 