<?php

require_once ROOT_PATH . '/models/ActivityLog.php';

/**
 * Activity Logger Helper
 * Provides easy logging methods for different actions
 */
class ActivityLogger
{
    /**
     * Log user authentication actions
     */
    public static function logAuth($action, $userId = null, $description = null)
    {
        return ActivityLog::logActivity($action, 'user', $userId, $description);
    }

    /**
     * Log material operations
     */
    public static function logMaterial($action, $materialId, $description = null)
    {
        return ActivityLog::logActivity($action, 'material', $materialId, $description);
    }

    /**
     * Log stock operations
     */
    public static function logStock($action, $materialId, $description = null)
    {
        return ActivityLog::logActivity($action, 'stock', $materialId, $description);
    }

    /**
     * Log supplier operations
     */
    public static function logSupplier($action, $supplierId, $description = null)
    {
        return ActivityLog::logActivity($action, 'supplier', $supplierId, $description);
    }

    /**
     * Log category operations
     */
    public static function logCategory($action, $categoryId, $description = null)
    {
        return ActivityLog::logActivity($action, 'category', $categoryId, $description);
    }

    /**
     * Log report access
     */
    public static function logReport($reportType, $description = null)
    {
        return ActivityLog::logActivity('VIEW', 'report', null, $description ?: "Viewed {$reportType} report");
    }

    /**
     * Log system operations
     */
    public static function logSystem($action, $description = null)
    {
        return ActivityLog::logActivity($action, 'system', null, $description);
    }

    /**
     * Log data export/import
     */
    public static function logDataOperation($action, $entityType, $description = null)
    {
        return ActivityLog::logActivity($action, $entityType, null, $description);
    }

    /**
     * Log stock transactions with details
     */
    public static function logStockTransaction($type, $materialId, $quantity, $reference = null)
    {
        $action = $type === 'in' ? 'STOCK_IN' : 'STOCK_OUT';
        $description = ucfirst($type) . " transaction: {$quantity} units";
        if ($reference) {
            $description .= " (Ref: {$reference})";
        }
        
        return ActivityLog::logActivity($action, 'material', $materialId, $description);
    }

    /**
     * Log stock adjustment
     */
    public static function logStockAdjustment($materialId, $oldStock, $newStock, $reason = null)
    {
        $difference = $newStock - $oldStock;
        $description = "Stock adjusted from {$oldStock} to {$newStock} (diff: {$difference})";
        if ($reason) {
            $description .= " - Reason: {$reason}";
        }
        
        return ActivityLog::logActivity('ADJUSTMENT', 'material', $materialId, $description);
    }

    /**
     * Log user management actions
     */
    public static function logUserManagement($action, $targetUserId, $description = null)
    {
        return ActivityLog::logActivity($action, 'user', $targetUserId, $description);
    }

    /**
     * Log role and permission changes
     */
    public static function logRoleChange($userId, $oldRole, $newRole)
    {
        $description = "Role changed from {$oldRole} to {$newRole}";
        return ActivityLog::logActivity('UPDATE', 'user', $userId, $description);
    }

    /**
     * Log critical security events
     */
    public static function logSecurity($event, $description = null)
    {
        return ActivityLog::logActivity('SECURITY', 'system', null, $description ?: $event);
    }

    /**
     * Log failed login attempts
     */
    public static function logFailedLogin($email, $reason = 'Invalid credentials')
    {
        $description = "Failed login attempt for email: {$email} - {$reason}";
        return ActivityLog::logActivity('LOGIN_FAILED', 'user', null, $description);
    }

    /**
     * Log successful login
     */
    public static function logSuccessfulLogin($userId, $email)
    {
        $description = "Successful login for user: {$email}";
        return ActivityLog::logActivity('LOGIN', 'user', $userId, $description);
    }

    /**
     * Log logout
     */
    public static function logLogout($userId, $email)
    {
        $description = "User logged out: {$email}";
        return ActivityLog::logActivity('LOGOUT', 'user', $userId, $description);
    }

    /**
     * Log bulk operations
     */
    public static function logBulkOperation($action, $entityType, $count, $description = null)
    {
        $desc = $description ?: "Bulk {$action} operation on {$count} {$entityType} records";
        return ActivityLog::logActivity($action, $entityType, null, $desc);
    }

    /**
     * Log configuration changes
     */
    public static function logConfigChange($setting, $oldValue, $newValue)
    {
        $description = "Configuration changed: {$setting} from '{$oldValue}' to '{$newValue}'";
        return ActivityLog::logActivity('UPDATE', 'system', null, $description);
    }

    /**
     * Log file operations
     */
    public static function logFileOperation($action, $filename, $description = null)
    {
        $desc = $description ?: "{$action} file: {$filename}";
        return ActivityLog::logActivity($action, 'file', null, $desc);
    }
}