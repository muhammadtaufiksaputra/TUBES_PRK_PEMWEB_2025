<?php

/**
 * Konfigurasi Aplikasi
 */

// Mode Development/Production
define('APP_ENV', 'development'); // 'development' atau 'production'
define('APP_DEBUG', true);

// Base URL
define('BASE_URL', 'http://localhost:8000');
define('ASSETS_URL', BASE_URL . '/assets');
define('UPLOAD_URL', BASE_URL . '/assets/uploads');

// Path
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('UPLOAD_PATH', PUBLIC_PATH . '/assets/uploads');

// Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'inventory_manager');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Session
define('SESSION_NAME', 'inventory_session');
define('SESSION_LIFETIME', 7200); // 2 jam

// Security
define('HASH_ALGO', PASSWORD_DEFAULT);
define('HASH_COST', 12);

// Upload
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/jpg', 'image/webp']);
define('UPLOAD_MAX_WIDTH', 2000);
define('UPLOAD_MAX_HEIGHT', 2000);

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Error Reporting
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
