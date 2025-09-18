<?php

/** @noinspection DuplicatedCode */

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @var Concrete\Core\Application\Application $app
 * @var Concrete\Core\Routing\Router $router
 */

/*
 * Base path: /ccm/system/dialogs/searches
 * Namespace: Concrete\Package\KatalysisProAi\Controller\Dialog\Searches
 */

$router->all('/bulk/delete', 'Bulk\Delete::view');
$router->all('/bulk/delete/submit', 'Bulk\Delete::submit');
$router->all('/advanced_search', 'AdvancedSearch::view');
$router->all('/advanced_search/preset/{presetID}', 'AdvancedSearch::preset');
$router->all('/advanced_search/preset/edit', 'AdvancedSearch::edit_preset');
$router->all('/advanced_search/preset/delete', 'AdvancedSearch::delete_preset');
$router->all('/advanced_search/clear', 'AdvancedSearch::clear');
$router->all('/advanced_search/submit', 'AdvancedSearch::submit');
