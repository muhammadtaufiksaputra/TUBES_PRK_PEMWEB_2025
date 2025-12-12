<?php

/**
 * User API Controller
 */

require_once ROOT_PATH . '/core/Controller.php';
require_once ROOT_PATH . '/core/Response.php';
require_once ROOT_PATH . '/core/Auth.php';
require_once ROOT_PATH . '/helpers/validation.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/middleware/AuthMiddleware.php';
require_once ROOT_PATH . '/models/User.php';
require_once ROOT_PATH . '/models/Role.php';
require_once ROOT_PATH . '/models/ActivityLog.php';

class UserApiController extends Controller
{
    private $userModel;
    private $roleModel;

    public function __construct()
    {
        AuthMiddleware::check();
        $this->userModel = new User();
        $this->roleModel = new Role();
    }

    /**
     * GET /api/users
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
            if (isset($_GET['is_active'])) {
                $filters['is_active'] = (int)$_GET['is_active'] === 1;
            }
            if (isset($_GET['role_id'])) {
                $filters['role_id'] = (int)$_GET['role_id'];
            }

            $result = $this->userModel->getPaginated($page, $perPage, $filters);

            Response::success('Users retrieved successfully', $result);
        } catch (Exception $e) {
            Response::error('Failed to fetch users: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * GET /api/users/{id}
     */
    public function show($id)
    {
        $user = $this->userModel->findWithRoles($id);

        if (!$user) {
            Response::notFound('User not found');
        }

        $user['role_ids'] = !empty($user['role_ids']) ? array_map('intval', explode(',', $user['role_ids'])) : [];

        Response::success('User detail retrieved', $user);
    }

    /**
     * POST /api/users
     */
    public function store()
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        $validator = Validator::make($input, [
            'name' => 'required|min:3|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role_id' => 'exists:roles,id'
        ]);

        if (!$validator->validate()) {
            Response::validationError($validator->errors());
        }

        $validated = $validator->validated();

        $roleId = $validated['role_id'] ?? null;
        if (!$roleId) {
            $defaultRole = $this->roleModel->findByCode('staff');
            $roleId = $defaultRole['id'] ?? null;
        }

        if ($roleId && !$this->roleModel->find($roleId)) {
            Response::validationError(['role_id' => ['Role tidak ditemukan.']]);
        }

        $userData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'is_active' => isset($input['is_active']) ? (int)$input['is_active'] : 1,
            'avatar_url' => $input['avatar_url'] ?? null
        ];

        $this->userModel->beginTransaction();

        try {
            $userId = $this->userModel->create($userData);

            if ($roleId) {
                $this->userModel->assignRole($userId, $roleId, true);
            }

            $this->logActivity('create', 'user', $userId, "Created user {$validated['email']}");

            $this->userModel->commit();
        } catch (Exception $e) {
            $this->userModel->rollback();
            Response::error('Failed to create user: ' . $e->getMessage(), [], 500);
        }

        $user = $this->userModel->findWithRoles($userId);
        Response::created('User created successfully', $user);
    }

    /**
     * POST /api/users/{id}
     */
    public function update($id)
    {
        $existing = $this->userModel->find($id);
        if (!$existing) {
            Response::notFound('User not found');
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        $validator = Validator::make($input, [
            'name' => 'required|min:3|max:100',
            'email' => 'required|email|unique:users,email,' . $id,
            'password' => 'min:6',
            'role_id' => 'exists:roles,id'
        ]);

        if (!$validator->validate()) {
            Response::validationError($validator->errors());
        }

        $validated = $validator->validated();

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'avatar_url' => $input['avatar_url'] ?? $existing['avatar_url'] ?? null
        ];

        if (array_key_exists('is_active', $input)) {
            $updateData['is_active'] = (int)$input['is_active'];
        }

        if (!empty($validated['password'])) {
            $updateData['password'] = $validated['password'];
        }

        $this->userModel->beginTransaction();

        try {
            $this->userModel->updateUser($id, $updateData);

            if (isset($validated['role_id'])) {
                $this->userModel->assignRole($id, (int)$validated['role_id'], true);
            }

            $this->logActivity('update', 'user', $id, "Updated user {$validated['email']}");

            $this->userModel->commit();
        } catch (Exception $e) {
            $this->userModel->rollback();
            Response::error('Failed to update user: ' . $e->getMessage(), [], 500);
        }

        $user = $this->userModel->findWithRoles($id);
        Response::success('User updated successfully', $user);
    }

    /**
     * POST /api/users/{id}/deactivate
     */
    public function deactivate($id)
    {
        $existing = $this->userModel->find($id);
        if (!$existing) {
            Response::notFound('User not found');
        }

        if (Auth::id() === (int)$id) {
            Response::error('Tidak dapat menonaktifkan akun sendiri.', [], 422);
        }

        $this->userModel->update($id, [
            'is_active' => 0,
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $this->logActivity('deactivate', 'user', $id, "Deactivated user {$existing['email']}");

        Response::success('User deactivated successfully');
    }

    /**
     * POST /api/users/{id}/reset-password
     */
    public function resetPassword($id)
    {
        $existing = $this->userModel->find($id);
        if (!$existing) {
            Response::notFound('User not found');
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        $validator = Validator::make($input, [
            'new_password' => 'required|min:6|confirmed'
        ]);

        if (!$validator->validate()) {
            Response::validationError($validator->errors());
        }

        $validated = $validator->validated();

        $this->userModel->updatePassword($id, $validated['new_password']);

        $this->logActivity('update', 'user', $id, 'Reset user password');

        Response::success('Password reset successfully');
    }

    /**
     * POST /api/users/{id}/role
     */
    public function setRole($id)
    {
        $existing = $this->userModel->find($id);
        if (!$existing) {
            Response::notFound('User not found');
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $roleId = $input['role_id'] ?? null;

        if (!$roleId || !$this->roleModel->find($roleId)) {
            Response::validationError(['role_id' => ['Role tidak ditemukan.']]);
        }

        $this->userModel->assignRole($id, (int)$roleId, true);

        $this->logActivity('update', 'user', $id, "Updated user role to {$roleId}");

        Response::success('User role updated successfully');
    }

    /**
     * POST /api/users/{id}/upload-avatar
     */
    public function uploadAvatar($id)
    {
        $existing = $this->userModel->find($id);
        if (!$existing) {
            Response::notFound('User not found');
        }

        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            Response::validationError(['avatar' => 'File tidak ditemukan atau terjadi kesalahan upload']);
        }

        $file = $_FILES['avatar'];

        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowedTypes)) {
            Response::validationError(['avatar' => 'File harus berupa gambar (JPG, PNG, GIF)']);
        }

        // Validate file size (max 2MB)
        if ($file['size'] > 2 * 1024 * 1024) {
            Response::validationError(['avatar' => 'Ukuran file maksimal 2MB']);
        }

        // Create uploads directory if not exists
        $uploadDir = ROOT_PATH . '/public/uploads/avatars';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'avatar_' . $id . '_' . time() . '.' . $extension;
        $filepath = $uploadDir . '/' . $filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            Response::error('Gagal menyimpan file');
        }

        // Get old avatar to delete
        $oldAvatar = $existing['avatar_url'] ?? null;

        // Update user avatar_url
        $avatarUrl = '/uploads/avatars/' . $filename;
        $result = $this->userModel->updateUser($id, ['avatar_url' => $avatarUrl]);

        if ($result) {
            // Delete old avatar file if exists
            if ($oldAvatar && file_exists(ROOT_PATH . '/public' . $oldAvatar)) {
                @unlink(ROOT_PATH . '/public' . $oldAvatar);
            }

            $this->logActivity('avatar_updated', 'user', $id, 'Upload foto profil user');

            Response::success('Foto profil berhasil diupload', ['avatar_url' => $avatarUrl]);
        } else {
            // Delete uploaded file if database update fails
            @unlink($filepath);
            Response::error('Gagal memperbarui database');
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
