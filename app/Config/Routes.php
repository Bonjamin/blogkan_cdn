<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('test', 'Home::test');
$routes->get('tool', 'Home::tool');
$routes->get('url/(.+)', 'Url::index/$1');
