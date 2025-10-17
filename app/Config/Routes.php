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
    $routes->get('tenant/', 'TenantController::index');
    $routes->get('apps/', 'AppsController::index');
    // Buscar planos do app (para modal de assinaturas)
    $routes->get('apps/(:num)/plans', 'AppsController::plans/$1');
    // Suporte a barra final
    $routes->get('apps/(:num)/plans/', 'AppsController::plans/$1');
    // Formulário de novo plano (carregado via AJAX dentro do modal)
    $routes->get('apps/(:num)/plans/new', 'AppsController::planForm/$1');
    // Suporte a barra final
    $routes->get('apps/(:num)/plans/new/', 'AppsController::planForm/$1');
    // Criar novo plano (POST via AJAX)
    $routes->post('apps/(:num)/plans', 'AppsController::storePlan/$1');
    // Suporte a barra final
    $routes->post('apps/(:num)/plans/', 'AppsController::storePlan/$1');

    // Formulário de edição de plano
    $routes->get('apps/(:num)/plans/(:num)/edit', 'AppsController::editPlanForm/$1/$2');
    // Suporte a barra final
    $routes->get('apps/(:num)/plans/(:num)/edit/', 'AppsController::editPlanForm/$1/$2');
    // Atualizar plano (POST via AJAX)
    $routes->post('apps/(:num)/plans/(:num)', 'AppsController::updatePlan/$1/$2');
    // Suporte a barra final
    $routes->post('apps/(:num)/plans/(:num)/', 'AppsController::updatePlan/$1/$2');

    // Alternar status do plano (desativar/ativar)
    $routes->post('apps/(:num)/plans/(:num)/deactivate', 'AppsController::deactivatePlan/$1/$2');
    $routes->get('apps/(:num)/plans/(:num)/deactivate', 'AppsController::deactivatePlan/$1/$2');
    $routes->post('apps/(:num)/plans/(:num)/activate', 'AppsController::activatePlan/$1/$2');
    $routes->get('apps/(:num)/plans/(:num)/activate', 'AppsController::activatePlan/$1/$2');

    // Criar Product & Price na Stripe e retornar Price ID
    $routes->post('apps/(:num)/stripe/price', 'AppsController::createStripePrice/$1');
    $routes->get('apps/(:num)/stripe/price', 'AppsController::createStripePrice/$1');
    // Desativar app (set is_active = 0)
    $routes->post('apps/(:num)/deactivate', 'AppsController::deactivate/$1');
    // Fallback GET (caso CSRF esteja ativo para POST e você não use token)
    $routes->get('apps/(:num)/deactivate', 'AppsController::deactivate/$1');
    // Ativar app (set is_active = 1)
    $routes->post('apps/(:num)/activate', 'AppsController::activate/$1');
    // Fallback GET
    $routes->get('apps/(:num)/activate', 'AppsController::activate/$1');

    // Novo App: carregar formulário e criar
    $routes->get('apps/new', 'AppsController::newAppForm');
    $routes->get('apps/new/', 'AppsController::newAppForm');
    $routes->post('apps', 'AppsController::storeApp');
    $routes->post('apps/', 'AppsController::storeApp');

    // Editar App: carregar formulário e atualizar
    $routes->get('apps/(:num)/edit', 'AppsController::editAppForm/$1');
    $routes->get('apps/(:num)/edit/', 'AppsController::editAppForm/$1');
    $routes->post('apps/(:num)', 'AppsController::updateApp/$1');
    $routes->post('apps/(:num)/', 'AppsController::updateApp/$1');
});

// API v1 routes
$routes->group('api', ['namespace' => 'App\\Controllers\\Api'], static function (RouteCollection $routes): void {
    // API v1 base namespace
    $routes->group('v1', ['namespace' => 'App\\Controllers\\Api\\V1'], static function (RouteCollection $routes): void {
        // Public auth route (no apiauth)
        $routes->post('auth/login', 'AuthController::login', ['filter' => 'tenantresolver']);

        // Protected routes (JWT + tenantresolver)
        $routes->patch('auth/password', 'AuthController::changePassword', ['filters' => ['apiauth', 'tenantresolver']]);
        $routes->post('auth/password', 'AuthController::changePassword', ['filters' => ['apiauth', 'tenantresolver']]);

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
