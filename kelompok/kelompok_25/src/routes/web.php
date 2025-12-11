<?php

/**
 * Web Routes
 */

// Guest routes (only accessible when not logged in)
$router->get('/', 'web/AuthController@showLogin');
$router->get('/login', 'web/AuthController@showLogin');
$router->post('/login', 'web/AuthController@login');
$router->get('/register', 'web/AuthController@showRegister');
$router->post('/register', 'web/AuthController@register');
$router->get('/forgot-password', 'web/AuthController@showForgotPassword');
$router->post('/forgot-password', 'web/AuthController@forgotPassword');

// Authenticated routes
$router->get('/dashboard', function() {
    AuthMiddleware::check();
    require_once ROOT_PATH . '/controllers/web/DashboardController.php';
    $controller = new DashboardController();
    $controller->index();
});

$router->post('/logout', 'web/AuthController@logout');

// Sidebar placeholder pages
$router->get('/materials', 'web/PageController@materials');
$router->get('/suppliers', 'web/PageController@suppliers');
$router->get('/categories', 'web/PageController@categories');
$router->get('/stock-in', 'web/PageController@stockIn');
$router->get('/stock-out', 'web/PageController@stockOut');
$router->get('/stock-adjustments', 'web/PageController@stockAdjustments');
$router->get('/reports/stock', 'web/PageController@reportsStock');
$router->get('/reports/transactions', 'web/PageController@reportsTransactions');
$router->get('/reports/low-stock', 'web/PageController@reportsLowStock');
$router->get('/roles', 'web/PageController@roles');
$router->get('/users', 'web/PageController@users');
$router->get('/profile', 'web/PageController@profile');
