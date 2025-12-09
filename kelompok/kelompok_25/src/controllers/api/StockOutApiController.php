<?php
// api/StockOutApiController.php
// usage: include routing to point to methods below.
// Requires: Database::connect() returning PDO, session auth

require_once __DIR__ . '/../models/StockOut.php';

class StockOutApiController
{
    protected PDO $db;
    protected StockOut $model;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->model = new StockOut($this->db);
        // set JSON header in each response method or globally in router
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

    /**
     * GET /api/stock-out
     * supports query params: page, perPage, material_id, usage_type, start_date, end_date, q
     */
    public function index()
    {
        $this->ensureAuthenticated();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int)($_GET['perPage'] ?? 20)));
        $filters = [
            'material_id' => isset($_GET['material_id']) ? (int)$_GET['material_id'] : null,
            'usage_type' => $_GET['usage_type'] ?? null,
            'start_date' => $_GET['start_date'] ?? null,
            'end_date' => $_GET['end_date'] ?? null,
            'q' => $_GET['q'] ?? null
        ];

        try {
            $result = $this->model->getAll($page, $perPage, $filters);
            $this->json(['status' => true, 'data' => $result]);
        } catch (Exception $e) {
            $this->json(['status' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/stock-out/{id}
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
     * POST /api/stock-out
     * body: JSON with material_id, quantity, usage_type, transaction_date, destination (opt), notes (opt)
     */
    public function store()
    {
        $userId = $this->ensureAuthenticated();
        $input = $this->getRequestBody();

        // map/whitelist fields
        $payload = [
            'material_id' => isset($input['material_id']) ? (int)$input['material_id'] : null,
            'quantity' => isset($input['quantity']) ? (float)$input['quantity'] : null,
            'usage_type' => $input['usage_type'] ?? null,
            'transaction_date' => $input['transaction_date'] ?? null,
            'destination' => $input['destination'] ?? null,
            'notes' => $input['notes'] ?? null,
            'created_by' => $userId
        ];

        try {
            $created = $this->model->create($payload);
            $this->json(['status' => true, 'data' => $created], 201);
        } catch (Exception $e) {
            $this->json(['status' => false, 'error' => $e->getMessage()], 400);
        }
    }

    /**
     * GET /api/stock-out/material/{id}
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
     * GET /api/stock-out/usage/{type}
     */
    public function usage($type)
    {
        $this->ensureAuthenticated();
        $start = $_GET['start_date'] ?? null;
        $end = $_GET['end_date'] ?? null;
        try {
            $rows = $this->model->getByUsageType($type, ['start' => $start, 'end' => $end]);
            $this->json(['status' => true, 'data' => $rows]);
        } catch (Exception $e) {
            $this->json(['status' => false, 'error' => $e->getMessage()], 400);
        }
    }

    /**
     * GET /api/stock-out/report?start_date=YYYY-MM-DD&end_date=YYYY-MM-DD
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
            $rows = $this->model->getByDateRange($start, $end);
            $this->json(['status' => true, 'data' => $rows]);
        } catch (Exception $e) {
            $this->json(['status' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/stock-out/stats?start_date=&end_date=
     */
    public function stats()
    {
        $this->ensureAuthenticated();
        $start = $_GET['start_date'] ?? null;
        $end = $_GET['end_date'] ?? null;
        $range = null;
        if ($start && $end) {
            $range = ['start' => $start, 'end' => $end];
        }
        try {
            $stats = $this->model->getStats($range);
            $this->json(['status' => true, 'data' => $stats]);
        } catch (Exception $e) {
            $this->json(['status' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
