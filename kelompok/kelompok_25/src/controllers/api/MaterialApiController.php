<?php

/**
 * Material API Controller
 * Menangani endpoint API untuk manajemen material
 */

class MaterialApiController
{
    private $materialModel;
    private $categoryModel;
    private $supplierModel;

    public function __construct()
    {
        $this->materialModel = new Material();
        $this->categoryModel = new Category();
        $this->supplierModel = new Supplier();
    }

    /**
     * GET /api/materials
     * Get all materials with pagination and filters
     */
    public function index()
    {
        try {
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;
            
            // Filters
            $filters = [];
            if (isset($_GET['category_id'])) {
                $filters['category_id'] = (int)$_GET['category_id'];
            }
            if (isset($_GET['supplier_id'])) {
                $filters['supplier_id'] = (int)$_GET['supplier_id'];
            }
            if (isset($_GET['stock_status'])) {
                $filters['stock_status'] = $_GET['stock_status'];
            }

            $materials = $this->materialModel->getAll($page, $perPage, $filters);
            $total = $this->materialModel->countAll($filters);

            // Add stock status to each material
            foreach ($materials as &$material) {
                $material['stock_status'] = $this->getStockStatusLabel($material);
                $material['stock_value'] = $material['current_stock'] * $material['unit_price'];
            }

            $this->logActivity('view', 'material', null, 'Viewed materials list');

            Response::success([
                'materials' => $materials,
                'pagination' => [
                    'total' => (int)$total,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'total_pages' => ceil($total / $perPage)
                ]
            ]);

        } catch (Exception $e) {
            Response::error('Failed to fetch materials: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/materials/:id
     * Get material detail
     */
    public function show($id)
    {
        try {
            $material = $this->materialModel->findById($id);

            if (!$material) {
                Response::error('Material not found', 404);
                return;
            }

            // Add additional info
            $material['stock_status'] = $this->getStockStatusLabel($material);
            $material['stock_value'] = $material['current_stock'] * $material['unit_price'];

            $this->logActivity('view', 'material', $id, "Viewed material: {$material['name']}");

            Response::success(['material' => $material]);

        } catch (Exception $e) {
            Response::error('Failed to fetch material: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/materials
     * Create new material
     */
    public function store()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            // Validation
            $errors = $this->validateMaterial($data);
            if (!empty($errors)) {
                Response::error('Validation failed', 422, ['errors' => $errors]);
                return;
            }

            // Check if code exists
            if ($this->materialModel->codeExists($data['code'])) {
                Response::error('Material code already exists', 422, [
                    'errors' => ['code' => 'Code already in use']
                ]);
                return;
            }

            // Check if category exists
            if (!$this->categoryModel->findById($data['category_id'])) {
                Response::error('Category not found', 422, [
                    'errors' => ['category_id' => 'Category does not exist']
                ]);
                return;
            }

            // Check if supplier exists (if provided)
            if (!empty($data['default_supplier_id'])) {
                if (!$this->supplierModel->findActive($data['default_supplier_id'])) {
                    Response::error('Supplier not found', 422, [
                        'errors' => ['default_supplier_id' => 'Supplier does not exist']
                    ]);
                    return;
                }
            }

            $materialId = $this->materialModel->create($data);

            if ($materialId) {
                $material = $this->materialModel->findById($materialId);
                
                $this->logActivity('create', 'material', $materialId, "Created material: {$data['name']}");
                
                Response::success([
                    'message' => 'Material created successfully',
                    'material' => $material
                ], 201);
            } else {
                Response::error('Failed to create material', 500);
            }

        } catch (Exception $e) {
            Response::error('Failed to create material: ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/materials/:id
     * Update material
     */
    public function update($id)
    {
        try {
            $material = $this->materialModel->findById($id);

            if (!$material) {
                Response::error('Material not found', 404);
                return;
            }

            $data = json_decode(file_get_contents('php://input'), true);

            // Validation
            $errors = $this->validateMaterial($data, $id);
            if (!empty($errors)) {
                Response::error('Validation failed', 422, ['errors' => $errors]);
                return;
            }

            // Check if code exists (except current)
            if (isset($data['code']) && $this->materialModel->codeExists($data['code'], $id)) {
                Response::error('Material code already exists', 422, [
                    'errors' => ['code' => 'Code already in use']
                ]);
                return;
            }

            // Check if category exists
            if (isset($data['category_id']) && !$this->categoryModel->findById($data['category_id'])) {
                Response::error('Category not found', 422, [
                    'errors' => ['category_id' => 'Category does not exist']
                ]);
                return;
            }

            // Check if supplier exists
            if (!empty($data['default_supplier_id']) && !$this->supplierModel->findActive($data['default_supplier_id'])) {
                Response::error('Supplier not found', 422, [
                    'errors' => ['default_supplier_id' => 'Supplier does not exist']
                ]);
                return;
            }

            $success = $this->materialModel->updateMaterial($id, $data);

            if ($success) {
                $updatedMaterial = $this->materialModel->findById($id);
                
                $this->logActivity('update', 'material', $id, "Updated material: {$updatedMaterial['name']}");
                
                Response::success([
                    'message' => 'Material updated successfully',
                    'material' => $updatedMaterial
                ]);
            } else {
                Response::error('Failed to update material', 500);
            }

        } catch (Exception $e) {
            Response::error('Failed to update material: ' . $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/materials/:id
     * Soft delete material
     */
    public function destroy($id)
    {
        try {
            $material = $this->materialModel->findById($id);

            if (!$material) {
                Response::error('Material not found', 404);
                return;
            }

            // Check if material has transactions
            if ($this->materialModel->hasTransactions($id)) {
                Response::error('Cannot delete material with existing transactions', 400, [
                    'message' => 'This material has stock transaction history and cannot be deleted'
                ]);
                return;
            }

            $success = $this->materialModel->delete($id);

            if ($success) {
                $this->logActivity('delete', 'material', $id, "Deleted material: {$material['name']}");
                
                Response::success(['message' => 'Material deleted successfully']);
            } else {
                Response::error('Failed to delete material', 500);
            }

        } catch (Exception $e) {
            Response::error('Failed to delete material: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/materials/search
     * Search materials
     */
    public function search()
    {
        try {
            $keyword = $_GET['q'] ?? '';
            
            if (empty($keyword)) {
                Response::error('Search keyword is required', 422);
                return;
            }

            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;

            $materials = $this->materialModel->search($keyword, $page, $perPage);
            $total = $this->materialModel->countSearch($keyword);

            // Add stock status
            foreach ($materials as &$material) {
                $material['stock_status'] = $this->getStockStatusLabel($material);
                $material['stock_value'] = $material['current_stock'] * $material['unit_price'];
            }

            $this->logActivity('search', 'material', null, "Searched materials: {$keyword}");

            Response::success([
                'materials' => $materials,
                'pagination' => [
                    'total' => (int)$total,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'total_pages' => ceil($total / $perPage)
                ],
                'keyword' => $keyword
            ]);

        } catch (Exception $e) {
            Response::error('Failed to search materials: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/materials/low-stock
     * Get low stock materials
     */
    public function lowStock()
    {
        try {
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;

            $materials = $this->materialModel->getLowStock($page, $perPage);
            $total = $this->materialModel->countLowStock();

            foreach ($materials as &$material) {
                $material['stock_status'] = $this->getStockStatusLabel($material);
                $material['stock_value'] = $material['current_stock'] * $material['unit_price'];
            }

            $this->logActivity('view', 'material', null, 'Viewed low stock materials');

            Response::success([
                'materials' => $materials,
                'pagination' => [
                    'total' => (int)$total,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'total_pages' => ceil($total / $perPage)
                ]
            ]);

        } catch (Exception $e) {
            Response::error('Failed to fetch low stock materials: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/materials/out-of-stock
     * Get out of stock materials
     */
    public function outOfStock()
    {
        try {
            $materials = $this->materialModel->getOutOfStock();

            foreach ($materials as &$material) {
                $material['stock_status'] = 'empty';
                $material['stock_value'] = 0;
            }

            $this->logActivity('view', 'material', null, 'Viewed out of stock materials');

            Response::success([
                'materials' => $materials,
                'total' => count($materials)
            ]);

        } catch (Exception $e) {
            Response::error('Failed to fetch out of stock materials: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/materials/stats
     * Get material statistics
     */
    public function stats()
    {
        try {
            $stats = $this->materialModel->getStats();

            $this->logActivity('view', 'material', null, 'Viewed material statistics');

            Response::success(['stats' => $stats]);

        } catch (Exception $e) {
            Response::error('Failed to fetch statistics: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/materials/category/:categoryId
     * Get materials by category
     */
    public function byCategory($categoryId)
    {
        try {
            $category = $this->categoryModel->findById($categoryId);

            if (!$category) {
                Response::error('Category not found', 404);
                return;
            }

            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;

            $materials = $this->materialModel->getByCategory($categoryId, $page, $perPage);
            $filters = ['category_id' => $categoryId];
            $total = $this->materialModel->countAll($filters);

            foreach ($materials as &$material) {
                $material['stock_status'] = $this->getStockStatusLabel($material);
                $material['stock_value'] = $material['current_stock'] * $material['unit_price'];
            }

            Response::success([
                'category' => $category,
                'materials' => $materials,
                'pagination' => [
                    'total' => (int)$total,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'total_pages' => ceil($total / $perPage)
                ]
            ]);

        } catch (Exception $e) {
            Response::error('Failed to fetch materials: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/materials/supplier/:supplierId
     * Get materials by supplier
     */
    public function bySupplier($supplierId)
    {
        try {
            $supplier = $this->supplierModel->findActive($supplierId);

            if (!$supplier) {
                Response::error('Supplier not found', 404);
                return;
            }

            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;

            $materials = $this->materialModel->getBySupplier($supplierId, $page, $perPage);
            $filters = ['supplier_id' => $supplierId];
            $total = $this->materialModel->countAll($filters);

            foreach ($materials as &$material) {
                $material['stock_status'] = $this->getStockStatusLabel($material);
                $material['stock_value'] = $material['current_stock'] * $material['unit_price'];
            }

            Response::success([
                'supplier' => $supplier,
                'materials' => $materials,
                'pagination' => [
                    'total' => (int)$total,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'total_pages' => ceil($total / $perPage)
                ]
            ]);

        } catch (Exception $e) {
            Response::error('Failed to fetch materials: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Validate material data
     */
    private function validateMaterial($data, $id = null)
    {
        $errors = [];

        // Code validation
        if (empty($data['code'])) {
            $errors['code'] = 'Material code is required';
        } elseif (!preg_match('/^[A-Z0-9\-]+$/', $data['code'])) {
            $errors['code'] = 'Code must contain only uppercase letters, numbers, and hyphens';
        } elseif (strlen($data['code']) < 2 || strlen($data['code']) > 20) {
            $errors['code'] = 'Code must be between 2 and 20 characters';
        }

        // Name validation
        if (empty($data['name'])) {
            $errors['name'] = 'Material name is required';
        } elseif (strlen($data['name']) < 3 || strlen($data['name']) > 100) {
            $errors['name'] = 'Name must be between 3 and 100 characters';
        }

        // Category validation
        if (empty($data['category_id'])) {
            $errors['category_id'] = 'Category is required';
        } elseif (!is_numeric($data['category_id']) || $data['category_id'] <= 0) {
            $errors['category_id'] = 'Invalid category ID';
        }

        // Unit validation
        if (empty($data['unit'])) {
            $errors['unit'] = 'Unit is required';
        } elseif (!in_array($data['unit'], ['pcs', 'kg', 'liter', 'meter', 'box', 'pack'])) {
            $errors['unit'] = 'Invalid unit. Allowed: pcs, kg, liter, meter, box, pack';
        }

        // Min stock validation
        if (!isset($data['min_stock'])) {
            $errors['min_stock'] = 'Minimum stock is required';
        } elseif (!is_numeric($data['min_stock']) || $data['min_stock'] < 0) {
            $errors['min_stock'] = 'Minimum stock must be a positive number';
        }

        // Current stock validation (only for create)
        if ($id === null && isset($data['current_stock'])) {
            if (!is_numeric($data['current_stock']) || $data['current_stock'] < 0) {
                $errors['current_stock'] = 'Current stock must be a positive number';
            }
        }

        // Unit price validation
        if (!isset($data['unit_price'])) {
            $errors['unit_price'] = 'Unit price is required';
        } elseif (!is_numeric($data['unit_price']) || $data['unit_price'] < 0) {
            $errors['unit_price'] = 'Unit price must be a positive number';
        }

        // Reorder point validation
        if (isset($data['reorder_point'])) {
            if (!is_numeric($data['reorder_point']) || $data['reorder_point'] < 0) {
                $errors['reorder_point'] = 'Reorder point must be a positive number';
            }
        }

        // Supplier validation (optional)
        if (!empty($data['default_supplier_id'])) {
            if (!is_numeric($data['default_supplier_id']) || $data['default_supplier_id'] <= 0) {
                $errors['default_supplier_id'] = 'Invalid supplier ID';
            }
        }

        return $errors;
    }

    /**
     * Get stock status label
     */
    private function getStockStatusLabel($material)
    {
        if ($material['current_stock'] == 0) {
            return 'empty';
        } elseif ($material['current_stock'] <= $material['min_stock']) {
            return 'low';
        } elseif ($material['current_stock'] <= $material['reorder_point']) {
            return 'warning';
        } else {
            return 'normal';
        }
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
            // Silent fail - logging should not break the main flow
            error_log("Failed to log activity: " . $e->getMessage());
        }
    }
}
