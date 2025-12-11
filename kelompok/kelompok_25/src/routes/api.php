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

// User & Role Management API routes
$router->get('/api/users', 'api/UserApiController@index');
$router->get('/api/users/{id}', 'api/UserApiController@show');
$router->post('/api/users', 'api/UserApiController@store');
$router->post('/api/users/{id}', 'api/UserApiController@update');
$router->post('/api/users/{id}/deactivate', 'api/UserApiController@deactivate');
$router->post('/api/users/{id}/reset-password', 'api/UserApiController@resetPassword');
$router->post('/api/users/{id}/role', 'api/UserApiController@setRole');

$router->get('/api/roles', 'api/RoleApiController@index');
$router->get('/api/roles/{id}', 'api/RoleApiController@show');
$router->post('/api/roles', 'api/RoleApiController@store');
$router->post('/api/roles/{id}', 'api/RoleApiController@update');
$router->post('/api/roles/{id}/delete', 'api/RoleApiController@destroy');
$router->post('/api/roles/{id}/permissions', 'api/RoleApiController@syncPermissions');

$router->get('/api/permissions', 'api/RoleApiController@permissions');

// Low Stock Alert API routes
$router->get('/api/low-stock', 'api/LowStockApiController@index');
$router->get('/api/low-stock/summary', 'api/LowStockApiController@summary');
$router->get('/api/low-stock/categories', 'api/LowStockApiController@categories');
$router->get('/api/low-stock/urgent', 'api/LowStockApiController@urgent');
$router->get('/api/low-stock/reorder-suggestions', 'api/LowStockApiController@reorderSuggestions');
$router->post('/api/low-stock/{id}/notify', 'api/LowStockApiController@notify');

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