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

// Materials API routes
$router->get('/api/materials', function() {
    AuthMiddleware::check();
    Response::success('Materials API endpoint', []);
});

$router->get('/api/materials/{id}', function($id) {
    AuthMiddleware::check();
    Response::success('Material detail', ['id' => $id]);
});

$router->post('/api/materials', function() {
    AuthMiddleware::check();
    RoleMiddleware::staff();
    Response::success('Create material endpoint', []);
});

$router->post('/api/materials/{id}', function($id) {
    AuthMiddleware::check();
    RoleMiddleware::staff();
    Response::success('Update material', ['id' => $id]);
});

$router->post('/api/materials/{id}/delete', function($id) {
    AuthMiddleware::check();
    RoleMiddleware::manager();
    Response::success('Delete material', ['id' => $id]);
});

// Stock API routes
$router->get('/api/stock', function() {
    AuthMiddleware::check();
    Response::success('Stock API endpoint', []);
});

$router->post('/api/stock/in', function() {
    AuthMiddleware::check();
    RoleMiddleware::staff();
    Response::success('Stock in endpoint', []);
});

$router->post('/api/stock/out', function() {
    AuthMiddleware::check();
    RoleMiddleware::staff();
    Response::success('Stock out endpoint', []);
});

// Reports API routes
$router->get('/api/reports/stock', function() {
    AuthMiddleware::check();
    Response::success('Stock report endpoint', []);
});

$router->get('/api/reports/transactions', function() {
    AuthMiddleware::check();
    Response::success('Transactions report endpoint', []);
});

$router->get('/api/reports/low-stock', function() {
    AuthMiddleware::check();
    Response::success('Low stock report endpoint', []);
});
