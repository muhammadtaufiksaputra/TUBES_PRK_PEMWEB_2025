<?php

/**
 * Material API Controller
 * Menangani endpoint API untuk manajemen material
 */

require_once ROOT_PATH . '/core/Controller.php';
require_once ROOT_PATH . '/core/Response.php';
require_once ROOT_PATH . '/core/Auth.php';
require_once ROOT_PATH . '/models/Material.php';
require_once ROOT_PATH . '/models/Category.php';
require_once ROOT_PATH . '/models/MaterialImage.php';
require_once ROOT_PATH . '/helpers/validation.php';

class MaterialApiController extends Controller
{
    private $materialModel;
    private $categoryModel;
    private $materialImageModel;

    public function __construct()
    {
        AuthMiddleware::check();
        
        $this->materialModel = new Material();
        $this->categoryModel = new Category();
        $this->materialImageModel = new MaterialImage();
    }

    /**
     * GET /api/materials?page=1&per_page=10&search=&category_id=&status=
     * Get all materials with pagination
     */
    public function index()
    {
        try {
            AuthMiddleware::check();

            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $perPage = isset($_GET['per_page']) ? min(100, intval($_GET['per_page'])) : 10;
            $search = $_GET['search'] ?? '';
            $categoryId = $_GET['category_id'] ?? '';
            $status = $_GET['status'] ?? '';

            $materials = $this->materialModel->getAllActive($page, $perPage, $search, $categoryId, $status);
            $total = $this->materialModel->countActive($search, $categoryId, $status);
            $lastPage = $total > 0 ? ceil($total / $perPage) : 1;
            
            // Add stock status and primary image to each material
            if (is_array($materials)) {
                foreach ($materials as &$material) {
                    // Calculate stock status
                    $currentStock = floatval($material['current_stock'] ?? 0);
                    $minStock = floatval($material['min_stock'] ?? 0);
                    
                    if ($currentStock > $minStock) {
                        $material['stock_status'] = 'Aman';
                    } elseif ($currentStock > 0) {
                        $material['stock_status'] = 'Hampir Habis';
                    } else {
                        $material['stock_status'] = 'Habis';
                    }

                    // Get primary image (with error handling)
                    $material['image_url'] = null;
                    if (isset($material['id'])) {
                        try {
                            $primaryImage = $this->materialImageModel->getPrimaryImage($material['id']);
                            if ($primaryImage && isset($primaryImage['image_url'])) {
                                $material['image_url'] = $primaryImage['image_url'];
                            }
                        } catch (Exception $e) {
                            // Silently fail, image_url remains null
                        }
                    }
                }
            }

            Response::success('Data material berhasil diambil', [
                'data' => $materials ?: [],
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage
            ]);

        } catch (Exception $e) {
            error_log('Material index error: ' . $e->getMessage());
            Response::error('Gagal mengambil data material: ' . $e->getMessage(), [], 500);
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
                Response::error('Material tidak ditemukan', [], 404);
                return;
            }

            // Add stock status
            if ($material['current_stock'] > $material['min_stock']) {
                $material['stock_status'] = 'Aman';
            } elseif ($material['current_stock'] > 0) {
                $material['stock_status'] = 'Hampir Habis';
            } else {
                $material['stock_status'] = 'Habis';
            }

            // Format supplier as nested object
            if ($material['supplier_name']) {
                $material['supplier'] = [
                    'id' => $material['supplier_id'],
                    'name' => $material['supplier_name'],
                    'contact_person' => $material['supplier_contact_person'],
                    'phone' => $material['supplier_phone'],
                    'email' => $material['supplier_email'],
                    'address' => $material['supplier_address']
                ];
            } else {
                $material['supplier'] = null;
            }

            // Remove flat supplier fields
            unset($material['supplier_id']);
            unset($material['supplier_name']);
            unset($material['supplier_contact_person']);
            unset($material['supplier_phone']);
            unset($material['supplier_email']);
            unset($material['supplier_address']);

            Response::success('Detail material berhasil diambil', [
                'data' => $material
            ]);

        } catch (Exception $e) {
            Response::error('Gagal mengambil detail material', [], 500);
        }
    }

    /**
     * GET /api/materials/report/stock
     * Get stock report
     */
    public function stockReport()
    {
        try {
            $search = $_GET['search'] ?? '';
            $categoryFilter = $_GET['category'] ?? '';
            $statusFilter = $_GET['status'] ?? '';

            $materials = $this->materialModel->getStockReport($search, $categoryFilter, $statusFilter);

            Response::success('Laporan stok berhasil diambil', [
                'data' => $materials,
                'total' => count($materials)
            ]);

        } catch (Exception $e) {
            Response::error('Gagal mengambil laporan stok', [], 500);
        }
    }

    /**
     * GET /api/materials/summary
     * Get stock summary
     */
    public function summary()
    {
        try {
            $summary = $this->materialModel->getStockSummary();

            Response::success('Ringkasan stok berhasil diambil', [
                'data' => $summary
            ]);

        } catch (Exception $e) {
            Response::error('Gagal mengambil ringkasan stok', [], 500);
        }
    }

    /**
     * GET /api/materials/categories
     * Get available categories for materials
     */
    public function categories()
    {
        try {
            $categories = $this->materialModel->getCategories();

            Response::success('Daftar kategori berhasil diambil', [
                'data' => $categories,
                'total' => count($categories)
            ]);

        } catch (Exception $e) {
            Response::error('Gagal mengambil daftar kategori', [], 500);
        }
    }

    /**
     * POST /api/materials
     * Create new material
     */
    public function store()
    {
        try {
            AuthMiddleware::check();

            $input = json_decode(file_get_contents('php://input'), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                Response::error('Format JSON tidak valid', [], 400);
                return;
            }

            $validator = Validator::make($input, [
                'name' => 'required|min:3|max:255',
                'category_id' => 'required|numeric',
                'unit' => 'required|max:50',
                'min_stock' => 'required|numeric|min:0',
                'code' => 'max:50',
                'default_supplier_id' => 'numeric',
                'current_stock' => 'numeric|min:0'
            ]);

            if (!$validator->validate()) {
                Response::validationError($validator->errors(), 'Validasi gagal');
                return;
            }

            $validated = $validator->validated();

            // Check if category exists
            $category = $this->categoryModel->findById($validated['category_id']);
            if (!$category) {
                Response::error('Kategori tidak ditemukan', ['category_id' => ['Kategori tidak valid']], 422);
                return;
            }

            // Create material
            $materialId = $this->materialModel->create($validated);
            $material = $this->materialModel->findById($materialId);

            Response::created('Material berhasil ditambahkan', $material);

        } catch (Exception $e) {
            Response::error('Terjadi kesalahan: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * POST /api/materials/:id
     * Update material
     */
    public function update($id)
    {
        try {
            AuthMiddleware::check();

            $id = intval($id);

            // Check if material exists
            if (!$this->materialModel->exists($id)) {
                Response::notFound('Material tidak ditemukan');
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                Response::error('Format JSON tidak valid', [], 400);
                return;
            }

            $validator = Validator::make($input, [
                'name' => 'min:3|max:255',
                'category_id' => 'numeric',
                'unit' => 'max:50',
                'min_stock' => 'numeric|min:0',
                'code' => 'max:50',
                'default_supplier_id' => 'numeric',
                'current_stock' => 'numeric|min:0'
            ]);

            if (!$validator->validate()) {
                Response::validationError($validator->errors(), 'Validasi gagal');
                return;
            }

            $validated = $validator->validated();

            // Check if category exists (if category_id is provided)
            if (isset($validated['category_id'])) {
                $category = $this->categoryModel->findById($validated['category_id']);
                if (!$category) {
                    Response::error('Kategori tidak ditemukan', ['category_id' => ['Kategori tidak valid']], 422);
                    return;
                }
            }

            // Update material
            $this->materialModel->updateMaterial($id, $validated);
            $material = $this->materialModel->findById($id);

            Response::success('Material berhasil diperbarui', $material);

        } catch (Exception $e) {
            Response::error('Terjadi kesalahan: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * POST /api/materials/:id/delete
     * Delete material (soft delete)
     */
    public function destroy($id)
    {
        try {
            AuthMiddleware::check();

            $id = intval($id);

            // Check if material exists
            if (!$this->materialModel->exists($id)) {
                Response::notFound('Material tidak ditemukan');
                return;
            }

            // Soft delete material
            $this->materialModel->softDelete($id);

            Response::success('Material berhasil dihapus', []);

        } catch (Exception $e) {
            Response::error('Terjadi kesalahan: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * GET /api/materials/search
     * Search materials
     */
    public function search()
    {
        try {
            $search = $_GET['search'] ?? '';
            $categoryId = $_GET['category_id'] ?? '';
            $status = $_GET['status'] ?? '';

            $materials = $this->materialModel->getStockReport($search, $categoryId, $status);

            foreach ($materials as &$material) {
                if ($material['current_stock'] > $material['min_stock']) {
                    $material['stock_status'] = 'Aman';
                } elseif ($material['current_stock'] > 0) {
                    $material['stock_status'] = 'Hampir Habis';
                } else {
                    $material['stock_status'] = 'Habis';
                }
            }

            Response::success('Hasil pencarian material', [
                'data' => $materials,
                'total' => count($materials)
            ]);

        } catch (Exception $e) {
            Response::error('Gagal melakukan pencarian', [], 500);
        }
    }
}
