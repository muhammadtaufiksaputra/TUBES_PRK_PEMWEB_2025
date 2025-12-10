<?php
// Test API langsung tanpa routing
session_start();

define('ROOT_PATH', __DIR__ . '/src');

require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/core/Model.php';
require_once ROOT_PATH . '/core/Response.php';
require_once ROOT_PATH . '/models/ReportHelper.php';

// Simulasi user login
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'admin';

try {
    $reportHelper = new ReportHelper();
    $summary = $reportHelper->getInventorySummary();
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Test berhasil!',
        'data' => $summary
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>