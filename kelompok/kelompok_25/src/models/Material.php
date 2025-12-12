<?php

class Material extends Model {
    protected $table = 'materials';

    public function getStockReport($search = '', $categoryFilter = '', $statusFilter = '') {
        $sql = "SELECT 
                    m.id,
                    m.name,
                    m.code,
                    m.unit,
                    m.current_stock,
                    m.min_stock,
                    m.image_url,
                    c.name as category_name,
                    COALESCE(
                        (SELECT unit_price FROM stock_in WHERE material_id = m.id ORDER BY created_at DESC LIMIT 1),
                        0
                    ) as unit_price,
                    (m.current_stock * COALESCE(
                        (SELECT unit_price FROM stock_in WHERE material_id = m.id ORDER BY created_at DESC LIMIT 1),
                        0
                    )) as total_value
                FROM materials m
                LEFT JOIN categories c ON m.category_id = c.id
                WHERE m.is_active = 1";
        
        $params = [];
        
        if ($search) {
            $sql .= " AND (m.name LIKE ? OR m.code LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if ($categoryFilter) {
            $sql .= " AND c.name = ?";
            $params[] = $categoryFilter;
        }
        
        if ($statusFilter) {
            if ($statusFilter === 'Aman') {
                $sql .= " AND m.current_stock > m.min_stock";
            } elseif ($statusFilter === 'Hampir Habis') {
                $sql .= " AND m.current_stock <= m.min_stock AND m.current_stock > 0";
            } elseif ($statusFilter === 'Perlu Restock') {
                $sql .= " AND m.current_stock <= m.min_stock";
            }
        }
        
        $sql .= " ORDER BY m.name ASC";
        
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    public function getStockSummary() {
        $sql = "SELECT 
                    COUNT(*) as total_items,
                    SUM(m.current_stock * COALESCE(
                        (SELECT unit_price FROM stock_in WHERE material_id = m.id ORDER BY created_at DESC LIMIT 1),
                        0
                    )) as total_value,
                    SUM(CASE WHEN m.current_stock <= m.min_stock THEN 1 ELSE 0 END) as restock_needed,
                    SUM(CASE WHEN m.current_stock <= m.min_stock AND m.current_stock > 0 THEN 1 ELSE 0 END) as almost_empty
                FROM materials m
                WHERE m.is_active = 1";
        
        $stmt = $this->query($sql);
        return $stmt->fetch();
    }

    public function getCategories() {
        $sql = "SELECT DISTINCT c.name 
                FROM categories c 
                INNER JOIN materials m ON c.id = m.category_id 
                WHERE m.is_active = 1 
                ORDER BY c.name";
        $stmt = $this->query($sql);
        return $stmt->fetchAll();
    }

    public function getAll() {
        $sql = "SELECT * FROM {$this->table} WHERE is_active = 1 ORDER BY name ASC";
        return $this->query($sql)->fetchAll();
    }

    public function findById($id) {
        $sql = "SELECT m.*, 
                c.name as category_name,
                s.id as supplier_id,
                s.name as supplier_name,
                s.contact_person as supplier_contact_person,
                s.phone as supplier_phone,
                s.email as supplier_email,
                s.address as supplier_address
                FROM {$this->table} m
                LEFT JOIN categories c ON m.category_id = c.id
                LEFT JOIN suppliers s ON m.default_supplier_id = s.id
                WHERE m.id = ? LIMIT 1";
        return $this->query($sql, [$id])->fetch();
    }

    /**
     * Create new material
     */
    public function create($data)
    {
        // Remove null values to prevent database errors
        $data = array_filter($data, function($value) {
            return $value !== null && $value !== '';
        });

        $data['is_active'] = $data['is_active'] ?? 1;
        $data['created_at'] = date('Y-m-d H:i:s');

        return $this->insert($data);
    }

    /**
     * Update material
     */
    public function updateMaterial($id, $data)
    {
        // Remove null values to prevent database errors
        $data = array_filter($data, function($value) {
            return $value !== null && $value !== '';
        });

        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->update($id, $data);
    }

    /**
     * Soft delete material (set is_active to false)
     */
    public function softDelete($id)
    {
        return $this->updateMaterial($id, ['is_active' => false]);
    }

    /**
     * Check if material exists
     */
    public function exists($materialId)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE id = ?";
        $stmt = $this->query($sql, [$materialId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }

    /**
     * Get all active materials with pagination
     */
    public function getAllActive($page = 1, $perPage = 10, $search = '', $categoryId = '', $status = '')
    {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT m.*, c.name as category_name, s.name as supplier_name 
                FROM {$this->table} m
                LEFT JOIN categories c ON m.category_id = c.id
                LEFT JOIN suppliers s ON m.default_supplier_id = s.id
                WHERE m.is_active = 1";
        
        $params = [];
        
        if ($search) {
            $sql .= " AND (m.name LIKE ? OR m.code LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if ($categoryId) {
            $sql .= " AND m.category_id = ?";
            $params[] = $categoryId;
        }
        
        if ($status) {
            if ($status === 'Aman') {
                $sql .= " AND m.current_stock > m.min_stock";
            } elseif ($status === 'Hampir Habis') {
                $sql .= " AND m.current_stock <= m.min_stock AND m.current_stock > 0";
            } elseif ($status === 'Habis') {
                $sql .= " AND m.current_stock <= 0";
            }
        }
        
        $sql .= " ORDER BY m.name ASC LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;
        
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count active materials with filters
     */
    public function countActive($search = '', $categoryId = '', $status = '')
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} m WHERE m.is_active = 1";
        
        $params = [];
        
        if ($search) {
            $sql .= " AND (m.name LIKE ? OR m.code LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if ($categoryId) {
            $sql .= " AND m.category_id = ?";
            $params[] = $categoryId;
        }
        
        if ($status) {
            if ($status === 'Aman') {
                $sql .= " AND m.current_stock > m.min_stock";
            } elseif ($status === 'Hampir Habis') {
                $sql .= " AND m.current_stock <= m.min_stock AND m.current_stock > 0";
            } elseif ($status === 'Habis') {
                $sql .= " AND m.current_stock <= 0";
            }
        }
        
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    /**
     * Get current stock of a material
     */
    public function getCurrentStock($materialId)
    {
        $sql = "SELECT current_stock FROM {$this->table} WHERE id = ? LIMIT 1";
        $stmt = $this->query($sql, [$materialId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (float)$result['current_stock'] : 0;
    }

    /**
     * Update material stock
     * @param int $materialId
     * @param float $quantity
     * @param string $operation 'add' or 'subtract'
     * @return bool
     */
    public function updateStock($materialId, $quantity, $operation = 'add')
    {
        if ($operation === 'add') {
            $sql = "UPDATE {$this->table} SET current_stock = current_stock + ? WHERE id = ?";
        } else {
            $sql = "UPDATE {$this->table} SET current_stock = current_stock - ? WHERE id = ?";
        }
        
        $stmt = $this->query($sql, [$quantity, $materialId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Get materials with low stock (current_stock <= min_stock)
     */
    public function getLowStock($page = 1, $perPage = 20, $filters = [])
    {
        $page = max(1, (int)$page);
        $perPage = max(1, min(100, (int)$perPage));
        $offset = ($page - 1) * $perPage;

        $where = ['m.is_active = 1', 'm.current_stock <= m.min_stock'];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = '(m.name LIKE ? OR m.code LIKE ?)';
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
        }

        if (!empty($filters['category_id'])) {
            $where[] = 'm.category_id = ?';
            $params[] = (int)$filters['category_id'];
        }

        if (isset($filters['only_out_of_stock']) && $filters['only_out_of_stock']) {
            $where[] = 'm.current_stock = 0';
        }

        $whereSql = 'WHERE ' . implode(' AND ', $where);

        $countSql = "SELECT COUNT(*) FROM materials m {$whereSql}";
        $total = (int)$this->query($countSql, $params)->fetchColumn();

        $dataSql = "SELECT 
                        m.id,
                        m.code,
                        m.name,
                        m.unit,
                        m.current_stock,
                        m.min_stock,
                        c.name as category_name,
                        s.name as supplier_name,
                        COALESCE(
                            (SELECT unit_price FROM stock_in WHERE material_id = m.id ORDER BY created_at DESC LIMIT 1),
                            0
                        ) as last_unit_price,
                        (m.min_stock - m.current_stock) as shortage_quantity,
                        CASE 
                            WHEN m.current_stock = 0 THEN 'out_of_stock'
                            WHEN m.current_stock <= m.min_stock THEN 'low_stock'
                            ELSE 'normal'
                        END as status
                    FROM materials m
                    LEFT JOIN categories c ON m.category_id = c.id
                    LEFT JOIN suppliers s ON m.default_supplier_id = s.id
                    {$whereSql}
                    ORDER BY m.current_stock ASC, m.name ASC
                    LIMIT ? OFFSET ?";

        $dataParams = array_merge($params, [$perPage, $offset]);
        $data = $this->query($dataSql, $dataParams)->fetchAll();

        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => (int)ceil($total / $perPage)
        ];
    }

    /**
     * Get low stock summary statistics
     */
    public function getLowStockSummary()
    {
        $sql = "SELECT 
                    COUNT(*) as total_low_stock,
                    SUM(CASE WHEN m.current_stock = 0 THEN 1 ELSE 0 END) as out_of_stock,
                    SUM(CASE WHEN m.current_stock > 0 AND m.current_stock <= m.min_stock THEN 1 ELSE 0 END) as critical,
                    SUM(m.min_stock - m.current_stock) as total_shortage_quantity
                FROM materials m
                WHERE m.is_active = 1 AND m.current_stock <= m.min_stock";

        $stmt = $this->query($sql);
        return $stmt->fetch();
    }

    /**
     * Get low stock materials for dashboard
     */
    public function getLowStockMaterials($limit = 10)
    {
        $sql = "SELECT 
                    m.id,
                    m.name,
                    m.code,
                    m.unit,
                    m.current_stock,
                    m.min_stock,
                    m.image_url,
                    c.name as category_name
                FROM materials m
                LEFT JOIN categories c ON m.category_id = c.id
                WHERE m.is_active = 1 AND m.current_stock <= m.min_stock
                ORDER BY (m.current_stock / NULLIF(m.min_stock, 0)) ASC, m.current_stock ASC
                LIMIT ?";
        
        $stmt = $this->query($sql, [$limit]);
        return $stmt->fetchAll();
    }
}
