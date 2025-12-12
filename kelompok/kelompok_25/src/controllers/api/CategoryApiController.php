<?php

/**
 * Category API Controller
 * Handle API requests untuk Category Management
 */

require_once ROOT_PATH . '/core/Controller.php';
require_once ROOT_PATH . '/core/Response.php';
require_once ROOT_PATH . '/core/Auth.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/middleware/AuthMiddleware.php';
require_once ROOT_PATH . '/models/Category.php';
require_once ROOT_PATH . '/helpers/validation.php';

class CategoryApiController extends Controller
{
    private $categoryModel;

    public function __construct()
    {
        // Semua endpoint memerlukan authentication
        AuthMiddleware::check();
        
        $this->categoryModel = new Category();
    }

    /**
     * GET /api/categories - List all categories with pagination
     */
    public function index()
    {
        try {
            // Get pagination parameters
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;
            $search = $_GET['search'] ?? '';

            // Validate pagination
            if ($page < 1) $page = 1;
            if ($perPage < 1 || $perPage > 100) $perPage = 20;

            // Get data
            $categories = $this->categoryModel->getAll($page, $perPage, $search);
            
            // Add material count to each category
            foreach ($categories as &$category) {
                $category['material_count'] = $this->categoryModel->getMaterialCount($category['id']);
            }
            unset($category);
            
            $total = $this->categoryModel->countAll($search);
            $totalPages = ceil($total / $perPage);

            Response::success('Data kategori berhasil diambil', [
                'data' => $categories,
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $totalPages
            ]);

        } catch (Exception $e) {
            Response::error('Terjadi kesalahan saat mengambil data kategori', [], 500);
        }
    }

    /**
     * GET /api/categories/:id - Get category detail with material count
     */
    public function show($id)
    {
        try {
            // Validate ID
            if (!is_numeric($id) || $id < 1) {
                Response::error('ID kategori tidak valid', [], 400);
            }

            // Get category
            $category = $this->categoryModel->findById($id);

            if (!$category) {
                Response::notFound('Kategori tidak ditemukan');
            }

            // Add material count
            $category['material_count'] = $this->categoryModel->getMaterialCount($id);

            Response::success('Detail kategori', ['category' => $category]);

        } catch (Exception $e) {
            Response::error('Terjadi kesalahan saat mengambil detail kategori', [], 500);
        }
    }

    /**
     * POST /api/categories - Create new category
     */
    public function store()
    {
        try {
            // Get JSON input
            $input = json_decode(file_get_contents('php://input'), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Response::error('Invalid JSON format', [], 400);
            }

            // Validate input
            $validator = Validator::make($input, [
                'name' => 'required|min:2|max:100',
                'description' => 'max:500'
            ]);

            if (!$validator->validate()) {
                Response::validationError($validator->errors(), 'Validasi gagal');
            }

            $validated = $validator->validated();

            // Check unique name
            if ($this->categoryModel->exists($validated['name'])) {
                Response::error('Nama kategori sudah digunakan', [
                    'field' => 'name',
                    'message' => 'Kategori dengan nama ini sudah ada'
                ], 422);
            }

            // Create category
            $categoryId = $this->categoryModel->create($validated);

            // Log activity
            $this->logActivity('CREATE', 'categories', $categoryId, "Created category: {$validated['name']}");

            Response::created('Kategori berhasil ditambahkan', [
                'id' => $categoryId,
                'name' => $validated['name']
            ]);

        } catch (Exception $e) {
            Response::error('Terjadi kesalahan saat membuat kategori', [], 500);
        }
    }

    /**
     * POST /api/categories/:id - Update existing category
     */
    public function update($id)
    {
        try {
            // Validate ID
            if (!is_numeric($id) || $id < 1) {
                Response::error('ID kategori tidak valid', [], 400);
            }

            // Check if category exists
            $category = $this->categoryModel->findById($id);
            if (!$category) {
                Response::notFound('Kategori tidak ditemukan');
            }

            // Get JSON input
            $input = json_decode(file_get_contents('php://input'), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Response::error('Invalid JSON format', [], 400);
            }

            // Validate input
            $validator = Validator::make($input, [
                'name' => 'min:2|max:100',
                'description' => 'max:500'
            ]);

            if (!$validator->validate()) {
                Response::validationError($validator->errors(), 'Validasi gagal');
            }

            $validated = $validator->validated();

            // Check unique name (exclude current category)
            if (isset($validated['name']) && $this->categoryModel->exists($validated['name'], $id)) {
                Response::error('Nama kategori sudah digunakan', [
                    'field' => 'name',
                    'message' => 'Kategori dengan nama ini sudah ada'
                ], 422);
            }

            // Update category
            $updated = $this->categoryModel->updateCategory($id, $validated);

            if (!$updated) {
                Response::error('Tidak ada perubahan data', [], 400);
            }

            // Log activity
            $this->logActivity('UPDATE', 'categories', $id, "Updated category: {$category['name']}");

            Response::success('Kategori berhasil diperbarui', [
                'id' => $id,
                'updated_fields' => array_keys($validated)
            ]);

        } catch (Exception $e) {
            Response::error('Terjadi kesalahan saat memperbarui kategori', [], 500);
        }
    }

    /**
     * POST /api/categories/:id/delete - Delete category (if not used)
     */
    public function destroy($id)
    {
        try {
            // Validate ID
            if (!is_numeric($id) || $id < 1) {
                Response::error('ID kategori tidak valid', [], 400);
            }

            // Check if category exists
            $category = $this->categoryModel->findById($id);
            if (!$category) {
                Response::notFound('Kategori tidak ditemukan');
            }

            // Check if category is used by materials
            if ($this->categoryModel->isUsedByMaterials($id)) {
                Response::error('Kategori tidak dapat dihapus karena masih digunakan oleh material', [
                    'material_count' => $this->categoryModel->getMaterialCount($id)
                ], 400);
            }

            // Delete category
            $deleted = $this->categoryModel->delete($id);

            if (!$deleted) {
                Response::error('Gagal menghapus kategori', [], 500);
            }

            // Log activity
            $this->logActivity('DELETE', 'categories', $id, "Deleted category: {$category['name']}");

            Response::success('Kategori berhasil dihapus', [
                'id' => $id,
                'name' => $category['name']
            ]);

        } catch (Exception $e) {
            Response::error('Terjadi kesalahan saat menghapus kategori', [], 500);
        }
    }

    /**
     * GET /api/categories/search?q=keyword - Search categories
     */
    public function search()
    {
        try {
            // Get query parameter
            $keyword = isset($_GET['q']) ? trim($_GET['q']) : '';

            // Validate keyword
            if (empty($keyword)) {
                Response::error('Parameter pencarian (q) wajib diisi', [], 400);
            }

            if (strlen($keyword) < 2) {
                Response::error('Kata kunci pencarian minimal 2 karakter', [], 400);
            }

            // Get pagination parameters
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;

            if ($page < 1) $page = 1;
            if ($perPage < 1 || $perPage > 100) $perPage = 20;

            // Search
            $categories = $this->categoryModel->search($keyword, $page, $perPage);
            $total = $this->categoryModel->countSearch($keyword);
            $totalPages = ceil($total / $perPage);

            Response::success('Hasil pencarian kategori', [
                'keyword' => $keyword,
                'categories' => $categories,
                'pagination' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'total_pages' => $totalPages
                ]
            ]);

        } catch (Exception $e) {
            Response::error('Terjadi kesalahan saat mencari kategori', [], 500);
        }
    }

    /**
     * Private helper to log activity
     */
    private function logActivity($action, $entityType, $entityId, $description)
    {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address, user_agent, created_at) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
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
            // Silent fail - logging should not break the main flow
        }
    }
}
