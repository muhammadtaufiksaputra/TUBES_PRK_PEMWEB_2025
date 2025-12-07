<?php

/**
 * Material Model
 * Mengelola data bahan baku/material
 */

require_once ROOT_PATH . '/core/Model.php';

class Material extends Model
{
    protected $table = 'materials';
    protected $primaryKey = 'id';

    /**
     * Get all materials with pagination and filters
     */
    public function getAll($page = 1, $perPage = 20, $filters = [])
    {
        $offset = ($page - 1) * $perPage;
        $where = ['m.is_active = TRUE'];
        $params = [];

        // Filter by category
        if (!empty($filters['category_id'])) {
            $where[] = 'm.category_id = ?';
            $params[] = $filters['category_id'];
        }

        // Filter by supplier
        if (!empty($filters['supplier_id'])) {
            $where[] = 'm.default_supplier_id = ?';
            $params[] = $filters['supplier_id'];
        }

        // Filter by stock status
        if (!empty($filters['stock_status'])) {
            switch ($filters['stock_status']) {
                case 'empty':
                    $where[] = 'm.current_stock = 0';
                    break;
                case 'low':
                    $where[] = 'm.current_stock > 0 AND m.current_stock <= m.min_stock';
                    break;
                case 'normal':
                    $where[] = 'm.current_stock > m.min_stock';
                    break;
            }
        }

        $whereClause = implode(' AND ', $where);
        $params[] = $perPage;
        $params[] = $offset;

        $sql = "SELECT m.*, 
                       c.name as category_name,
                       s.name as supplier_name
                FROM {$this->table} m
                LEFT JOIN categories c ON m.category_id = c.id
                LEFT JOIN suppliers s ON m.default_supplier_id = s.id
                WHERE {$whereClause}
                ORDER BY m.name ASC
                LIMIT ? OFFSET ?";
        
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count all materials
     */
    public function countAll($filters = [])
    {
        $where = ['is_active = TRUE'];
        $params = [];

        if (!empty($filters['category_id'])) {
            $where[] = 'category_id = ?';
            $params[] = $filters['category_id'];
        }

        if (!empty($filters['supplier_id'])) {
            $where[] = 'default_supplier_id = ?';
            $params[] = $filters['supplier_id'];
        }

        if (!empty($filters['stock_status'])) {
            switch ($filters['stock_status']) {
                case 'empty':
                    $where[] = 'current_stock = 0';
                    break;
                case 'low':
                    $where[] = 'current_stock > 0 AND current_stock <= min_stock';
                    break;
                case 'normal':
                    $where[] = 'current_stock > min_stock';
                    break;
            }
        }

        $whereClause = implode(' AND ', $where);

        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE {$whereClause}";
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    /**
     * Find material by ID with relations
     */
    public function findById($id)
    {
        $sql = "SELECT m.*, 
                       c.name as category_name,
                       s.name as supplier_name,
                       s.contact_person as supplier_contact,
                       s.phone as supplier_phone
                FROM {$this->table} m
                LEFT JOIN categories c ON m.category_id = c.id
                LEFT JOIN suppliers s ON m.default_supplier_id = s.id
                WHERE m.id = ?";
        
        $stmt = $this->query($sql, [$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Find material by code
     */
    public function findByCode($code)
    {
        $sql = "SELECT * FROM {$this->table} WHERE code = ?";
        $stmt = $this->query($sql, [$code]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create new material
     */
    public function create($data)
    {
        $sql = "INSERT INTO {$this->table} 
                (code, name, description, category_id, default_supplier_id, 
                 unit, current_stock, min_stock, reorder_point, unit_price, 
                 is_active, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $this->query($sql, [
            $data['code'],
            $data['name'],
            $data['description'] ?? null,
            $data['category_id'],
            $data['default_supplier_id'] ?? null,
            $data['unit'],
            $data['current_stock'] ?? 0,
            $data['min_stock'],
            $data['reorder_point'] ?? $data['min_stock'],
            $data['unit_price'],
            $data['is_active'] ?? true
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * Update material
     */
    public function updateMaterial($id, $data)
    {
        $fields = [];
        $values = [];

        $allowedFields = ['code', 'name', 'description', 'category_id', 
                          'default_supplier_id', 'unit', 'min_stock', 
                          'reorder_point', 'unit_price', 'is_active'];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $values[] = $data[$field];
            }
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
     * Update stock (for stock in/out operations)
     */
    public function updateStock($id, $quantity, $operation = 'add')
    {
        if ($operation === 'add') {
            $sql = "UPDATE {$this->table} 
                    SET current_stock = current_stock + ?, 
                        updated_at = NOW() 
                    WHERE id = ?";
        } else {
            $sql = "UPDATE {$this->table} 
                    SET current_stock = current_stock - ?, 
                        updated_at = NOW() 
                    WHERE id = ? AND current_stock >= ?";
        }

        $params = $operation === 'subtract' 
            ? [$quantity, $id, $quantity] 
            : [$quantity, $id];

        $stmt = $this->query($sql, $params);
        return $stmt->rowCount() > 0;
    }

    /**
     * Soft delete material
     */
    public function delete($id)
    {
        $sql = "UPDATE {$this->table} SET is_active = FALSE, updated_at = NOW() WHERE id = ?";
        $stmt = $this->query($sql, [$id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Search materials
     */
    public function search($keyword, $page = 1, $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;
        $searchTerm = "%$keyword%";
        
        $sql = "SELECT m.*, 
                       c.name as category_name,
                       s.name as supplier_name
                FROM {$this->table} m
                LEFT JOIN categories c ON m.category_id = c.id
                LEFT JOIN suppliers s ON m.default_supplier_id = s.id
                WHERE m.is_active = TRUE 
                AND (m.name LIKE ? OR m.code LIKE ? OR m.description LIKE ?)
                ORDER BY m.name ASC
                LIMIT ? OFFSET ?";
        
        $stmt = $this->query($sql, [$searchTerm, $searchTerm, $searchTerm, $perPage, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count search results
     */
    public function countSearch($keyword)
    {
        $searchTerm = "%$keyword%";
        
        $sql = "SELECT COUNT(*) as total FROM {$this->table} 
                WHERE is_active = TRUE 
                AND (name LIKE ? OR code LIKE ? OR description LIKE ?)";
        
        $stmt = $this->query($sql, [$searchTerm, $searchTerm, $searchTerm]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    /**
     * Get low stock materials
     */
    public function getLowStock($page = 1, $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT m.*, 
                       c.name as category_name,
                       s.name as supplier_name,
                       (m.min_stock - m.current_stock) as shortage
                FROM {$this->table} m
                LEFT JOIN categories c ON m.category_id = c.id
                LEFT JOIN suppliers s ON m.default_supplier_id = s.id
                WHERE m.is_active = TRUE 
                AND m.current_stock <= m.min_stock
                ORDER BY shortage DESC, m.name ASC
                LIMIT ? OFFSET ?";
        
        $stmt = $this->query($sql, [$perPage, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count low stock materials
     */
    public function countLowStock()
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} 
                WHERE is_active = TRUE AND current_stock <= min_stock";
        $stmt = $this->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    /**
     * Get out of stock materials
     */
    public function getOutOfStock()
    {
        $sql = "SELECT m.*, 
                       c.name as category_name,
                       s.name as supplier_name
                FROM {$this->table} m
                LEFT JOIN categories c ON m.category_id = c.id
                LEFT JOIN suppliers s ON m.default_supplier_id = s.id
                WHERE m.is_active = TRUE AND m.current_stock = 0
                ORDER BY m.name ASC";
        
        $stmt = $this->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get stock status
     */
    public function getStockStatus($id)
    {
        $material = $this->findById($id);
        
        if (!$material) {
            return null;
        }

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
     * Calculate stock value
     */
    public function calculateStockValue($id)
    {
        $material = $this->findById($id);
        
        if (!$material) {
            return 0;
        }

        return $material['current_stock'] * $material['unit_price'];
    }

    /**
     * Get materials by category
     */
    public function getByCategory($categoryId, $page = 1, $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT m.*, 
                       c.name as category_name,
                       s.name as supplier_name
                FROM {$this->table} m
                LEFT JOIN categories c ON m.category_id = c.id
                LEFT JOIN suppliers s ON m.default_supplier_id = s.id
                WHERE m.is_active = TRUE AND m.category_id = ?
                ORDER BY m.name ASC
                LIMIT ? OFFSET ?";
        
        $stmt = $this->query($sql, [$categoryId, $perPage, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get materials by supplier
     */
    public function getBySupplier($supplierId, $page = 1, $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT m.*, 
                       c.name as category_name,
                       s.name as supplier_name
                FROM {$this->table} m
                LEFT JOIN categories c ON m.category_id = c.id
                LEFT JOIN suppliers s ON m.default_supplier_id = s.id
                WHERE m.is_active = TRUE AND m.default_supplier_id = ?
                ORDER BY m.name ASC
                LIMIT ? OFFSET ?";
        
        $stmt = $this->query($sql, [$supplierId, $perPage, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get statistics
     */
    public function getStats()
    {
        $sql = "SELECT 
                    COUNT(*) as total_materials,
                    SUM(current_stock * unit_price) as total_value,
                    SUM(CASE WHEN current_stock <= min_stock THEN 1 ELSE 0 END) as low_stock_count,
                    SUM(CASE WHEN current_stock = 0 THEN 1 ELSE 0 END) as out_of_stock_count
                FROM {$this->table}
                WHERE is_active = TRUE";
        
        $stmt = $this->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Check if code exists
     */
    public function codeExists($code, $exceptId = null)
    {
        if ($exceptId) {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE code = ? AND id != ?";
            $stmt = $this->query($sql, [$code, $exceptId]);
        } else {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE code = ?";
            $stmt = $this->query($sql, [$code]);
        }

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }

    /**
     * Check if material has transactions
     */
    public function hasTransactions($id)
    {
        // Check stock_in
        $sql = "SELECT COUNT(*) as count FROM stock_in WHERE material_id = ?";
        $stmt = $this->query($sql, [$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            return true;
        }

        // Check stock_out
        $sql = "SELECT COUNT(*) as count FROM stock_out WHERE material_id = ?";
        $stmt = $this->query($sql, [$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] > 0;
    }

    /**
     * Get current stock
     */
    public function getCurrentStock($id)
    {
        $sql = "SELECT current_stock FROM {$this->table} WHERE id = ?";
        $stmt = $this->query($sql, [$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['current_stock'] ?? 0;
    }
}
