<?php

/**
 * Web Routes
 */

// Guest routes (only accessible when not logged in)
$router->get('/', 'web/AuthController@showLogin');
$router->get('/login', 'web/AuthController@showLogin');
$router->post('/login', 'web/AuthController@login');
$router->get('/forgot-password', 'web/AuthController@showForgotPassword');
$router->post('/forgot-password', 'web/AuthController@forgotPassword');

// Authenticated routes
$router->get('/dashboard', function() {
    AuthMiddleware::check();
    PermissionMiddleware::handle('view_dashboard');
    require_once ROOT_PATH . '/controllers/web/DashboardController.php';
    $controller = new DashboardController();
    $controller->index();
});

$router->post('/logout', 'web/AuthController@logout');

// Materials routes
$router->get('/materials', function() {
    AuthMiddleware::check();
    PermissionMiddleware::handle('view_materials');
    require_once ROOT_PATH . '/controllers/web/PageController.php';
    $controller = new PageController();
    $controller->materials();
});

// Suppliers routes
$router->get('/suppliers', function() {
    AuthMiddleware::check();
    PermissionMiddleware::handle('view_suppliers');
    require_once ROOT_PATH . '/controllers/web/PageController.php';
    $controller = new PageController();
    $controller->suppliers();
});

// Categories routes
$router->get('/categories', function() {
    AuthMiddleware::check();
    PermissionMiddleware::handle('view_categories');
    require_once ROOT_PATH . '/controllers/web/PageController.php';
    $controller = new PageController();
    $controller->categories();
});

// Stock In routes
$router->get('/stock-in', function() {
    AuthMiddleware::check();
    PermissionMiddleware::handle('view_stock_in');
    require_once ROOT_PATH . '/controllers/web/PageController.php';
    $controller = new PageController();
    $controller->stockIn();
});

// Stock Out routes
$router->get('/stock-out', function() {
    AuthMiddleware::check();
    PermissionMiddleware::handle('view_stock_out');
    require_once ROOT_PATH . '/controllers/web/PageController.php';
    $controller = new PageController();
    $controller->stockOut();
});

// Stock Adjustments routes
$router->get('/stock-adjustments', function() {
    AuthMiddleware::check();
    PermissionMiddleware::handle('view_stock_adjustments');
    require_once ROOT_PATH . '/models/StockAdjustment.php';
    require_once ROOT_PATH . '/models/Material.php';
    require_once ROOT_PATH . '/controllers/web/StockAdjustmentController.php';
    $controller = new StockAdjustmentController();
    $controller->index();
});

// Reports routes
$router->get('/reports/stock', function() {
    AuthMiddleware::check();
    PermissionMiddleware::handle('view_reports');
    require_once ROOT_PATH . '/controllers/web/ReportController.php';
    $controller = new ReportController();
    $controller->stockReport();
});

$router->get('/reports/export-excel', function() {
    AuthMiddleware::check();
    PermissionMiddleware::handle('export_reports');
    require_once ROOT_PATH . '/controllers/web/ReportController.php';
    $controller = new ReportController();
    $controller->exportExcel();
});

$router->get('/reports/export-transactions', function() {
    AuthMiddleware::check();
    PermissionMiddleware::handle('export_reports');
    require_once ROOT_PATH . '/controllers/web/ReportController.php';
    $controller = new ReportController();
    $controller->exportTransactions();
});

$router->get('/test-export', function() {
    echo 'Test export route works!';
});

$router->get('/reports/transactions', function() {
    AuthMiddleware::check();
    PermissionMiddleware::handle('view_reports');
    require_once ROOT_PATH . '/models/Transaction.php';
    require_once ROOT_PATH . '/controllers/web/TransactionController.php';
    $controller = new TransactionController();
    $controller->report();
});

$router->get('/reports/transactions/export', function() {
    AuthMiddleware::check();
    PermissionMiddleware::handle('export_reports');
    require_once ROOT_PATH . '/models/Transaction.php';
    require_once ROOT_PATH . '/controllers/web/TransactionController.php';
    $controller = new TransactionController();
    $controller->exportCSV();
});

$router->get('/reports/low-stock', function() {
    AuthMiddleware::check();
    PermissionMiddleware::handle('view_low_stock');
    require_once ROOT_PATH . '/controllers/web/PageController.php';
    $controller = new PageController();
    $controller->reportsLowStock();
});

$router->get('/reports/low-stock/export', function() {
    AuthMiddleware::check();
    PermissionMiddleware::handle(['view_low_stock', 'export_reports']);
    require_once ROOT_PATH . '/controllers/web/ReportController.php';
    $controller = new ReportController();
    $controller->exportLowStock();
});
$router->get('/roles', 'web/PageController@roles');
$router->get('/users', 'web/PageController@users');
$router->get('/profile', 'web/PageController@profile');
