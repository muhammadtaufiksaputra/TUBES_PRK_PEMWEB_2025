<?php

/**
 * Supplier API Controller
 */

require_once ROOT_PATH . '/core/Controller.php';
require_once ROOT_PATH . '/core/Response.php';
require_once ROOT_PATH . '/models/Supplier.php';
require_once ROOT_PATH . '/helpers/validation.php';

class SupplierApiController extends Controller
{

    /**
     * Get all suppliers with pagination
     * GET /api/suppliers?page=1&per_page=10
     */
    public function index()
    {
        try {
            AuthMiddleware::check();

            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $perPage = isset($_GET['per_page']) ? min(100, intval($_GET['per_page'])) : 10;

            $supplier = new Supplier();
            $suppliers = $supplier->getAllActive($page, $perPage);
            $total = $supplier->countActive();
            $lastPage = ceil($total / $perPage);

            $data = [
                'suppliers' => $suppliers,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => $lastPage
                ]
            ];

            // Log activity
            $this->logActivity('view', 'supplier', 0, 'Viewed supplier list');

            Response::success('Suppliers retrieved successfully', $data);
        } catch (Exception $e) {
            Response::error('Failed to retrieve suppliers', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get single supplier detail
     * GET /api/suppliers/{id}
     */
    public function show($id)
    {
        try {
            AuthMiddleware::check();

            $id = intval($id);
            $supplier = new Supplier();
            
            if (!$supplier->exists($id)) {
                Response::notFound('Supplier not found');
            }

            $supplierData = $supplier->findWithMaterialCount($id);

            Response::success('Supplier retrieved successfully', ['supplier' => $supplierData]);
        } catch (Exception $e) {
            Response::error('Failed to retrieve supplier', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Create new supplier
     * POST /api/suppliers
     */
    public function store()
    {
        try {
            AuthMiddleware::check();
            RoleMiddleware::staff();

            $input = $this->getJsonInput();

            // Validate input
            $rules = [
                'name' => 'required|min:3|max:255',
                'contact_person' => 'max:100',
                'phone' => 'max:50',
                'email' => 'email|max:255',
                'address' => 'max:500'
            ];

            $validator = new Validator($input, $rules);
            if (!$validator->validate()) {
                Response::validationError($validator->errors(), 'Validation failed');
            }

            $supplier = new Supplier();

            // Check if email already exists
            if (!empty($input['email']) && $supplier->emailExists($input['email'])) {
                Response::error('Email already exists', ['email' => ['Email sudah terdaftar.']], 422);
            }

            // Prepare data
            $data = [
                'name' => trim($input['name']),
                'contact_person' => trim($input['contact_person'] ?? ''),
                'phone' => trim($input['phone'] ?? ''),
                'email' => trim($input['email'] ?? ''),
                'address' => trim($input['address'] ?? ''),
                'is_active' => isset($input['is_active']) ? (bool) $input['is_active'] : true
            ];

            // Create supplier
            $supplierId = $supplier->create($data);
            $newSupplier = $supplier->find($supplierId);

            Response::created('Supplier created successfully', ['supplier' => $newSupplier]);
        } catch (Exception $e) {
            Response::error('Failed to create supplier', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update existing supplier
     * PUT /api/suppliers/{id}
     */
    public function update($id)
    {
        try {
            AuthMiddleware::check();
            RoleMiddleware::staff();

            $id = intval($id);
            $supplier = new Supplier();

            if (!$supplier->exists($id)) {
                Response::notFound('Supplier not found');
            }

            $input = $this->getJsonInput();

            // Validate input (all optional on update)
            $rules = [
                'name' => 'min:3|max:255',
                'contact_person' => 'max:100',
                'phone' => 'max:50',
                'email' => 'email|max:255',
                'address' => 'max:500'
            ];

            $validator = new Validator($input, $rules);
            if (!$validator->validate()) {
                Response::validationError($validator->errors(), 'Validation failed');
            }

            // Check if email already exists (exclude current supplier)
            if (!empty($input['email']) && $supplier->emailExists($input['email'], $id)) {
                Response::error('Email already exists', ['email' => ['Email sudah terdaftar.']], 422);
            }

            // Prepare update data (only non-empty fields)
            $data = [];
            
            if (isset($input['name']) && !empty($input['name'])) {
                $data['name'] = trim($input['name']);
            }
            
            if (isset($input['contact_person'])) {
                $data['contact_person'] = trim($input['contact_person']);
            }
            
            if (isset($input['phone'])) {
                $data['phone'] = trim($input['phone']);
            }
            
            if (isset($input['email'])) {
                $data['email'] = trim($input['email']);
            }
            
            if (isset($input['address'])) {
                $data['address'] = trim($input['address']);
            }
            
            if (isset($input['is_active'])) {
                $data['is_active'] = (bool) $input['is_active'];
            }

            // Update supplier
            $supplier->updateSupplier($id, $data);
            $updatedSupplier = $supplier->find($id);

            Response::success('Supplier updated successfully', ['supplier' => $updatedSupplier]);
        } catch (Exception $e) {
            Response::error('Failed to update supplier', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete supplier (soft delete)
     * DELETE /api/suppliers/{id}
     */
    public function destroy($id)
    {
        try {
            AuthMiddleware::check();
            RoleMiddleware::manager();

            $id = intval($id);
            $supplier = new Supplier();

            if (!$supplier->exists($id)) {
                Response::notFound('Supplier not found');
            }

            // Check if supplier is used in materials
            $supplierData = $supplier->findWithMaterialCount($id);
            if ($supplierData->material_count > 0) {
                Response::error(
                    'Cannot delete supplier',
                    ['error' => 'Supplier is used in ' . $supplierData->material_count . ' material(s).'],
                    409
                );
            }

            // Soft delete supplier
            $supplier->softDelete($id);

            Response::success('Supplier deleted successfully', []);
        } catch (Exception $e) {
            Response::error('Failed to delete supplier', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Search suppliers
     * GET /api/suppliers/search?q=query&page=1&per_page=10
     */
    public function search()
    {
        try {
            AuthMiddleware::check();

            $query = $_GET['q'] ?? '';
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $perPage = isset($_GET['per_page']) ? min(100, intval($_GET['per_page'])) : 10;

            if (strlen($query) < 2) {
                Response::error('Search query must be at least 2 characters', [], 400);
            }

            $supplier = new Supplier();
            $suppliers = $supplier->search($query, $page, $perPage);
            $total = $supplier->searchCount($query);
            $lastPage = ceil($total / $perPage);

            $data = [
                'suppliers' => $suppliers,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => $lastPage
                ],
                'query' => $query
            ];

            Response::success('Search completed successfully', $data);
        } catch (Exception $e) {
            Response::error('Search failed', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get helper to retrieve JSON input
     */
    private function getJsonInput()
    {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?? [];
    }

    /**
     * Log activity
     */
    private function logActivity($action, $entityType, $entityId, $description)
    {
        // TODO: Implement activity logging once ActivityLog model is ready
        // For now, this method is a placeholder
    }
}
