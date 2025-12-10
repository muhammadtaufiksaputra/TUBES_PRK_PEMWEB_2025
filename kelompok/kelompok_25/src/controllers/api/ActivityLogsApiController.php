<?php

require_once ROOT_PATH . '/core/Controller.php';
require_once ROOT_PATH . '/models/ActivityLog.php';

class ActivityLogsApiController extends Controller
{
    private $activityLog;

    public function __construct()
    {
        parent::__construct();
        $this->activityLog = new ActivityLog();
    }

    /**
     * GET /api/activity-logs - List with filters
     */
    public function index()
    {
        try {
            $page = $_GET['page'] ?? 1;
            $perPage = $_GET['per_page'] ?? 20;
            
            $filters = [
                'user_id' => $_GET['user_id'] ?? null,
                'action' => $_GET['action'] ?? null,
                'entity_type' => $_GET['entity_type'] ?? null,
                'start_date' => $_GET['start_date'] ?? null,
                'end_date' => $_GET['end_date'] ?? null
            ];

            // Remove empty filters
            $filters = array_filter($filters, function($value) {
                return $value !== null && $value !== '';
            });

            $result = $this->activityLog->getAll($page, $perPage, $filters);
            
            ActivityLog::logActivity('VIEW', 'activity_log', null, 'Viewed activity logs list');
            
            Response::success('Activity logs retrieved successfully', $result);
        } catch (Exception $e) {
            Response::error('Failed to get activity logs: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/activity-logs/user/{id} - User activity
     */
    public function byUser($userId)
    {
        try {
            if (!is_numeric($userId)) {
                Response::error('Invalid user ID', 400);
                return;
            }

            $dateRange = null;
            if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
                $dateRange = [
                    'start' => $_GET['start_date'],
                    'end' => $_GET['end_date']
                ];
            }

            $logs = $this->activityLog->getByUser($userId, $dateRange);
            
            ActivityLog::logActivity('VIEW', 'activity_log', $userId, "Viewed activity logs for user ID {$userId}");
            
            Response::success('User activity logs retrieved successfully', [
                'user_id' => (int)$userId,
                'date_range' => $dateRange,
                'logs' => $logs,
                'count' => count($logs)
            ]);
        } catch (Exception $e) {
            Response::error('Failed to get user activity logs: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/activity-logs/action/{action} - By action
     */
    public function byAction($action)
    {
        try {
            $dateRange = null;
            if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
                $dateRange = [
                    'start' => $_GET['start_date'],
                    'end' => $_GET['end_date']
                ];
            }

            $logs = $this->activityLog->getByAction($action, $dateRange);
            
            ActivityLog::logActivity('VIEW', 'activity_log', null, "Viewed activity logs for action: {$action}");
            
            Response::success('Action activity logs retrieved successfully', [
                'action' => $action,
                'date_range' => $dateRange,
                'logs' => $logs,
                'count' => count($logs)
            ]);
        } catch (Exception $e) {
            Response::error('Failed to get action activity logs: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/activity-logs/entity/{type}/{id} - By entity
     */
    public function byEntity($entityType, $entityId)
    {
        try {
            if (!is_numeric($entityId)) {
                Response::error('Invalid entity ID', 400);
                return;
            }

            $logs = $this->activityLog->getByEntity($entityType, $entityId);
            
            ActivityLog::logActivity('VIEW', 'activity_log', null, "Viewed activity logs for {$entityType} ID {$entityId}");
            
            Response::success('Entity activity logs retrieved successfully', [
                'entity_type' => $entityType,
                'entity_id' => (int)$entityId,
                'logs' => $logs,
                'count' => count($logs)
            ]);
        } catch (Exception $e) {
            Response::error('Failed to get entity activity logs: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/activity-logs/recent - Recent activities
     */
    public function recent()
    {
        try {
            $limit = $_GET['limit'] ?? 10;
            
            if (!is_numeric($limit) || $limit > 100) {
                $limit = 10;
            }

            $logs = $this->activityLog->getRecent($limit);
            
            ActivityLog::logActivity('VIEW', 'activity_log', null, "Viewed recent activity logs (limit: {$limit})");
            
            Response::success('Recent activity logs retrieved successfully', [
                'limit' => (int)$limit,
                'logs' => $logs,
                'count' => count($logs)
            ]);
        } catch (Exception $e) {
            Response::error('Failed to get recent activity logs: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/activity-logs/cleanup - Clean old logs
     */
    public function cleanup()
    {
        try {
            // Only admin can cleanup logs
            if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
                Response::error('Unauthorized. Admin access required.', 403);
                return;
            }

            $days = $_POST['days'] ?? 90;
            
            if (!is_numeric($days) || $days < 30) {
                Response::error('Invalid days parameter. Minimum 30 days.', 400);
                return;
            }

            $result = $this->activityLog->cleanOldLogs($days);
            
            ActivityLog::logActivity('DELETE', 'activity_log', null, "Cleaned activity logs older than {$days} days");
            
            Response::success('Old activity logs cleaned successfully', [
                'days' => (int)$days,
                'affected_rows' => $result->rowCount()
            ]);
        } catch (Exception $e) {
            Response::error('Failed to cleanup activity logs: ' . $e->getMessage(), 500);
        }
    }
}