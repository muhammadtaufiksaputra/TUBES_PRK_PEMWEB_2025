<?php

/**
 * Category Model
 * Mengelola data kategori bahan baku
 */

require_once ROOT_PATH . '/core/Model.php';

class Category extends Model
{
    protected $table = 'categories';
    protected $primaryKey = 'id';

    /**
     * Get all categories with pagination and search
     */
    public function getAll($page = 1, $perPage = 20, $search = '')
    {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        
        if (!empty($search)) {
            $sql .= " WHERE (name LIKE ? OR description LIKE ?)";
            $searchParam = '%' . $search . '%';
            $params = [$searchParam, $searchParam];
        }
        
        $sql .= " ORDER BY name ASC LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;
        
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get count of all categories with search
     */
    public function countAll($search = '')
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $params = [];
        
        if (!empty($search)) {
            $sql .= " WHERE (name LIKE ? OR description LIKE ?)";
            $searchParam = '%' . $search . '%';
            $params = [$searchParam, $searchParam];
        }
        
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    /**
     * Find category by ID
     */
    public function findById($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $stmt = $this->query($sql, [$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Find category by name
     */
    public function findByName($name)
    {
        $sql = "SELECT * FROM {$this->table} WHERE name = ?";
        $stmt = $this->query($sql, [$name]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create new category
     */
    public function create($data)
    {
        $sql = "INSERT INTO {$this->table} (name, description, created_at, updated_at) 
                VALUES (?, ?, NOW(), NOW())";
        
        $stmt = $this->query($sql, [
            $data['name'],
            $data['description'] ?? null
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * Update category
     */
    public function updateCategory($id, $data)
    {
        $fields = [];
        $values = [];

        if (isset($data['name'])) {
            $fields[] = 'name = ?';
            $values[] = $data['name'];
        }

        if (isset($data['description'])) {
            $fields[] = 'description = ?';
            $values[] = $data['description'];
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = 'updated_at = NOW()';
        $values[] = $id;

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->query($sql, $values);

        return $stmt->rowCount() > 0;
    }

    /**
     * Delete category (hard delete)
     */
    public function delete($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->query($sql, [$id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Search categories by name
     */
    public function search($keyword, $page = 1, $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;
        $searchTerm = "%$keyword%";
        
        $sql = "SELECT * FROM {$this->table} 
                WHERE name LIKE ? OR description LIKE ?
                ORDER BY name ASC 
                LIMIT ? OFFSET ?";
        
        $stmt = $this->query($sql, [$searchTerm, $searchTerm, $perPage, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count search results
     */
    public function countSearch($keyword)
    {
        $searchTerm = "%$keyword%";
        
        $sql = "SELECT COUNT(*) as total FROM {$this->table} 
                WHERE name LIKE ? OR description LIKE ?";
        
        $stmt = $this->query($sql, [$searchTerm, $searchTerm]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    /**
     * Check if category name exists (for unique validation)
     */
    public function exists($name, $exceptId = null)
    {
        if ($exceptId) {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE name = ? AND id != ?";
            $stmt = $this->query($sql, [$name, $exceptId]);
        } else {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE name = ?";
            $stmt = $this->query($sql, [$name]);
        }

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }

    /**
     * Get material count for a category
     */
    public function getMaterialCount($categoryId)
    {
        $sql = "SELECT COUNT(*) as total FROM materials WHERE category_id = ?";
        $stmt = $this->query($sql, [$categoryId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    /**
     * Get categories with material count
     */
    public function getWithMaterialCount($page = 1, $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT c.*, COUNT(m.id) as material_count 
                FROM {$this->table} c 
                LEFT JOIN materials m ON c.id = m.category_id 
                GROUP BY c.id
                ORDER BY c.name ASC
                LIMIT ? OFFSET ?";
        
        $stmt = $this->query($sql, [$perPage, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Check if category is used by any material
     */
    public function isUsedByMaterials($categoryId)
    {
        return $this->getMaterialCount($categoryId) > 0;
    }
}
