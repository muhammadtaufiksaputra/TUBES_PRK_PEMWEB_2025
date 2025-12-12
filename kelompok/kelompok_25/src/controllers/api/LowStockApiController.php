<?php

/**
 * Low Stock Alert API Controller
 * Handles API endpoints for low stock materials monitoring
 */

require_once ROOT_PATH . '/core/Controller.php';
require_once ROOT_PATH . '/core/Response.php';
require_once ROOT_PATH . '/core/Auth.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/middleware/AuthMiddleware.php';
require_once ROOT_PATH . '/models/Material.php';
require_once ROOT_PATH . '/models/Category.php';
require_once ROOT_PATH . '/models/ActivityLog.php';

class LowStockApiController extends Controller
{
    private $materialModel;
    private $categoryModel;

    public function __construct()
    {
        AuthMiddleware::check();
        $this->materialModel = new Material();
        $this->categoryModel = new Category();
    }

    /**
     * GET /api/low-stock
     * Get all materials with low stock (current_stock <= min_stock)
     */
    public function index()
    {
        try {
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;
            $filters = [];

            if (isset($_GET['search'])) {
                $filters['search'] = trim($_GET['search']);
            }

            if (isset($_GET['category_id'])) {
                $filters['category_id'] = (int)$_GET['category_id'];
            }

            if (isset($_GET['only_out_of_stock'])) {
                $filters['only_out_of_stock'] = (bool)$_GET['only_out_of_stock'];
            }

            $result = $this->materialModel->getLowStock($page, $perPage, $filters);

            $this->logActivity('view', 'low_stock', null, 'Viewed low stock materials');

            Response::success('Low stock materials retrieved successfully', $result);
        } catch (Exception $e) {
            Response::error('Failed to fetch low stock materials: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * GET /api/low-stock/summary
     * Get summary statistics for low stock materials
     */
    public function summary()
    {
        try {
            $summary = $this->materialModel->getLowStockSummary();

            Response::success('Low stock summary retrieved successfully', $summary);
        } catch (Exception $e) {
            Response::error('Failed to fetch low stock summary: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * GET /api/low-stock/categories
     * Get categories with low stock materials
     */
    public function categories()
    {
        try {
            $sql = "SELECT 
                        c.id,
                        c.name,
                        COUNT(m.id) as low_stock_count,
                        SUM(CASE WHEN m.current_stock = 0 THEN 1 ELSE 0 END) as out_of_stock_count
                    FROM categories c
                    INNER JOIN materials m ON c.id = m.category_id
                    WHERE m.is_active = 1 
                    AND m.current_stock <= m.min_stock
                    GROUP BY c.id, c.name
                    ORDER BY low_stock_count DESC";

            $stmt = $this->materialModel->query($sql);
            $categories = $stmt->fetchAll();

            Response::success('Categories with low stock retrieved successfully', $categories);
        } catch (Exception $e) {
            Response::error('Failed to fetch categories: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * GET /api/low-stock/urgent
     * Get materials that are completely out of stock (current_stock = 0)
     */
    public function urgent()
    {
        try {
            $sql = "SELECT 
                        m.id,
                        m.code,
                        m.name,
                        m.unit,
                        m.current_stock,
                        m.min_stock,
                        c.name as category_name,
                        s.name as supplier_name,
                        s.phone as supplier_phone,
                        s.email as supplier_email,
                        COALESCE(
                            (SELECT unit_price FROM stock_in WHERE material_id = m.id ORDER BY created_at DESC LIMIT 1),
                            0
                        ) as last_unit_price,
                        COALESCE(
                            (SELECT MAX(created_at) FROM stock_in WHERE material_id = m.id),
                            NULL
                        ) as last_stock_in_date
                    FROM materials m
                    LEFT JOIN categories c ON m.category_id = c.id
                    LEFT JOIN suppliers s ON m.default_supplier_id = s.id
                    WHERE m.is_active = 1 AND m.current_stock = 0
                    ORDER BY m.name ASC";

            $stmt = $this->materialModel->query($sql);
            $urgent = $stmt->fetchAll();

            $this->logActivity('view', 'low_stock', null, 'Viewed urgent out-of-stock materials');

            Response::success('Urgent out-of-stock materials retrieved successfully', [
                'count' => count($urgent),
                'materials' => $urgent
            ]);
        } catch (Exception $e) {
            Response::error('Failed to fetch urgent materials: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * GET /api/low-stock/reorder-suggestions
     * Get suggested reorder quantities based on min_stock and recent consumption
     */
    public function reorderSuggestions()
    {
        try {
            $sql = "SELECT 
                        m.id,
                        m.code,
                        m.name,
                        m.unit,
                        m.current_stock,
                        m.min_stock,
                        (m.min_stock - m.current_stock) as min_reorder_qty,
                        (m.min_stock * 2 - m.current_stock) as suggested_reorder_qty,
                        c.name as category_name,
                        s.name as supplier_name,
                        s.phone as supplier_phone,
                        COALESCE(
                            (SELECT unit_price FROM stock_in WHERE material_id = m.id ORDER BY created_at DESC LIMIT 1),
                            0
                        ) as last_unit_price,
                        (m.min_stock * 2 - m.current_stock) * COALESCE(
                            (SELECT unit_price FROM stock_in WHERE material_id = m.id ORDER BY created_at DESC LIMIT 1),
                            0
                        ) as estimated_cost
                    FROM materials m
                    LEFT JOIN categories c ON m.category_id = c.id
                    LEFT JOIN suppliers s ON m.default_supplier_id = s.id
                    WHERE m.is_active = 1 
                    AND m.current_stock <= m.min_stock
                    ORDER BY 
                        CASE WHEN m.current_stock = 0 THEN 0 ELSE 1 END,
                        estimated_cost DESC";

            $stmt = $this->materialModel->query($sql);
            $suggestions = $stmt->fetchAll();

            $totalEstimatedCost = array_sum(array_column($suggestions, 'estimated_cost'));

            Response::success('Reorder suggestions retrieved successfully', [
                'total_items' => count($suggestions),
                'total_estimated_cost' => $totalEstimatedCost,
                'suggestions' => $suggestions
            ]);
        } catch (Exception $e) {
            Response::error('Failed to fetch reorder suggestions: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * POST /api/low-stock/{id}/notify
     * Mark material as notified (for tracking notifications sent)
     */
    public function notify($id)
    {
        try {
            $material = $this->materialModel->find($id);

            if (!$material) {
                Response::notFound('Material not found');
            }

            // Log notification action
            $this->logActivity('notify', 'material', $id, "Low stock notification sent for material: {$material['name']}");

            Response::success('Notification logged successfully', [
                'material_id' => $id,
                'notified_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            Response::error('Failed to log notification: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Log activity helper
     */
    private function logActivity($action, $entity, $entityId, $description)
    {
        try {
            ActivityLog::logActivity($action, $entity, $entityId, $description);
        } catch (Exception $e) {
            error_log('Failed to log activity: ' . $e->getMessage());
        }
    }
}
