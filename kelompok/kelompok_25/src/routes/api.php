<?php

/**
 * API Routes
 */

// Auth API routes
$router->post('/api/auth/login', 'api/AuthApiController@login');
$router->post('/api/auth/register', 'api/AuthApiController@register');
$router->post('/api/auth/logout', 'api/AuthApiController@logout');
$router->get('/api/auth/me', 'api/AuthApiController@me');
$router->get('/api/auth/check', 'api/AuthApiController@check');

// Supplier API routes
$router->get('/api/suppliers', 'api/SupplierApiController@index');
$router->get('/api/suppliers/search', 'api/SupplierApiController@search');
$router->get('/api/suppliers/{id}', 'api/SupplierApiController@show');
$router->post('/api/suppliers', 'api/SupplierApiController@store');
$router->post('/api/suppliers/{id}', 'api/SupplierApiController@update');
$router->post('/api/suppliers/{id}/delete', 'api/SupplierApiController@destroy');

// Category API routes
$router->get('/api/categories', 'api/CategoryApiController@index');
$router->get('/api/categories/search', 'api/CategoryApiController@search');
$router->get('/api/categories/{id}', 'api/CategoryApiController@show');
$router->post('/api/categories', 'api/CategoryApiController@store');
$router->post('/api/categories/{id}', 'api/CategoryApiController@update');
$router->post('/api/categories/{id}/delete', 'api/CategoryApiController@destroy');

// Material API routes
$router->get('/api/materials', 'api/MaterialApiController@index');
$router->get('/api/materials/search', 'api/MaterialApiController@search');
$router->get('/api/materials/low-stock', 'api/MaterialApiController@lowStock');
$router->get('/api/materials/out-of-stock', 'api/MaterialApiController@outOfStock');
$router->get('/api/materials/stats', 'api/MaterialApiController@stats');
$router->get('/api/materials/category/{categoryId}', 'api/MaterialApiController@byCategory');
$router->get('/api/materials/supplier/{supplierId}', 'api/MaterialApiController@bySupplier');
$router->get('/api/materials/{id}', 'api/MaterialApiController@show');
$router->post('/api/materials', 'api/MaterialApiController@store');
$router->post('/api/materials/{id}', 'api/MaterialApiController@update');
$router->post('/api/materials/{id}/delete', 'api/MaterialApiController@destroy');

// Material Images API routes
$router->get('/api/materials/{materialId}/images', 'api/MaterialImageApiController@index');
$router->post('/api/materials/{materialId}/images', 'api/MaterialImageApiController@upload');
$router->post('/api/materials/{materialId}/images/{id}/primary', 'api/MaterialImageApiController@setPrimary');
$router->post('/api/materials/{materialId}/images/{id}/delete', 'api/MaterialImageApiController@delete');

// Stock In API routes
$router->get('/api/stock-in', 'api/StockInApiController@index');
$router->get('/api/stock-in/today', 'api/StockInApiController@today');
$router->get('/api/stock-in/stats', 'api/StockInApiController@stats');
$router->get('/api/stock-in/top-materials', 'api/StockInApiController@topMaterials');
$router->get('/api/stock-in/top-suppliers', 'api/StockInApiController@topSuppliers');
$router->get('/api/stock-in/monthly/{year}', 'api/StockInApiController@monthly');
$router->get('/api/stock-in/{id}', 'api/StockInApiController@show');
$router->post('/api/stock-in', 'api/StockInApiController@store');
$router->post('/api/stock-in/{id}', 'api/StockInApiController@update');
$router->post('/api/stock-in/{id}/delete', 'api/StockInApiController@destroy');

// Stock Out API routes (placeholder)
$router->get('/api/stock-out', function() {
    AuthMiddleware::check();
    Response::success('Stock out endpoint', []);
});

$router->post('/api/stock-out', function() {
    AuthMiddleware::check();
    RoleMiddleware::staff();
    Response::success('Stock out endpoint', []);
});

// Reports API routes
$router->get('/api/reports/stock', function() {
    AuthMiddleware::check();
    Response::success('Stock report endpoint', []);
});

$router->get('/api/transactions/trend', function() {
    AuthMiddleware::check();
    require_once ROOT_PATH . '/models/Transaction.php';
    require_once ROOT_PATH . '/controllers/web/TransactionController.php';
    $controller = new TransactionController();
    $controller->getTrendData();
});

$router->get('/api/reports/transactions', function() {
    AuthMiddleware::check();
    Response::success('Transactions report endpoint', []);
});

$router->get('/api/reports/low-stock', function() {
    AuthMiddleware::check();
    Response::success('Low stock report endpoint', []);
});
