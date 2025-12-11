<?php

/**
 * Role API Controller
 */

require_once ROOT_PATH . '/core/Controller.php';
require_once ROOT_PATH . '/core/Response.php';
require_once ROOT_PATH . '/core/Auth.php';
require_once ROOT_PATH . '/helpers/validation.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/middleware/AuthMiddleware.php';
require_once ROOT_PATH . '/models/Role.php';
require_once ROOT_PATH . '/models/Permission.php';
require_once ROOT_PATH . '/models/ActivityLog.php';

class RoleApiController extends Controller
{
    private $roleModel;
    private $permissionModel;

    public function __construct()
    {
        AuthMiddleware::check();
        $this->roleModel = new Role();
        $this->permissionModel = new Permission();
    }

    /**
     * GET /api/roles
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

            $result = $this->roleModel->getPaginated($page, $perPage, $filters);

            Response::success('Roles retrieved successfully', $result);
        } catch (Exception $e) {
            Response::error('Failed to fetch roles: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * GET /api/roles/{id}
     */
    public function show($id)
    {
        $role = $this->roleModel->findWithPermissions($id);

        if (!$role) {
            Response::notFound('Role not found');
        }

        $role['permission_ids'] = !empty($role['permission_ids'])
            ? array_map('intval', explode(',', $role['permission_ids']))
            : [];

        Response::success('Role detail retrieved', $role);
    }

    /**
     * POST /api/roles
     */
    public function store()
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        $validator = Validator::make($input, [
            'code' => 'required|min:2|max:50|unique:roles,code',
            'name' => 'required|min:3|max:100'
        ]);

        if (!$validator->validate()) {
            Response::validationError($validator->errors());
        }

        $validated = $validator->validated();

        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $validated['code'])) {
            Response::validationError(['code' => ['Kode hanya boleh berisi huruf, angka, dash, atau underscore.']]);
        }

        $data = [
            'code' => $validated['code'],
            'name' => $validated['name'],
            'description' => $input['description'] ?? null,
            'is_active' => isset($input['is_active']) ? (int)$input['is_active'] : 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $roleId = $this->roleModel->insert($data);

        $this->logActivity('create', 'role', $roleId, "Created role {$data['code']}");

        Response::created('Role created successfully', [
            'role_id' => $roleId
        ]);
    }

    /**
     * POST /api/roles/{id}
     */
    public function update($id)
    {
        $existing = $this->roleModel->find($id);
        if (!$existing) {
            Response::notFound('Role not found');
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        $validator = Validator::make($input, [
            'code' => 'required|min:2|max:50|unique:roles,code,' . $id,
            'name' => 'required|min:3|max:100'
        ]);

        if (!$validator->validate()) {
            Response::validationError($validator->errors());
        }

        $validated = $validator->validated();

        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $validated['code'])) {
            Response::validationError(['code' => ['Kode hanya boleh berisi huruf, angka, dash, atau underscore.']]);
        }

        $data = [
            'code' => $validated['code'],
            'name' => $validated['name'],
            'description' => $input['description'] ?? null,
            'is_active' => isset($input['is_active']) ? (int)$input['is_active'] : $existing['is_active'],
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $this->roleModel->update($id, $data);

        $this->logActivity('update', 'role', $id, "Updated role {$data['code']}");

        Response::success('Role updated successfully', $this->roleModel->findWithPermissions($id));
    }

    /**
     * POST /api/roles/{id}/delete
     */
    public function destroy($id)
    {
        $existing = $this->roleModel->find($id);
        if (!$existing) {
            Response::notFound('Role not found');
        }

        $assignedUsers = $this->roleModel->getUsers($id);
        if (!empty($assignedUsers)) {
            Response::error('Role masih digunakan oleh user lain.', ['role_id' => ['Role sedang dipakai pengguna.']], 422);
        }

        $this->roleModel->beginTransaction();
        try {
            $this->roleModel->query('DELETE FROM role_permissions WHERE role_id = ?', [$id]);
            $this->roleModel->delete($id);
            $this->roleModel->commit();
        } catch (Exception $e) {
            $this->roleModel->rollback();
            throw $e;
        }

        $this->logActivity('delete', 'role', $id, "Deleted role {$existing['code']}");

        Response::success('Role deleted successfully');
    }

    /**
     * POST /api/roles/{id}/permissions
     */
    public function syncPermissions($id)
    {
        $role = $this->roleModel->find($id);
        if (!$role) {
            Response::notFound('Role not found');
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $permissionIds = $input['permission_ids'] ?? [];

        if (!is_array($permissionIds)) {
            Response::validationError(['permission_ids' => ['Format permission_ids tidak valid.']]);
        }

        // Validate permissions exist
        $foundPermissions = $this->permissionModel->findByIds($permissionIds);
        if (count($foundPermissions) !== count($permissionIds)) {
            Response::validationError(['permission_ids' => ['Terdapat permission yang tidak ditemukan.']]);
        }

        $this->roleModel->beginTransaction();

        try {
            $this->roleModel->query('DELETE FROM role_permissions WHERE role_id = ?', [$id]);

            foreach ($permissionIds as $pid) {
                $this->roleModel->assignPermission($id, $pid, true);
            }

            $this->roleModel->commit();
        } catch (Exception $e) {
            $this->roleModel->rollback();
            Response::error('Gagal menyimpan permission: ' . $e->getMessage(), [], 500);
        }

        $this->logActivity('update', 'role', $id, "Updated role permissions for {$role['code']}");

        Response::success('Permissions updated successfully');
    }

    /**
     * GET /api/permissions
     */
    public function permissions()
    {
        try {
            $permissions = $this->permissionModel->getActive();
            Response::success('Permissions retrieved successfully', $permissions);
        } catch (Exception $e) {
            Response::error('Failed to fetch permissions: ' . $e->getMessage(), [], 500);
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
