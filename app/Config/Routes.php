<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// Admin routes
$routes->group('admin', ['namespace' => 'App\\Controllers\\Admin'], static function (RouteCollection $routes): void {
    $routes->get('', 'DashboardController::index');
    $routes->get('dashboard', 'DashboardController::index');
    // Suporte a barra final
    $routes->get('dashboard/', 'DashboardController::index');
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
        $routes->get('plans', 'PlansController::index', ['filters' => ['apiauth', 'tenantresolver']]);
        $routes->get('subscriptions', 'SubscriptionsController::index', ['filters' => ['apiauth', 'tenantresolver']]);
        $routes->post('subscriptions', 'SubscriptionsController::create', ['filters' => ['apiauth', 'tenantresolver']]);
        // Suporte a barra final (evita 404 em /subscriptions/)
        $routes->post('subscriptions/', 'SubscriptionsController::create', ['filters' => ['apiauth', 'tenantresolver']]);
        // Cancelamento imediato da assinatura
        $routes->patch('subscriptions/(:num)/cancel', 'SubscriptionsController::cancel/$1', ['filters' => ['apiauth', 'tenantresolver']]);
        // Suporte a barra final (evita 404 em /subscriptions/{id}/cancel/)
        $routes->patch('subscriptions/(:num)/cancel/', 'SubscriptionsController::cancel/$1', ['filters' => ['apiauth', 'tenantresolver']]);
        $routes->get('payments', 'PaymentsController::index', ['filters' => ['apiauth', 'tenantresolver']]);
        $routes->post('payments', 'PaymentsController::create', ['filters' => ['apiauth', 'tenantresolver']]);
        // Stripe Checkout: criar sessão (protegido)
        $routes->post('checkout/session', 'CheckoutController::createSession', ['filters' => ['apiauth', 'tenantresolver']]);
    });

    // Stripe Webhook (no filters)
    $routes->get('v1/webhooks/ping', 'WebhooksController::ping');
    $routes->post('v1/webhooks/stripe', 'WebhooksController::stripe');
});

// Endpoint público de cadastro via Google removido temporariamente
