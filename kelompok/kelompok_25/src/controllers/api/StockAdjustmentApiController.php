<?php
// api/StockAdjustmentApiController.php
// Requires: Database::connect() -> PDO, models/StockAdjustment.php

require_once __DIR__ . '/../models/StockAdjustment.php';

class StockAdjustmentApiController
{
    protected PDO $db;
    protected StockAdjustment $model;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->model = new StockAdjustment($this->db);
        header('Content-Type: application/json; charset=utf-8');
    }

    protected function json($data, int $status = 200)
    {
        http_response_code($status);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function getRequestBody(): array
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return [];
        }
        return $data;
    }

    protected function ensureAuthenticated()
    {
        session_start();
        if (empty($_SESSION['user_id'])) {
            $this->json(['error' => 'Unauthorized'], 401);
        }
        return (int)$_SESSION['user_id'];
    }

    protected function ensureManager()
    {
        session_start();
        // Simple role check - adapt to your project (RoleMiddleware)
        if (empty($_SESSION['role']) || $_SESSION['role'] !== 'manager') {
            $this->json(['error' => 'Forbidden: manager approval required'], 403);
        }
        return true;
    }

    /**
     * GET /api/stock-adjustments
     * Query: page, perPage, material_id, reason, start_date, end_date, q
     */
    public function index()
    {
        $this->ensureAuthenticated();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int)($_GET['perPage'] ?? 20)));
        $filters = [
            'material_id' => isset($_GET['material_id']) ? (int)$_GET['material_id'] : null,
            'reason' => $_GET['reason'] ?? null,
            'start_date' => $_GET['start_date'] ?? null,
            'end_date' => $_GET['end_date'] ?? null,
            'q' => $_GET['q'] ?? null
        ];
        try {
            $res = $this->model->getAll($page, $perPage, $filters);
            $this->json(['status' => true, 'data' => $res]);
        } catch (Exception $e) {
            $this->json(['status' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/stock-adjustments/{id}
     */
    public function show($id)
    {
        $this->ensureAuthenticated();
        try {
            $row = $this->model->findById((int)$id);
            if (!$row) $this->json(['status' => false, 'error' => 'Not found'], 404);
            $this->json(['status' => true, 'data' => $row]);
        } catch (Exception $e) {
            $this->json(['status' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/stock-adjustments
     * Body: material_id, new_stock, reason, notes
     * Business rule: require manager approval (role = manager)
     */
    public function store()
    {
        $userId = $this->ensureAuthenticated();

        // require manager approval
        // If your flow is "submit then approve", remove this check and implement separate approve endpoint.
        $this->ensureManager();

        $input = $this->getRequestBody();
        $payload = [
            'material_id' => isset($input['material_id']) ? (int)$input['material_id'] : null,
            'new_stock' => isset($input['new_stock']) ? (float)$input['new_stock'] : null,
            'reason' => $input['reason'] ?? null,
            'notes' => $input['notes'] ?? null,
            'adjusted_by' => $userId
        ];

        try {
            $created = $this->model->create($payload);
            $this->json(['status' => true, 'data' => $created], 201);
        } catch (Exception $e) {
            $this->json(['status' => false, 'error' => $e->getMessage()], 400);
        }
    }

    /**
     * GET /api/stock-adjustments/material/{id}
     */
    public function material($materialId)
    {
        $this->ensureAuthenticated();
        $start = $_GET['start_date'] ?? null;
        $end = $_GET['end_date'] ?? null;
        try {
            $rows = $this->model->getByMaterial((int)$materialId, ['start' => $start, 'end' => $end]);
            $this->json(['status' => true, 'data' => $rows]);
        } catch (Exception $e) {
            $this->json(['status' => false, 'error' => $e->getMessage()], 400);
        }
    }

    /**
     * GET /api/stock-adjustments/report?start_date=&end_date=
     */
    public function report()
    {
        $this->ensureAuthenticated();
        $start = $_GET['start_date'] ?? null;
        $end = $_GET['end_date'] ?? null;
        if (!$start || !$end) {
            $this->json(['status' => false, 'error' => 'start_date and end_date required'], 400);
        }
        try {
            $rows = $this->model->getByMaterial(0, ['start' => $start, 'end' => $end]); // reuse if needed OR implement separate report method
            // Better: implement dedicated report - for now return adjustments in date range:
            $stmt = $this->db->prepare("SELECT sa.*, m.name as material_name, u.name as adjusted_by_name FROM stock_adjustments sa LEFT JOIN materials m ON m.id = sa.material_id LEFT JOIN users u ON u.id = sa.adjusted_by WHERE sa.adjusted_at BETWEEN :start AND :end ORDER BY sa.adjusted_at DESC");
            $stmt->execute([':start'=> $start . ' 00:00:00', ':end' => $end . ' 23:59:59']);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->json(['status' => true, 'data' => $data]);
        } catch (Exception $e) {
            $this->json(['status' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/stock-adjustments/reason/{reason}?start_date=&end_date=
     */
    public function reason($reason)
    {
        $this->ensureAuthenticated();
        $start = $_GET['start_date'] ?? null;
        $end = $_GET['end_date'] ?? null;
        try {
            $rows = $this->model->getByReason($reason, ['start' => $start, 'end' => $end]);
            $this->json(['status' => true, 'data' => $rows]);
        } catch (Exception $e) {
            $this->json(['status' => false, 'error' => $e->getMessage()], 400);
        }
    }

    /**
     * GET /api/stock-adjustments/stats?start_date=&end_date=
     */
    public function stats()
    {
        $this->ensureAuthenticated();
        $start = $_GET['start_date'] ?? null;
        $end = $_GET['end_date'] ?? null;
        $range = null;
        if ($start && $end) $range = ['start' => $start, 'end' => $end];
        try {
            $stats = $this->model->getStats($range);
            $this->json(['status' => true, 'data' => $stats]);
        } catch (Exception $e) {
            $this->json(['status' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
