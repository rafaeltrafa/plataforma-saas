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
    // API v1 base namespace
    $routes->group('v1', ['namespace' => 'App\\Controllers\\Api\\V1'], static function (RouteCollection $routes): void {
        // Public auth route (no apiauth)
        $routes->post('auth/login', 'AuthController::login', ['filter' => 'tenantresolver']);

        // Protected routes (JWT + tenantresolver)
        $routes->get('tenants', 'TenantsController::index', ['filters' => ['apiauth', 'tenantresolver']]);
        $routes->post('tenants', 'TenantsController::create', ['filters' => ['apiauth', 'tenantresolver']]);
        $routes->get('subscriptions', 'SubscriptionsController::index', ['filters' => ['apiauth', 'tenantresolver']]);
        $routes->post('subscriptions', 'SubscriptionsController::create', ['filters' => ['apiauth', 'tenantresolver']]);
        $routes->get('plans', 'PlansController::index', ['filters' => ['apiauth', 'tenantresolver']]);
        $routes->get('payments', 'PaymentsController::index', ['filters' => ['apiauth', 'tenantresolver']]);
        $routes->post('payments', 'PaymentsController::create', ['filters' => ['apiauth', 'tenantresolver']]);
    });
});

// Endpoint público de cadastro via Google removido temporariamente
