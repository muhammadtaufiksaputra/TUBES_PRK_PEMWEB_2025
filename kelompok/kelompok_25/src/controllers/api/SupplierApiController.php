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
            $search = $_GET['search'] ?? '';

            $supplier = new Supplier();
            $suppliers = $supplier->getAllActive($page, $perPage, $search);
            $total = $supplier->countActive($search);
            $lastPage = ceil($total / $perPage);

            // Format: Match category response format for consistent AJAX handling
            $data = [
                'data' => $suppliers,
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage
            ];

            // Log activity
            $this->logActivity('view', 'supplier', 0, 'Viewed supplier list');

            Response::success('Data supplier berhasil diambil', $data);
        } catch (Exception $e) {
            Response::error('Gagal mengambil data supplier', ['error' => $e->getMessage()], 500);
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
                Response::notFound('Supplier tidak ditemukan');
            }

            $supplierData = $supplier->findActive($id);

            Response::success('Data supplier berhasil diambil', ['data' => $supplierData]);
        } catch (Exception $e) {
            Response::error('Gagal mengambil data supplier', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Create new supplier
     * POST /api/suppliers
     */
    public function store()
    {
        try {
            // Get JSON input
            $input = json_decode(file_get_contents('php://input'), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Response::error('Format JSON tidak valid', [], 400);
            }

            // Validate input
            $validator = Validator::make($input, [
                'name' => 'required|min:3|max:255',
                'contact_person' => 'required|min:2|max:100',
                'phone' => 'required|min:8|max:50',
                'email' => 'email|max:255',
                'address' => 'max:500'
            ]);

            if (!$validator->validate()) {
                Response::validationError($validator->errors(), 'Validasi gagal');
            }

            $validated = $validator->validated();
            $supplier = new Supplier();

            // Check if email already exists
            if (!empty($validated['email']) && $supplier->emailExists($validated['email'])) {
                Response::error('Email sudah terdaftar', [
                    'email' => ['Email sudah digunakan']
                ], 422);
            }

            // Create supplier
            $supplierId = $supplier->create($validated);
            $newSupplier = $supplier->findActive($supplierId);

            Response::created('Supplier berhasil ditambahkan', ['data' => $newSupplier]);
        } catch (Exception $e) {
            Response::error('Gagal menambahkan supplier', [], 500);
        }
    }

    /**
     * Update existing supplier
     * PUT /api/suppliers/{id}
     */
    public function update($id)
    {
        try {
            $id = intval($id);
            $supplier = new Supplier();

            if (!$supplier->exists($id)) {
                Response::notFound('Supplier tidak ditemukan');
            }

            // Get JSON input
            $input = json_decode(file_get_contents('php://input'), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Response::error('Format JSON tidak valid', [], 400);
            }

            // Validate input (all optional on update)
            $validator = Validator::make($input, [
                'name' => 'min:3|max:255',
                'contact_person' => 'min:2|max:100',
                'phone' => 'min:8|max:50',
                'email' => 'email|max:255',
                'address' => 'max:500'
            ]);

            if (!$validator->validate()) {
                Response::validationError($validator->errors(), 'Validasi gagal');
            }

            $validated = $validator->validated();

            // Check if email already exists (exclude current supplier)
            if (!empty($validated['email']) && $supplier->emailExists($validated['email'], $id)) {
                Response::error('Email sudah terdaftar', [
                    'email' => ['Email sudah digunakan']
                ], 422);
            }

            // Update supplier
            $supplier->updateSupplier($id, $validated);
            $updatedSupplier = $supplier->findActive($id);

            Response::success('Supplier berhasil diperbarui', ['data' => $updatedSupplier]);
        } catch (Exception $e) {
            Response::error('Gagal memperbarui supplier', [], 500);
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

            $id = intval($id);
            $supplier = new Supplier();

            if (!$supplier->exists($id)) {
                Response::notFound('Supplier tidak ditemukan');
            }

            // Soft delete supplier
            $supplier->softDelete($id);

            Response::success('Supplier berhasil dihapus', []);
        } catch (Exception $e) {
            Response::error('Gagal menghapus supplier', ['error' => $e->getMessage()], 500);
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

            $query = $_GET['search'] ?? '';
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $perPage = isset($_GET['per_page']) ? min(100, intval($_GET['per_page'])) : 10;

            $supplier = new Supplier();
            
            if (!$query) {
                // If no search query, return all active suppliers
                $suppliers = $supplier->getAllActive($page, $perPage);
                $total = $supplier->countActive();
            } else {
                // Search suppliers
                if (strlen($query) < 2) {
                    Response::error('Query pencarian minimal 2 karakter', [], 400);
                }
                $suppliers = $supplier->search($query, $page, $perPage);
                $total = $supplier->searchCount($query);
            }
            
            $lastPage = ceil($total / $perPage);

            $data = [
                'data' => $suppliers,
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage
            ];

            Response::success('Data supplier berhasil diambil', $data);
        } catch (Exception $e) {
            Response::error('Gagal mengambil data supplier', ['error' => $e->getMessage()], 500);
        }
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
