<?php

/**
 * Authentication Helper
 */

class Auth
{
    /**
     * Login user
     */
    public static function login($user)
    {
        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);
        
        // Get user permissions
        $permissions = self::getUserPermissions($user['id']);
        
        // Set session data
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role_id' => $user['role_id'],
            'role_code' => $user['role_code'],
            'role_name' => $user['role_name'],
            'avatar_url' => $user['avatar_url'] ?? null,
            'permissions' => $permissions,
        ];
        
        // Mark session as authenticated
        $_SESSION['authenticated'] = true;
        $_SESSION['last_activity'] = time();
        $_SESSION['created'] = time();

        // Set remember me cookie if needed
        if (isset($_POST['remember']) && $_POST['remember']) {
            self::setRememberCookie($user['id']);
        }

        // Log activity
        self::logActivity($user['id'], 'login');
        
        // Force session write
        session_write_close();
        session_start();
    }

    /**
     * Logout user
     */
    public static function logout()
    {
        $userId = $_SESSION['user']['id'] ?? null;

        if ($userId) {
            self::logActivity($userId, 'logout');
        }

        // Clear remember me cookie
        self::clearRememberCookie();

        // Clear session cookie
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }

        // Clear all session data
        $_SESSION = [];
        
        // Destroy session
        session_destroy();
    }

    /**
     * Check if user is authenticated
     */
    public static function check()
    {
        // Check if session exists and is authenticated
        if (!isset($_SESSION['user']) || !isset($_SESSION['authenticated'])) {
            return false;
        }
        
        // Check session timeout
        if (isset($_SESSION['last_activity'])) {
            $timeout = SESSION_LIFETIME;
            if (time() - $_SESSION['last_activity'] > $timeout) {
                self::logout();
                return false;
            }
            $_SESSION['last_activity'] = time();
        }
        
        return true;
    }

    /**
     * Get current user
     */
    public static function user()
    {
        return $_SESSION['user'] ?? null;
    }

    /**
     * Get user ID
     */
    public static function id()
    {
        return $_SESSION['user']['id'] ?? null;
    }

    /**
     * Check if user has role
     */
    public static function hasRole($role)
    {
        if (!self::check()) {
            return false;
        }

        $userRole = $_SESSION['user']['role_code'] ?? null;

        if (is_array($role)) {
            return in_array($userRole, $role);
        }

        return $userRole === $role;
    }

    /**
     * Check if user has permission
     */
    public static function hasPermission($permissionCode)
    {
        if (!self::check()) {
            return false;
        }

        $userId = self::id();
        $db = Database::getInstance()->getConnection();

        $sql = "SELECT COUNT(*) FROM role_permissions rp
                JOIN permissions p ON rp.permission_id = p.id
                JOIN user_roles ur ON rp.role_id = ur.role_id
                WHERE ur.user_id = ? AND p.code = ? AND rp.is_default = 1";

        $stmt = $db->prepare($sql);
        $stmt->execute([$userId, $permissionCode]);

        return $stmt->fetchColumn() > 0;
    }

    /**
     * Attempt login
     */
    public static function attempt($email, $password)
    {
        $userModel = new User();
        $user = $userModel->findByEmailWithRole($email);

        if (!$user) {
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        // Check if user is active
        if (!$user['is_active']) {
            return false;
        }

        self::login($user);
        return true;
    }

    /**
     * Set remember me cookie
     */
    private static function setRememberCookie($userId)
    {
        $token = bin2hex(random_bytes(32));
        $expiry = time() + (86400 * 30); // 30 days

        // Save token to database
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
        $stmt->execute([$token, $userId]);

        // Set cookie
        setcookie('remember_token', $token, $expiry, '/', '', false, true);
    }

    /**
     * Clear remember me cookie
     */
    private static function clearRememberCookie()
    {
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/', '', false, true);
        }
    }

    /**
     * Check remember me cookie
     */
    public static function checkRememberMe()
    {
        if (!isset($_COOKIE['remember_token'])) {
            return false;
        }

        $token = $_COOKIE['remember_token'];
        $userModel = new User();
        $user = $userModel->findByRememberToken($token);

        if ($user && $user['is_active']) {
            self::login($user);
            return true;
        }

        return false;
    }

    /**
     * Get user permissions from database
     */
    private static function getUserPermissions($userId)
    {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                SELECT DISTINCT p.code
                FROM permissions p
                INNER JOIN role_permissions rp ON p.id = rp.permission_id
                INNER JOIN user_roles ur ON rp.role_id = ur.role_id
                WHERE ur.user_id = ?
            ");
            $stmt->execute([$userId]);
            
            $permissions = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $permissions[] = $row['code'];
            }
            
            return $permissions;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Log user activity
     */
    private static function logActivity($userId, $action)
    {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, entity_type, ip_address, user_agent, created_at) 
                                  VALUES (?, ?, 'auth', ?, ?, NOW())");
            $stmt->execute([
                $userId,
                $action,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (Exception $e) {
            // Silent fail
        }
    }
}
