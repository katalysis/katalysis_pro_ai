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
 * Base path: /ccm/system/search/searches
 * Namespace: Concrete\Package\KatalysisProAi\Controller\Search\Searches
 */

$router->all('/basic', 'Searches::searchBasic');
$router->all('/current', 'Searches::searchCurrent');
$router->all('/preset/{presetID}', 'Searches::searchPreset');
$router->all('/clear', 'Searches::clearSearch');
