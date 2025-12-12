<?php

/**
 * Permission Middleware
 * Check if user has required permission(s) to access a route
 * 
 * @requires helpers/functions.php (loaded in index.php)
 * Functions used: is_logged_in(), has_permission(), redirect_to(), url()
 */
class PermissionMiddleware
{
    /**
     * Handle permission check
     * @param string|array $permissions Single permission or array of permissions (OR logic)
     */
    public static function handle($permissions)
    {
        if (!is_logged_in()) {
            redirect('/login');
        }

        // Convert single permission to array
        if (!is_array($permissions)) {
            $permissions = [$permissions];
        }

        // Check if user has any of the required permissions
        $hasPermission = false;
        foreach ($permissions as $permission) {
            if (has_permission($permission)) {
                $hasPermission = true;
                break;
            }
        }

        if (!$hasPermission) {
            // Set flash message
            $_SESSION['flash_error'] = 'Anda tidak memiliki izin untuk mengakses halaman ini. Silakan hubungi administrator untuk mengatur hak akses Anda.';
            
            // Show unauthorized page
            http_response_code(403);
            require_once ROOT_PATH . '/views/errors/unauthorized.php';
            exit;
        }

        return true;
    }

    /**
     * Check if user has ALL specified permissions (AND logic)
     */
    public static function requireAll($permissions)
    {
        if (!is_logged_in()) {
            redirect('/login');
        }

        if (!is_array($permissions)) {
            $permissions = [$permissions];
        }

        foreach ($permissions as $permission) {
            if (!has_permission($permission)) {
                $_SESSION['flash_error'] = 'Anda tidak memiliki izin untuk mengakses halaman ini. Silakan hubungi administrator untuk mengatur hak akses Anda.';
                
                // Show unauthorized page
                http_response_code(403);
                require_once ROOT_PATH . '/views/errors/unauthorized.php';
                exit;
            }
        }

        return true;
    }
}
