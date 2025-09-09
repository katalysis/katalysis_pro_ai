<?php

/**
 *
 * This file was build with the Entity Designer add-on.
 *
 * https://www.concrete5.org/marketplace/addons/entity-designer
 *
 */

/** @noinspection DuplicatedCode */

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @var \Concrete\Core\Routing\Router $router
 * Base path: /ccm/system/search/chats
 * Namespace: Concrete\Package\KatalysisAiChatBot\Controller\Search\Chats
 */

$router->all('/basic', 'Chats::searchBasic');
$router->all('/current', 'Chats::searchCurrent');
$router->all('/preset/{presetID}', 'Chats::searchPreset');
$router->all('/clear', 'Chats::clearSearch');
