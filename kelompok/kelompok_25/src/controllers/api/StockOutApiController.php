<?php

/**
 * Stock Out API Controller
 * Handle API requests untuk Stock Out Management
 */

require_once ROOT_PATH . '/core/Controller.php';
require_once ROOT_PATH . '/core/Response.php';
require_once ROOT_PATH . '/core/Auth.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/middleware/AuthMiddleware.php';
require_once ROOT_PATH . '/models/StockOut.php';

class StockOutApiController extends Controller
{
    private $model;
    private $db;

    public function __construct()
    {
        AuthMiddleware::check();
        
        $this->db = Database::getInstance()->getConnection();
        $this->model = new StockOut($this->db);
    }

    /**
     * GET /api/stock-out
     * List all stock out transactions
     */
    public function index()
    {
        try {
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;
            
            if ($page < 1) $page = 1;
            if ($perPage < 1 || $perPage > 100) $perPage = 20;

            $filters = [
                'material_id' => isset($_GET['material_id']) ? (int)$_GET['material_id'] : null,
                'usage_type' => $_GET['usage_type'] ?? null,
                'start_date' => $_GET['start_date'] ?? null,
                'end_date' => $_GET['end_date'] ?? null,
                'q' => $_GET['q'] ?? null
            ];

            $result = $this->model->getAll($page, $perPage, $filters);

            Response::success('Data retrieved successfully', $result);

        } catch (Exception $e) {
            Response::error('Gagal mengambil data stock out', [], 500);
        }
    }

    /**
     * GET /api/stock-out/:id
     * Get stock out detail
     */
    public function show($id)
    {
        try {
            if (!is_numeric($id) || $id < 1) {
                Response::error('ID tidak valid', [], 400);
                return;
            }

            $stockOut = $this->model->findById($id);

            if (!$stockOut) {
                Response::notFound('Stock out tidak ditemukan');
                return;
            }

            Response::success('Detail retrieved successfully', $stockOut);

        } catch (Exception $e) {
            Response::error('Gagal mengambil detail', [], 500);
        }
    }

    /**
     * POST /api/stock-out
     * Create new stock out transaction
     */
    public function store()
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Response::error('Invalid JSON format', [], 400);
                return;
            }

            $userId = Auth::id();

            $payload = [
                'material_id' => isset($input['material_id']) ? (int)$input['material_id'] : null,
                'quantity' => isset($input['quantity']) ? (float)$input['quantity'] : null,
                'usage_type' => $input['usage_type'] ?? null,
                'transaction_date' => $input['transaction_date'] ?? date('Y-m-d'),
                'destination' => $input['destination'] ?? null,
                'notes' => $input['notes'] ?? null,
                'created_by' => $userId
            ];

            $stockOut = $this->model->create($payload);

            // Log activity
            $this->logActivity('CREATE', 'stock_out', $stockOut['id'], 
                "Created stock out {$stockOut['reference_number']} for material ID {$input['material_id']}");

            Response::created('Stock out berhasil dibuat', $stockOut);

        } catch (Exception $e) {
            Response::error('Gagal membuat stock out: ' . $e->getMessage(), [], 400);
        }
    }

    /**
     * GET /api/stock-out/material/:id
     * Get stock out by material
     */
    public function material($materialId)
    {
        try {
            $materialId = (int)$materialId;
            $start = $_GET['start_date'] ?? null;
            $end = $_GET['end_date'] ?? null;
            
            $dateRange = null;
            if ($start && $end) {
                $dateRange = ['start' => $start, 'end' => $end];
            }

            $stockOuts = $this->model->getByMaterial($materialId, $dateRange);

            Response::success('Data stock out berhasil diambil', ['data' => $stockOuts]);

        } catch (Exception $e) {
            Response::error('Gagal mengambil data', [], 400);
        }
    }

    /**
     * GET /api/stock-out/usage/:type
     * Get stock out by usage type
     */
    public function usage($type)
    {
        try {
            $start = $_GET['start_date'] ?? null;
            $end = $_GET['end_date'] ?? null;
            
            $dateRange = null;
            if ($start && $end) {
                $dateRange = ['start' => $start, 'end' => $end];
            }

            $stockOuts = $this->model->getByUsageType($type, $dateRange);

            Response::success('Data stock out berhasil diambil', ['data' => $stockOuts]);

        } catch (Exception $e) {
            Response::error('Gagal mengambil data', [], 400);
        }
    }

    /**
     * GET /api/stock-out/stats
     * Get statistics
     */
    public function stats()
    {
        try {
            $start = $_GET['start_date'] ?? null;
            $end = $_GET['end_date'] ?? null;
            
            $dateRange = null;
            if ($start && $end) {
                $dateRange = ['start' => $start, 'end' => $end];
            }

            $stats = $this->model->getStats($dateRange);

            Response::success('Statistik stock out', ['data' => $stats]);

        } catch (Exception $e) {
            Response::error('Gagal mengambil statistik', [], 500);
        }
    }

    /**
     * GET /api/stock-out/report
     * Get report for date range
     */
    public function report()
    {
        try {
            $start = $_GET['start_date'] ?? null;
            $end = $_GET['end_date'] ?? null;

            if (!$start || !$end) {
                Response::error('start_date dan end_date wajib diisi', [], 400);
                return;
            }

            $stockOuts = $this->model->getByDateRange($start, $end);

            Response::success('Laporan stock out', [
                'data' => $stockOuts,
                'period' => ['start' => $start, 'end' => $end],
                'total' => count($stockOuts)
            ]);

        } catch (Exception $e) {
            Response::error('Gagal mengambil laporan', [], 500);
        }
    }

    /**
     * DELETE /api/stock-out/:id
     * Delete stock out transaction and reverse stock
     */
    public function destroy($id)
    {
        try {
            if (!is_numeric($id) || $id < 1) {
                Response::error('ID tidak valid', [], 400);
                return;
            }

            $stockOut = $this->model->findById($id);

            if (!$stockOut) {
                Response::notFound('Stock out tidak ditemukan');
                return;
            }

            $deleted = $this->model->delete($id);

            if ($deleted) {
                $this->logActivity('delete', 'stock_out', $id, "Deleted stock out: {$stockOut['reference_number']}");
                Response::success('Stock out berhasil dihapus', []);
            } else {
                Response::error('Gagal menghapus stock out', [], 500);
            }

        } catch (Exception $e) {
            Response::error('Gagal menghapus stock out: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Log activity helper
     */
    private function logActivity($action, $entityType, $entityId, $description)
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO activity_logs 
                (user_id, action, entity_type, entity_id, description, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                Auth::id(),
                $action,
                $entityType,
                $entityId,
                $description,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (Exception $e) {
            // Silent fail
        }
    }
}
