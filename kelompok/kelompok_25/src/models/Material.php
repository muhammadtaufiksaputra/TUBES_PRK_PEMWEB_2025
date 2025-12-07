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
        $sql = "SELECT * FROM {$this->table} WHERE id = ? LIMIT 1";
        return $this->query($sql, [$id])->fetch();
    }
}
