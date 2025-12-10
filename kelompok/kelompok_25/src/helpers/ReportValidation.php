<?php

/**
 * Report Validation Helper
 */

class ReportValidation
{
    /**
     * Validate date format (YYYY-MM-DD)
     */
    public static function validateDate($date)
    {
        if (empty($date)) {
            return false;
        }

        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    /**
     * Validate date range
     */
    public static function validateDateRange($startDate, $endDate)
    {
        if (!self::validateDate($startDate) || !self::validateDate($endDate)) {
            return [
                'valid' => false,
                'message' => 'Invalid date format. Use YYYY-MM-DD'
            ];
        }

        if (strtotime($startDate) > strtotime($endDate)) {
            return [
                'valid' => false,
                'message' => 'Start date must be before end date'
            ];
        }

        // Check if date range is not too large (max 1 year)
        $diff = (strtotime($endDate) - strtotime($startDate)) / (60 * 60 * 24);
        if ($diff > 365) {
            return [
                'valid' => false,
                'message' => 'Date range cannot exceed 365 days'
            ];
        }

        return [
            'valid' => true,
            'message' => 'Valid date range'
        ];
    }

    /**
     * Validate pagination parameters
     */
    public static function validatePagination($page, $perPage)
    {
        $page = (int)$page;
        $perPage = (int)$perPage;

        if ($page < 1) {
            $page = 1;
        }

        if ($perPage < 1 || $perPage > 100) {
            $perPage = 20;
        }

        return [
            'page' => $page,
            'per_page' => $perPage
        ];
    }

    /**
     * Validate material ID
     */
    public static function validateMaterialId($materialId)
    {
        return is_numeric($materialId) && (int)$materialId > 0;
    }

    /**
     * Validate user ID
     */
    public static function validateUserId($userId)
    {
        return is_numeric($userId) && (int)$userId > 0;
    }

    /**
     * Validate report type
     */
    public static function validateReportType($type)
    {
        $allowedTypes = ['value', 'quantity', 'usage'];
        return in_array($type, $allowedTypes);
    }

    /**
     * Validate activity log action
     */
    public static function validateAction($action)
    {
        $allowedActions = [
            'CREATE', 'UPDATE', 'DELETE', 'VIEW',
            'LOGIN', 'LOGOUT',
            'STOCK_IN', 'STOCK_OUT', 'ADJUSTMENT',
            'EXPORT', 'IMPORT'
        ];
        return in_array(strtoupper($action), $allowedActions);
    }

    /**
     * Validate entity type
     */
    public static function validateEntityType($entityType)
    {
        $allowedTypes = [
            'user', 'material', 'supplier', 'category',
            'stock_in', 'stock_out', 'stock_adjustment',
            'report', 'activity_log'
        ];
        return in_array(strtolower($entityType), $allowedTypes);
    }

    /**
     * Sanitize input data
     */
    public static function sanitizeInput($data)
    {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }
        
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validate and sanitize filters
     */
    public static function validateFilters($filters)
    {
        $validatedFilters = [];

        if (isset($filters['user_id']) && self::validateUserId($filters['user_id'])) {
            $validatedFilters['user_id'] = (int)$filters['user_id'];
        }

        if (isset($filters['action']) && self::validateAction($filters['action'])) {
            $validatedFilters['action'] = strtoupper($filters['action']);
        }

        if (isset($filters['entity_type']) && self::validateEntityType($filters['entity_type'])) {
            $validatedFilters['entity_type'] = strtolower($filters['entity_type']);
        }

        if (isset($filters['start_date']) && self::validateDate($filters['start_date'])) {
            $validatedFilters['start_date'] = $filters['start_date'];
        }

        if (isset($filters['end_date']) && self::validateDate($filters['end_date'])) {
            $validatedFilters['end_date'] = $filters['end_date'];
        }

        return $validatedFilters;
    }
}