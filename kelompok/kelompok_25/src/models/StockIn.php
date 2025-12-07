<?php

/**
 * StockIn Model
 * Mengelola transaksi stok masuk (pembelian material)
 */

require_once ROOT_PATH . '/core/Model.php';

class StockIn extends Model
{
    protected $table = 'stock_in';
    protected $primaryKey = 'id';

    /**
     * Get all stock in transactions with pagination and filters
     */
    public function getAll($page = 1, $perPage = 20, $filters = [])
    {
        $offset = ($page - 1) * $perPage;
        $where = ['1=1'];
        $params = [];

        // Filter by material
        if (!empty($filters['material_id'])) {
            $where[] = 'si.material_id = ?';
            $params[] = $filters['material_id'];
        }

        // Filter by supplier
        if (!empty($filters['supplier_id'])) {
            $where[] = 'si.supplier_id = ?';
            $params[] = $filters['supplier_id'];
        }

        // Filter by date range
        if (!empty($filters['date_from'])) {
            $where[] = 'DATE(si.transaction_date) >= ?';
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'DATE(si.transaction_date) <= ?';
            $params[] = $filters['date_to'];
        }

        // Filter by reference number
        if (!empty($filters['reference_number'])) {
            $where[] = 'si.reference_number LIKE ?';
            $params[] = '%' . $filters['reference_number'] . '%';
        }

        $whereClause = implode(' AND ', $where);
        $params[] = $perPage;
        $params[] = $offset;

        $sql = "SELECT si.*, 
                       m.code as material_code,
                       m.name as material_name,
                       m.unit as material_unit,
                       s.name as supplier_name,
                       u.name as user_name
                FROM {$this->table} si
                LEFT JOIN materials m ON si.material_id = m.id
                LEFT JOIN suppliers s ON si.supplier_id = s.id
                LEFT JOIN users u ON si.user_id = u.id
                WHERE {$whereClause}
                ORDER BY si.transaction_date DESC, si.created_at DESC
                LIMIT ? OFFSET ?";
        
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count all stock in transactions
     */
    public function countAll($filters = [])
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['material_id'])) {
            $where[] = 'material_id = ?';
            $params[] = $filters['material_id'];
        }

        if (!empty($filters['supplier_id'])) {
            $where[] = 'supplier_id = ?';
            $params[] = $filters['supplier_id'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'DATE(transaction_date) >= ?';
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'DATE(transaction_date) <= ?';
            $params[] = $filters['date_to'];
        }

        if (!empty($filters['reference_number'])) {
            $where[] = 'reference_number LIKE ?';
            $params[] = '%' . $filters['reference_number'] . '%';
        }

        $whereClause = implode(' AND ', $where);

        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE {$whereClause}";
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    /**
     * Find stock in transaction by ID with relations
     */
    public function findById($id)
    {
        $sql = "SELECT si.*, 
                       m.code as material_code,
                       m.name as material_name,
                       m.unit as material_unit,
                       m.current_stock,
                       s.name as supplier_name,
                       s.contact_person as supplier_contact,
                       s.phone as supplier_phone,
                       u.name as user_name,
                       u.email as user_email
                FROM {$this->table} si
                LEFT JOIN materials m ON si.material_id = m.id
                LEFT JOIN suppliers s ON si.supplier_id = s.id
                LEFT JOIN users u ON si.user_id = u.id
                WHERE si.id = ?";
        
        $stmt = $this->query($sql, [$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Find by reference number
     */
    public function findByReference($referenceNumber)
    {
        $sql = "SELECT * FROM {$this->table} WHERE reference_number = ?";
        $stmt = $this->query($sql, [$referenceNumber]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create new stock in transaction
     */
    public function create($data)
    {
        $sql = "INSERT INTO {$this->table} 
                (reference_number, material_id, supplier_id, quantity, unit_price, 
                 total_price, transaction_date, invoice_number, notes, user_id, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->query($sql, [
            $data['reference_number'],
            $data['material_id'],
            $data['supplier_id'],
            $data['quantity'],
            $data['unit_price'],
            $data['total_price'],
            $data['transaction_date'],
            $data['invoice_number'] ?? null,
            $data['notes'] ?? null,
            $data['user_id']
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * Update stock in transaction
     */
    public function updateStockIn($id, $data)
    {
        $fields = [];
        $values = [];

        $allowedFields = ['supplier_id', 'transaction_date', 'invoice_number', 'notes'];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $values[] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $values[] = $id;

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->query($sql, $values);

        return $stmt->rowCount() > 0;
    }

    /**
     * Delete stock in transaction
     */
    public function delete($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->query($sql, [$id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Get transactions by material
     */
    public function getByMaterial($materialId, $page = 1, $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT si.*, 
                       s.name as supplier_name,
                       u.name as user_name
                FROM {$this->table} si
                LEFT JOIN suppliers s ON si.supplier_id = s.id
                LEFT JOIN users u ON si.user_id = u.id
                WHERE si.material_id = ?
                ORDER BY si.transaction_date DESC
                LIMIT ? OFFSET ?";
        
        $stmt = $this->query($sql, [$materialId, $perPage, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get transactions by supplier
     */
    public function getBySupplier($supplierId, $page = 1, $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT si.*, 
                       m.code as material_code,
                       m.name as material_name,
                       m.unit as material_unit,
                       u.name as user_name
                FROM {$this->table} si
                LEFT JOIN materials m ON si.material_id = m.id
                LEFT JOIN users u ON si.user_id = u.id
                WHERE si.supplier_id = ?
                ORDER BY si.transaction_date DESC
                LIMIT ? OFFSET ?";
        
        $stmt = $this->query($sql, [$supplierId, $perPage, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get transactions by date range
     */
    public function getByDateRange($dateFrom, $dateTo, $page = 1, $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT si.*, 
                       m.code as material_code,
                       m.name as material_name,
                       s.name as supplier_name,
                       u.name as user_name
                FROM {$this->table} si
                LEFT JOIN materials m ON si.material_id = m.id
                LEFT JOIN suppliers s ON si.supplier_id = s.id
                LEFT JOIN users u ON si.user_id = u.id
                WHERE DATE(si.transaction_date) BETWEEN ? AND ?
                ORDER BY si.transaction_date DESC
                LIMIT ? OFFSET ?";
        
        $stmt = $this->query($sql, [$dateFrom, $dateTo, $perPage, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get today's transactions
     */
    public function getToday()
    {
        $sql = "SELECT si.*, 
                       m.code as material_code,
                       m.name as material_name,
                       m.unit as material_unit,
                       s.name as supplier_name,
                       u.name as user_name
                FROM {$this->table} si
                LEFT JOIN materials m ON si.material_id = m.id
                LEFT JOIN suppliers s ON si.supplier_id = s.id
                LEFT JOIN users u ON si.user_id = u.id
                WHERE DATE(si.transaction_date) = CURDATE()
                ORDER BY si.created_at DESC";
        
        $stmt = $this->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Generate reference number
     */
    public function generateReferenceNumber()
    {
        $prefix = 'IN';
        $date = date('Ymd');
        
        // Get last reference number for today
        $sql = "SELECT reference_number FROM {$this->table} 
                WHERE reference_number LIKE ? 
                ORDER BY reference_number DESC 
                LIMIT 1";
        
        $stmt = $this->query($sql, [$prefix . $date . '%']);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            // Extract sequence number and increment
            $lastNumber = (int)substr($result['reference_number'], -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . $date . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Check if reference number exists
     */
    public function referenceExists($referenceNumber, $exceptId = null)
    {
        if ($exceptId) {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                    WHERE reference_number = ? AND id != ?";
            $stmt = $this->query($sql, [$referenceNumber, $exceptId]);
        } else {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE reference_number = ?";
            $stmt = $this->query($sql, [$referenceNumber]);
        }

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }

    /**
     * Get statistics
     */
    public function getStats($dateFrom = null, $dateTo = null)
    {
        $where = '1=1';
        $params = [];

        if ($dateFrom && $dateTo) {
            $where = 'DATE(transaction_date) BETWEEN ? AND ?';
            $params = [$dateFrom, $dateTo];
        }

        $sql = "SELECT 
                    COUNT(*) as total_transactions,
                    SUM(quantity) as total_quantity,
                    SUM(total_price) as total_value,
                    AVG(total_price) as avg_value,
                    COUNT(DISTINCT material_id) as total_materials,
                    COUNT(DISTINCT supplier_id) as total_suppliers
                FROM {$this->table}
                WHERE {$where}";
        
        $stmt = $this->query($sql, $params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get top materials (most purchased)
     */
    public function getTopMaterials($limit = 10, $dateFrom = null, $dateTo = null)
    {
        $where = '1=1';
        $params = [];

        if ($dateFrom && $dateTo) {
            $where = 'DATE(si.transaction_date) BETWEEN ? AND ?';
            $params = [$dateFrom, $dateTo];
        }

        $params[] = $limit;

        $sql = "SELECT 
                    si.material_id,
                    m.code as material_code,
                    m.name as material_name,
                    m.unit as material_unit,
                    COUNT(*) as transaction_count,
                    SUM(si.quantity) as total_quantity,
                    SUM(si.total_price) as total_value
                FROM {$this->table} si
                LEFT JOIN materials m ON si.material_id = m.id
                WHERE {$where}
                GROUP BY si.material_id
                ORDER BY total_quantity DESC
                LIMIT ?";
        
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get top suppliers (most purchases)
     */
    public function getTopSuppliers($limit = 10, $dateFrom = null, $dateTo = null)
    {
        $where = '1=1';
        $params = [];

        if ($dateFrom && $dateTo) {
            $where = 'DATE(si.transaction_date) BETWEEN ? AND ?';
            $params = [$dateFrom, $dateTo];
        }

        $params[] = $limit;

        $sql = "SELECT 
                    si.supplier_id,
                    s.name as supplier_name,
                    COUNT(*) as transaction_count,
                    SUM(si.quantity) as total_quantity,
                    SUM(si.total_price) as total_value
                FROM {$this->table} si
                LEFT JOIN suppliers s ON si.supplier_id = s.id
                WHERE {$where}
                GROUP BY si.supplier_id
                ORDER BY total_value DESC
                LIMIT ?";
        
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get monthly summary
     */
    public function getMonthlySummary($year)
    {
        $sql = "SELECT 
                    MONTH(transaction_date) as month,
                    COUNT(*) as total_transactions,
                    SUM(quantity) as total_quantity,
                    SUM(total_price) as total_value
                FROM {$this->table}
                WHERE YEAR(transaction_date) = ?
                GROUP BY MONTH(transaction_date)
                ORDER BY month";
        
        $stmt = $this->query($sql, [$year]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Calculate total value
     */
    public function getTotalValue($materialId = null, $dateFrom = null, $dateTo = null)
    {
        $where = ['1=1'];
        $params = [];

        if ($materialId) {
            $where[] = 'material_id = ?';
            $params[] = $materialId;
        }

        if ($dateFrom && $dateTo) {
            $where[] = 'DATE(transaction_date) BETWEEN ? AND ?';
            $params[] = $dateFrom;
            $params[] = $dateTo;
        }

        $whereClause = implode(' AND ', $where);

        $sql = "SELECT COALESCE(SUM(total_price), 0) as total FROM {$this->table} WHERE {$whereClause}";
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }
}
