<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// Admin routes
$routes->group('admin', ['namespace' => 'App\\Controllers\\Admin'], static function (RouteCollection $routes): void {
    $routes->get('', 'DashboardController::index');
    $routes->get('rafael', 'DashboardController::rafael');
    $routes->get('tenants', 'TenantsController::index');
});

// API v1 routes
$routes->group('api', ['namespace' => 'App\\Controllers\\Api'], static function (RouteCollection $routes): void {
    $routes->group('v1', [
        'namespace' => 'App\\Controllers\\Api\\V1',
        'filter'    => 'apiauth,tenantresolver',
    ], static function (RouteCollection $routes): void {
        $routes->get('tenants', 'TenantsController::index');
        $routes->get('subscriptions', 'SubscriptionsController::index');
        $routes->get('payments', 'PaymentsController::index');
    });
});
