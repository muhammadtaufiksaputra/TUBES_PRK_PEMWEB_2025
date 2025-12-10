<?php

require_once ROOT_PATH . '/core/Model.php';

class ActivityLog extends Model
{
    protected $table = 'activity_logs';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Create activity log entry
     */
    public function create($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->insert($data);
    }

    /**
     * Get all logs with pagination and filters
     */
    public function getAll($page = 1, $perPage = 20, $filters = [])
    {
        $offset = ($page - 1) * $perPage;
        $where = [];
        $params = [];

        // Build WHERE conditions
        if (!empty($filters['user_id'])) {
            $where[] = 'al.user_id = ?';
            $params[] = $filters['user_id'];
        }

        if (!empty($filters['action'])) {
            $where[] = 'al.action = ?';
            $params[] = $filters['action'];
        }

        if (!empty($filters['entity_type'])) {
            $where[] = 'al.entity_type = ?';
            $params[] = $filters['entity_type'];
        }

        if (!empty($filters['start_date'])) {
            $where[] = 'DATE(al.created_at) >= ?';
            $params[] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $where[] = 'DATE(al.created_at) <= ?';
            $params[] = $filters['end_date'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Get total count
        $countSql = "
            SELECT COUNT(*) as total
            FROM activity_logs al
            LEFT JOIN users u ON al.user_id = u.id
            {$whereClause}
        ";
        $total = $this->query($countSql, $params)->fetch()['total'];

        // Get data
        $sql = "
            SELECT 
                al.*,
                u.name as user_name,
                u.email as user_email
            FROM activity_logs al
            LEFT JOIN users u ON al.user_id = u.id
            {$whereClause}
            ORDER BY al.created_at DESC
            LIMIT ? OFFSET ?
        ";
        
        $params[] = $perPage;
        $params[] = $offset;
        
        $data = $this->query($sql, $params)->fetchAll();

        return [
            'data' => $data,
            'total' => (int)$total,
            'page' => (int)$page,
            'per_page' => (int)$perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Get logs by user
     */
    public function getByUser($userId, $dateRange = null)
    {
        $params = [$userId];
        $dateFilter = '';

        if ($dateRange && isset($dateRange['start']) && isset($dateRange['end'])) {
            $dateFilter = 'AND DATE(al.created_at) BETWEEN ? AND ?';
            $params[] = $dateRange['start'];
            $params[] = $dateRange['end'];
        }

        return $this->query("
            SELECT 
                al.*,
                u.name as user_name,
                u.email as user_email
            FROM activity_logs al
            LEFT JOIN users u ON al.user_id = u.id
            WHERE al.user_id = ? {$dateFilter}
            ORDER BY al.created_at DESC
        ", $params)->fetchAll();
    }

    /**
     * Get logs by action
     */
    public function getByAction($action, $dateRange = null)
    {
        $params = [$action];
        $dateFilter = '';

        if ($dateRange && isset($dateRange['start']) && isset($dateRange['end'])) {
            $dateFilter = 'AND DATE(al.created_at) BETWEEN ? AND ?';
            $params[] = $dateRange['start'];
            $params[] = $dateRange['end'];
        }

        return $this->query("
            SELECT 
                al.*,
                u.name as user_name,
                u.email as user_email
            FROM activity_logs al
            LEFT JOIN users u ON al.user_id = u.id
            WHERE al.action = ? {$dateFilter}
            ORDER BY al.created_at DESC
        ", $params)->fetchAll();
    }

    /**
     * Get logs by entity
     */
    public function getByEntity($entityType, $entityId)
    {
        return $this->query("
            SELECT 
                al.*,
                u.name as user_name,
                u.email as user_email
            FROM activity_logs al
            LEFT JOIN users u ON al.user_id = u.id
            WHERE al.entity_type = ? AND al.entity_id = ?
            ORDER BY al.created_at DESC
        ", [$entityType, $entityId])->fetchAll();
    }

    /**
     * Get recent activities
     */
    public function getRecent($limit = 10)
    {
        return $this->query("
            SELECT 
                al.*,
                u.name as user_name,
                u.email as user_email
            FROM activity_logs al
            LEFT JOIN users u ON al.user_id = u.id
            ORDER BY al.created_at DESC
            LIMIT ?
        ", [$limit])->fetchAll();
    }

    /**
     * Clean old logs (remove logs older than specified days)
     */
    public function cleanOldLogs($days = 90)
    {
        return $this->query("
            DELETE FROM activity_logs 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
        ", [$days]);
    }

    /**
     * Log activity helper function
     */
    public static function logActivity($action, $entityType = null, $entityId = null, $description = null)
    {
        $log = new self();
        
        // Get current user
        $userId = null;
        if (isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
        }

        // Get IP address
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        // Get user agent
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        $data = [
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent
        ];

        return $log->create($data);
    }
}