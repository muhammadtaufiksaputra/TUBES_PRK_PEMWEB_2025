<?php

/**
 * StockIn API Controller
 * Menangani endpoint API untuk transaksi stok masuk
 */

require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/middleware/AuthMiddleware.php';
require_once ROOT_PATH . '/core/Controller.php';
require_once ROOT_PATH . '/core/Response.php';
require_once ROOT_PATH . '/models/StockIn.php';
require_once ROOT_PATH . '/models/Material.php';
require_once ROOT_PATH . '/models/Supplier.php';

class StockInApiController extends Controller
{
    private $db;
    private $stockInModel;
    private $materialModel;
    private $supplierModel;

    public function __construct()
    {
        AuthMiddleware::check();
        $this->db = Database::getInstance()->getConnection();
        $this->stockInModel = new StockIn();
        $this->materialModel = new Material();
        $this->supplierModel = new Supplier();
    }

    /**
     * GET /api/stock-in
     * Get all stock in transactions with pagination and filters
     */
    public function index()
    {
        try {
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;
            
            // Filters
            $filters = [];
            if (isset($_GET['material_id'])) {
                $filters['material_id'] = (int)$_GET['material_id'];
            }
            if (isset($_GET['supplier_id'])) {
                $filters['supplier_id'] = (int)$_GET['supplier_id'];
            }
            // Support both start_date/end_date (from JS) and date_from/date_to
            if (isset($_GET['start_date']) || isset($_GET['date_from'])) {
                $filters['date_from'] = $_GET['start_date'] ?? $_GET['date_from'];
            }
            if (isset($_GET['end_date']) || isset($_GET['date_to'])) {
                $filters['date_to'] = $_GET['end_date'] ?? $_GET['date_to'];
            }
            if (isset($_GET['search'])) {
                $filters['search'] = $_GET['search'];
            }
            if (isset($_GET['reference_number'])) {
                $filters['reference_number'] = $_GET['reference_number'];
            }

            $transactions = $this->stockInModel->getAll($page, $perPage, $filters);
            $total = $this->stockInModel->countAll($filters);

            $this->logActivity('view', 'stock_in', null, 'Viewed stock in transactions');

            Response::success('Data retrieved successfully', [
                'data' => $transactions,
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => (int)$total,
                'last_page' => ceil($total / $perPage)
            ]);

        } catch (Exception $e) {
            Response::error('Failed to fetch transactions: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/stock-in/:id
     * Get transaction detail
     */
    public function show($id)
    {
        try {
            $transaction = $this->stockInModel->findById($id);

            if (!$transaction) {
                Response::error('Transaction not found', [], 404);
                return;
            }

            $this->logActivity('view', 'stock_in', $id, "Viewed stock in: {$transaction['reference_number']}");

            Response::success('Detail retrieved successfully', $transaction);

        } catch (Exception $e) {
            Response::error('Failed to fetch transaction: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/stock-in
     * Create new stock in transaction
     */
    public function store()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            // Validation
            $errors = $this->validateStockIn($data);
            if (!empty($errors)) {
                Response::error('Validation failed', 422, ['errors' => $errors]);
                return;
            }

            // Check if material exists
            $material = $this->materialModel->findById($data['material_id']);
            if (!$material) {
                Response::error('Material not found', 422, [
                    'errors' => ['material_id' => 'Material does not exist']
                ]);
                return;
            }

            // Check if supplier exists
            $supplier = $this->supplierModel->findActive($data['supplier_id']);
            if (!$supplier) {
                Response::error('Supplier not found', 422, [
                    'errors' => ['supplier_id' => 'Supplier does not exist']
                ]);
                return;
            }

            // Generate reference number if not provided
            if (empty($data['reference_number'])) {
                $data['reference_number'] = $this->stockInModel->generateReferenceNumber();
            } else {
                // Check if reference number exists
                if ($this->stockInModel->referenceExists($data['reference_number'])) {
                    Response::error('Reference number already exists', 422, [
                        'errors' => ['reference_number' => 'Reference number already in use']
                    ]);
                    return;
                }
            }

            // Calculate total price
            $data['total_price'] = $data['quantity'] * $data['unit_price'];

            // Add creator user ID
            $data['created_by'] = Auth::id();

            // Begin transaction
            $this->stockInModel->beginTransaction();

            try {
                // Create stock in record
                $transactionId = $this->stockInModel->create($data);

                if (!$transactionId) {
                    throw new Exception('Failed to create transaction record');
                }

                // Update material stock
                $stockUpdated = $this->materialModel->updateStock(
                    $data['material_id'], 
                    $data['quantity'], 
                    'add'
                );

                if (!$stockUpdated) {
                    throw new Exception('Failed to update material stock');
                }

                // Commit transaction
                $this->stockInModel->commit();

                $transaction = $this->stockInModel->findById($transactionId);

                $this->logActivity('create', 'stock_in', $transactionId, 
                    "Created stock in: {$data['reference_number']} - {$material['name']} ({$data['quantity']} {$material['unit']})");

                Response::created('Stock in transaction created successfully', $transaction);

            } catch (Exception $e) {
                $this->stockInModel->rollback();
                throw $e;
            }

        } catch (Exception $e) {
            Response::error('Failed to create transaction: ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/stock-in/:id
     * Update stock in transaction (limited fields)
     */
    public function update($id)
    {
        try {
            $transaction = $this->stockInModel->findById($id);

            if (!$transaction) {
                Response::error('Transaction not found', 404);
                return;
            }

            $data = json_decode(file_get_contents('php://input'), true);

            // Only allow updating certain fields (not quantity or material)
            $allowedFields = ['supplier_id', 'transaction_date', 'note'];
            $updateData = [];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }
            
            // Map notes/invoice_number to note for backward compatibility
            if (isset($data['notes']) || isset($data['invoice_number'])) {
                $updateData['note'] = $data['notes'] ?? $data['invoice_number'] ?? null;
            }

            if (empty($updateData)) {
                Response::error('No valid fields to update', 422);
                return;
            }

            // Validate supplier if changed
            if (isset($updateData['supplier_id'])) {
                if (!$this->supplierModel->findActive($updateData['supplier_id'])) {
                    Response::error('Supplier not found', 422, [
                        'errors' => ['supplier_id' => 'Supplier does not exist']
                    ]);
                    return;
                }
            }

            // Validate date format if provided
            if (isset($updateData['transaction_date'])) {
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $updateData['transaction_date'])) {
                    Response::error('Invalid date format', 422, [
                        'errors' => ['transaction_date' => 'Date must be in YYYY-MM-DD format']
                    ]);
                    return;
                }
            }

            $success = $this->stockInModel->updateStockIn($id, $updateData);

            if ($success) {
                $updatedTransaction = $this->stockInModel->findById($id);
                
                $this->logActivity('update', 'stock_in', $id, "Updated stock in: {$transaction['reference_number']}");
                
                Response::success([
                    'message' => 'Transaction updated successfully',
                    'transaction' => $updatedTransaction
                ]);
            } else {
                Response::error('Failed to update transaction', 500);
            }

        } catch (Exception $e) {
            Response::error('Failed to update transaction: ' . $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/stock-in/:id
     * Delete stock in transaction and reverse stock
     */
    public function destroy($id)
    {
        try {
            $transaction = $this->stockInModel->findById($id);

            if (!$transaction) {
                Response::error('Transaction not found', 404);
                return;
            }

            // Check current stock before reversing
            $currentStock = $this->materialModel->getCurrentStock($transaction['material_id']);
            
            if ($currentStock < $transaction['quantity']) {
                Response::error('Cannot delete transaction: insufficient stock to reverse', 400, [
                    'message' => 'Current stock is less than transaction quantity. Cannot reverse this transaction.',
                    'current_stock' => $currentStock,
                    'transaction_quantity' => $transaction['quantity']
                ]);
                return;
            }

            // Begin transaction
            $this->stockInModel->beginTransaction();

            try {
                // Reverse material stock
                $stockUpdated = $this->materialModel->updateStock(
                    $transaction['material_id'], 
                    $transaction['quantity'], 
                    'subtract'
                );

                if (!$stockUpdated) {
                    throw new Exception('Failed to reverse material stock');
                }

                // Delete transaction record
                $deleted = $this->stockInModel->delete($id);

                if (!$deleted) {
                    throw new Exception('Failed to delete transaction record');
                }

                // Commit transaction
                $this->stockInModel->commit();

                $this->logActivity('delete', 'stock_in', $id, 
                    "Deleted stock in: {$transaction['reference_number']} - {$transaction['material_name']}");

                Response::success(['message' => 'Transaction deleted successfully']);

            } catch (Exception $e) {
                $this->stockInModel->rollback();
                throw $e;
            }

        } catch (Exception $e) {
            Response::error('Failed to delete transaction: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/stock-in/today
     * Get today's transactions
     */
    public function today()
    {
        try {
            $transactions = $this->stockInModel->getToday();

            $this->logActivity('view', 'stock_in', null, "Viewed today's stock in transactions");

            Response::success([
                'transactions' => $transactions,
                'total' => count($transactions)
            ]);

        } catch (Exception $e) {
            Response::error('Failed to fetch transactions: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/stock-in/stats
     * Get statistics
     */
    public function stats()
    {
        try {
            $dateFrom = $_GET['date_from'] ?? null;
            $dateTo = $_GET['date_to'] ?? null;

            $stats = $this->stockInModel->getStats($dateFrom, $dateTo);

            $this->logActivity('view', 'stock_in', null, 'Viewed stock in statistics');

            Response::success(['stats' => $stats]);

        } catch (Exception $e) {
            Response::error('Failed to fetch statistics: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/stock-in/top-materials
     * Get top materials
     */
    public function topMaterials()
    {
        try {
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $dateFrom = $_GET['date_from'] ?? null;
            $dateTo = $_GET['date_to'] ?? null;

            $materials = $this->stockInModel->getTopMaterials($limit, $dateFrom, $dateTo);

            Response::success(['materials' => $materials]);

        } catch (Exception $e) {
            Response::error('Failed to fetch top materials: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/stock-in/top-suppliers
     * Get top suppliers
     */
    public function topSuppliers()
    {
        try {
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $dateFrom = $_GET['date_from'] ?? null;
            $dateTo = $_GET['date_to'] ?? null;

            $suppliers = $this->stockInModel->getTopSuppliers($limit, $dateFrom, $dateTo);

            Response::success(['suppliers' => $suppliers]);

        } catch (Exception $e) {
            Response::error('Failed to fetch top suppliers: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/stock-in/monthly/:year
     * Get monthly summary
     */
    public function monthly($year)
    {
        try {
            if (!is_numeric($year) || $year < 2000 || $year > 2100) {
                Response::error('Invalid year', 422);
                return;
            }

            $summary = $this->stockInModel->getMonthlySummary($year);

            Response::success([
                'year' => (int)$year,
                'summary' => $summary
            ]);

        } catch (Exception $e) {
            Response::error('Failed to fetch monthly summary: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Validate stock in data
     */
    private function validateStockIn($data)
    {
        $errors = [];

        // Material validation
        if (empty($data['material_id'])) {
            $errors['material_id'] = 'Material is required';
        } elseif (!is_numeric($data['material_id']) || $data['material_id'] <= 0) {
            $errors['material_id'] = 'Invalid material ID';
        }

        // Supplier validation
        if (empty($data['supplier_id'])) {
            $errors['supplier_id'] = 'Supplier is required';
        } elseif (!is_numeric($data['supplier_id']) || $data['supplier_id'] <= 0) {
            $errors['supplier_id'] = 'Invalid supplier ID';
        }

        // Quantity validation
        if (!isset($data['quantity'])) {
            $errors['quantity'] = 'Quantity is required';
        } elseif (!is_numeric($data['quantity']) || $data['quantity'] <= 0) {
            $errors['quantity'] = 'Quantity must be greater than 0';
        }

        // Unit price validation
        if (!isset($data['unit_price'])) {
            $errors['unit_price'] = 'Unit price is required';
        } elseif (!is_numeric($data['unit_price']) || $data['unit_price'] < 0) {
            $errors['unit_price'] = 'Unit price must be a positive number';
        }

        // Transaction date validation
        if (empty($data['transaction_date'])) {
            $errors['transaction_date'] = 'Transaction date is required';
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['transaction_date'])) {
            $errors['transaction_date'] = 'Date must be in YYYY-MM-DD format';
        } elseif (strtotime($data['transaction_date']) > time()) {
            $errors['transaction_date'] = 'Transaction date cannot be in the future';
        }

        // Reference number validation (if provided)
        if (!empty($data['reference_number'])) {
            if (!preg_match('/^[A-Z0-9\-]+$/', $data['reference_number'])) {
                $errors['reference_number'] = 'Reference number must contain only uppercase letters, numbers, and hyphens';
            }
        }

        return $errors;
    }

    /**
     * Log activity
     */
    private function logActivity($action, $entity, $entityId, $description)
    {
        try {
            $userId = Auth::id();
            
            $sql = "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
            
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare($sql);
            $stmt->execute([$userId, $action, $entity, $entityId, $description]);
        } catch (Exception $e) {
            error_log("Failed to log activity: " . $e->getMessage());
        }
    }
}
