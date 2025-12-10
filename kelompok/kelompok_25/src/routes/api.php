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
$router->get('/api/materials/{id}/images', 'api/MaterialImageApiController@index');
$router->post('/api/materials/{id}/images', 'api/MaterialImageApiController@upload');
$router->post('/api/materials/images/{id}/set-primary', 'api/MaterialImageApiController@setPrimary');
$router->post('/api/materials/images/{id}/delete', 'api/MaterialImageApiController@destroy');

// Stock In API routes
$router->get('/api/stock-in', 'api/StockInApiController@index');
$router->get('/api/stock-in/today', 'api/StockInApiController@today');
$router->get('/api/stock-in/stats', 'api/StockInApiController@stats');
$router->get('/api/stock-in/top-materials', 'api/StockInApiController@topMaterials');
$router->get('/api/stock-in/top-suppliers', 'api/StockInApiController@topSuppliers');
$router->get('/api/stock-in/monthly/{year}', 'api/StockInApiController@monthly');
$router->get('/api/stock-in/{id}', 'api/StockInApiController@show');
$router->post('/api/stock-in', 'api/StockInApiController@store');
$router->put('/api/stock-in/{id}', 'api/StockInApiController@update');
$router->delete('/api/stock-in/{id}', 'api/StockInApiController@destroy');

// Stock Adjustment API routes
$router->get('/api/stock-adjustments', 'api/StockAdjustmentApiController@index');
$router->get('/api/stock-adjustments/stats', 'api/StockAdjustmentApiController@stats');
$router->get('/api/stock-adjustments/report', 'api/StockAdjustmentApiController@report');
$router->get('/api/stock-adjustments/material/{id}', 'api/StockAdjustmentApiController@material');
$router->get('/api/stock-adjustments/reason/{reason}', 'api/StockAdjustmentApiController@reason');
$router->get('/api/stock-adjustments/{id}', 'api/StockAdjustmentApiController@show');
$router->post('/api/stock-adjustments', 'api/StockAdjustmentApiController@store');
$router->delete('/api/stock-adjustments/{id}', 'api/StockAdjustmentApiController@destroy');

// Stock Out API routes
$router->get('/api/stock-out', 'api/StockOutApiController@index');
$router->get('/api/stock-out/stats', 'api/StockOutApiController@stats');
$router->get('/api/stock-out/report', 'api/StockOutApiController@report');
$router->get('/api/stock-out/material/{id}', 'api/StockOutApiController@material');
$router->get('/api/stock-out/usage/{type}', 'api/StockOutApiController@usage');
$router->get('/api/stock-out/{id}', 'api/StockOutApiController@show');
$router->post('/api/stock-out', 'api/StockOutApiController@store');
$router->delete('/api/stock-out/{id}', 'api/StockOutApiController@destroy');

// Reports API routes
$router->get('/api/reports/inventory', function() {
    AuthMiddleware::check();
    require_once ROOT_PATH . '/controllers/api/ReportsApiController.php';
    $controller = new ReportsApiController();
    $controller->inventory();
});

$router->get('/api/reports/transactions', function() {
    AuthMiddleware::check();
    require_once ROOT_PATH . '/controllers/api/ReportsApiController.php';
    $controller = new ReportsApiController();
    $controller->transactions();
});

$router->get('/api/reports/low-stock', function() {
    AuthMiddleware::check();
    require_once ROOT_PATH . '/controllers/api/ReportsApiController.php';
    $controller = new ReportsApiController();
    $controller->lowStock();
});

$router->get('/api/reports/material-trend/{id}', function($id) {
    AuthMiddleware::check();
    require_once ROOT_PATH . '/controllers/api/ReportsApiController.php';
    $controller = new ReportsApiController();
    $controller->materialTrend($id);
});

$router->get('/api/reports/category-distribution', function() {
    AuthMiddleware::check();
    require_once ROOT_PATH . '/controllers/api/ReportsApiController.php';
    $controller = new ReportsApiController();
    $controller->categoryDistribution();
});

$router->get('/api/reports/supplier-performance', function() {
    AuthMiddleware::check();
    require_once ROOT_PATH . '/controllers/api/ReportsApiController.php';
    $controller = new ReportsApiController();
    $controller->supplierPerformance();
});

$router->get('/api/reports/stock-movement/{id}', function($id) {
    AuthMiddleware::check();
    require_once ROOT_PATH . '/controllers/api/ReportsApiController.php';
    $controller = new ReportsApiController();
    $controller->stockMovement($id);
});

$router->get('/api/reports/top-materials', function() {
    AuthMiddleware::check();
    require_once ROOT_PATH . '/controllers/api/ReportsApiController.php';
    $controller = new ReportsApiController();
    $controller->topMaterials();
});

$router->get('/api/reports/stock-value-by-category', function() {
    AuthMiddleware::check();
    require_once ROOT_PATH . '/controllers/api/ReportsApiController.php';
    $controller = new ReportsApiController();
    $controller->stockValueByCategory();
});

// Activity Logs API routes
$router->get('/api/activity-logs', function() {
    AuthMiddleware::check();
    require_once ROOT_PATH . '/controllers/api/ActivityLogsApiController.php';
    $controller = new ActivityLogsApiController();
    $controller->index();
});

$router->get('/api/activity-logs/user/{id}', function($id) {
    AuthMiddleware::check();
    require_once ROOT_PATH . '/controllers/api/ActivityLogsApiController.php';
    $controller = new ActivityLogsApiController();
    $controller->byUser($id);
});

$router->get('/api/activity-logs/action/{action}', function($action) {
    AuthMiddleware::check();
    require_once ROOT_PATH . '/controllers/api/ActivityLogsApiController.php';
    $controller = new ActivityLogsApiController();
    $controller->byAction($action);
});

$router->get('/api/activity-logs/entity/{type}/{id}', function($type, $id) {
    AuthMiddleware::check();
    require_once ROOT_PATH . '/controllers/api/ActivityLogsApiController.php';
    $controller = new ActivityLogsApiController();
    $controller->byEntity($type, $id);
});

$router->get('/api/activity-logs/recent', function() {
    AuthMiddleware::check();
    require_once ROOT_PATH . '/controllers/api/ActivityLogsApiController.php';
    $controller = new ActivityLogsApiController();
    $controller->recent();
});

$router->post('/api/activity-logs/cleanup', function() {
    AuthMiddleware::check();
    require_once ROOT_PATH . '/controllers/api/ActivityLogsApiController.php';
    $controller = new ActivityLogsApiController();
    $controller->cleanup();
});



// Legacy route for compatibility
$router->get('/api/transactions/trend', function() {
    AuthMiddleware::check();
    require_once ROOT_PATH . '/models/Transaction.php';
    require_once ROOT_PATH . '/controllers/web/TransactionController.php';
    $controller = new TransactionController();
    $controller->getTrendData();
});
