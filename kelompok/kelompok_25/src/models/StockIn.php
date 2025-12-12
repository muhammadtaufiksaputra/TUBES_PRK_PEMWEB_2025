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
    protected $dateColumnName;

    /**
     * Resolve the column used to store transaction dates (transaction_date or legacy txn_date).
     * Fallback to created_at when neither column exists.
     */
    protected function getDateColumnName()
    {
        if ($this->dateColumnName === null) {
            $candidates = ['transaction_date', 'txn_date'];

            foreach ($candidates as $candidate) {
                $sql = "SHOW COLUMNS FROM {$this->table} LIKE '" . $candidate . "'";
                $stmt = $this->db->query($sql);
                if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                    $this->dateColumnName = $candidate;
                    break;
                }
            }

            if ($this->dateColumnName === null) {
                $this->dateColumnName = 'created_at';
            }
        }

        return $this->dateColumnName;
    }

    protected function getDateColumnExpression($alias = null)
    {
        $column = $this->getDateColumnName();
        return $alias ? "{$alias}.{$column}" : $column;
    }

    protected function getSelectDateAlias($alias = 'si')
    {
        $column = $this->getDateColumnName();
        if ($column === 'transaction_date') {
            return '';
        }

        return ", {$alias}.{$column} as transaction_date";
    }

    /**
     * Get all stock in transactions with pagination and filters
     */
    public function getAll($page = 1, $perPage = 20, $filters = [])
    {
        $offset = ($page - 1) * $perPage;
        $where = ['1=1'];
        $params = [];
        $dateColumnExpr = $this->getDateColumnExpression('si');
        $dateSelectAlias = $this->getSelectDateAlias('si');

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
            $where[] = "DATE({$dateColumnExpr}) >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = "DATE({$dateColumnExpr}) <= ?";
            $params[] = $filters['date_to'];
        }

        // Filter by reference number
        if (!empty($filters['reference_number'])) {
            $where[] = 'si.reference_number LIKE ?';
            $params[] = '%' . $filters['reference_number'] . '%';
        }

        // Search filter (searches in reference_number, material name, supplier name)
        if (!empty($filters['search'])) {
            $where[] = '(si.reference_number LIKE ? OR m.name LIKE ? OR m.code LIKE ? OR s.name LIKE ?)';
            $searchParam = '%' . $filters['search'] . '%';
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }

        $whereClause = implode(' AND ', $where);
        $params[] = $perPage;
        $params[] = $offset;

        $orderClause = "ORDER BY {$dateColumnExpr} DESC";
        if ($this->getDateColumnName() !== 'created_at') {
            $orderClause .= ', si.created_at DESC';
        }

        $sql = "SELECT si.*, 
                   m.code as material_code,
                   m.name as material_name,
                   m.unit as unit,
                   s.name as supplier_name,
                   u.name as user_name{$dateSelectAlias}
                FROM {$this->table} si
                LEFT JOIN materials m ON si.material_id = m.id
                LEFT JOIN suppliers s ON si.supplier_id = s.id
            LEFT JOIN users u ON si.created_by = u.id
                WHERE {$whereClause}
                {$orderClause}
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
        $searchJoin = false;
        $dateColumn = $this->getDateColumnName();

        if (!empty($filters['material_id'])) {
            $where[] = 'si.material_id = ?';
            $params[] = $filters['material_id'];
        }

        if (!empty($filters['supplier_id'])) {
            $where[] = 'si.supplier_id = ?';
            $params[] = $filters['supplier_id'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = "DATE(si.{$dateColumn}) >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = "DATE(si.{$dateColumn}) <= ?";
            $params[] = $filters['date_to'];
        }

        if (!empty($filters['reference_number'])) {
            $where[] = 'si.reference_number LIKE ?';
            $params[] = '%' . $filters['reference_number'] . '%';
        }

        // Search filter needs JOIN for material and supplier names
        if (!empty($filters['search'])) {
            $searchJoin = true;
            $where[] = '(si.reference_number LIKE ? OR m.name LIKE ? OR m.code LIKE ? OR s.name LIKE ?)';
            $searchParam = '%' . $filters['search'] . '%';
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }

        $whereClause = implode(' AND ', $where);

        // Use JOIN if search is used
        if ($searchJoin) {
            $sql = "SELECT COUNT(*) as total 
                    FROM {$this->table} si
                    LEFT JOIN materials m ON si.material_id = m.id
                    LEFT JOIN suppliers s ON si.supplier_id = s.id
                    WHERE {$whereClause}";
        } else {
            $sql = "SELECT COUNT(*) as total FROM {$this->table} si WHERE {$whereClause}";
        }

        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    /**
     * Find stock in transaction by ID with relations
     */
    public function findById($id)
    {
        $dateSelectAlias = $this->getSelectDateAlias('si');

        $sql = "SELECT si.*, 
                   m.code as material_code,
                   m.name as material_name,
                   m.unit as unit,
                   m.current_stock,
                   s.name as supplier_name,
                   s.contact_person as supplier_contact,
                   s.phone as supplier_phone,
                   u.name as user_name,
                   u.email as user_email{$dateSelectAlias}
                FROM {$this->table} si
                LEFT JOIN materials m ON si.material_id = m.id
                LEFT JOIN suppliers s ON si.supplier_id = s.id
                LEFT JOIN users u ON si.created_by = u.id
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
        $dateColumnName = $this->getDateColumnName();

        $columns = [
            'reference_number',
            'material_id',
            'supplier_id',
            'quantity',
            'unit_price',
            'total_price'
        ];
        $placeholders = array_fill(0, count($columns), '?');
        $values = [
            $data['reference_number'],
            $data['material_id'],
            $data['supplier_id'],
            $data['quantity'],
            $data['unit_price'],
            $data['total_price']
        ];

        if (in_array($dateColumnName, ['transaction_date', 'txn_date'], true)) {
            $columns[] = $dateColumnName;
            $placeholders[] = '?';
            $values[] = $data['transaction_date'];
        }

        // Map notes/invoice_number to actual 'note' column
        $columns[] = 'note';
        $placeholders[] = '?';
        $values[] = $data['notes'] ?? $data['invoice_number'] ?? null;

        $columns[] = 'created_by';
        $placeholders[] = '?';
        $values[] = $data['created_by'];

        $createdAtValue = date('Y-m-d H:i:s');
        if ($dateColumnName === 'created_at' && !empty($data['transaction_date'])) {
            $createdAtValue = $data['transaction_date'] . ' 00:00:00';
        }

        $columns[] = 'created_at';
        $placeholders[] = '?';
        $values[] = $createdAtValue;

        $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $this->query($sql, $values);

        return $this->db->lastInsertId();
    }

    /**
     * Update stock in transaction
     */
    public function updateStockIn($id, $data)
    {
        $fields = [];
        $values = [];

        $allowedFields = ['supplier_id', 'transaction_date', 'note'];
        $dateColumnName = $this->getDateColumnName();

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                if ($field === 'transaction_date') {
                    $targetColumn = $this->getDateColumnName();
                    if ($targetColumn === 'created_at') {
                        $fields[] = "{$targetColumn} = ?";
                        $values[] = $data[$field] . ' 00:00:00';
                    } else {
                        $fields[] = "{$targetColumn} = ?";
                        $values[] = $data[$field];
                    }
                    continue;
                }

                $fields[] = "$field = ?";
                $values[] = $data[$field];
            }
        }

        // Also support notes/invoice_number mapping to note
        if (isset($data['notes']) || isset($data['invoice_number'])) {
            $fields[] = "note = ?";
            $values[] = $data['notes'] ?? $data['invoice_number'] ?? null;
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
        $dateColumnExpr = $this->getDateColumnExpression('si');
        $dateSelectAlias = $this->getSelectDateAlias('si');

        $orderClause = "ORDER BY {$dateColumnExpr} DESC";
        if ($this->getDateColumnName() !== 'created_at') {
            $orderClause .= ', si.created_at DESC';
        }
        
        $sql = "SELECT si.*, 
                       s.name as supplier_name,
                       u.name as user_name{$dateSelectAlias}
                FROM {$this->table} si
                LEFT JOIN suppliers s ON si.supplier_id = s.id
                LEFT JOIN users u ON si.created_by = u.id
                WHERE si.material_id = ?
                {$orderClause}
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
        $dateColumnExpr = $this->getDateColumnExpression('si');
        $dateSelectAlias = $this->getSelectDateAlias('si');

        $orderClause = "ORDER BY {$dateColumnExpr} DESC";
        if ($this->getDateColumnName() !== 'created_at') {
            $orderClause .= ', si.created_at DESC';
        }
        
        $sql = "SELECT si.*, 
                       m.code as material_code,
                       m.name as material_name,
                       m.unit as material_unit,
                       u.name as user_name{$dateSelectAlias}
                FROM {$this->table} si
                LEFT JOIN materials m ON si.material_id = m.id
                LEFT JOIN users u ON si.created_by = u.id
                WHERE si.supplier_id = ?
                {$orderClause}
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
        $dateColumnExpr = $this->getDateColumnExpression('si');
        $dateSelectAlias = $this->getSelectDateAlias('si');

        $orderClause = "ORDER BY {$dateColumnExpr} DESC";
        if ($this->getDateColumnName() !== 'created_at') {
            $orderClause .= ', si.created_at DESC';
        }
        
        $sql = "SELECT si.*, 
                       m.code as material_code,
                       m.name as material_name,
                       s.name as supplier_name,
                       u.name as user_name{$dateSelectAlias}
                FROM {$this->table} si
                LEFT JOIN materials m ON si.material_id = m.id
                LEFT JOIN suppliers s ON si.supplier_id = s.id
                LEFT JOIN users u ON si.created_by = u.id
                WHERE DATE({$dateColumnExpr}) BETWEEN ? AND ?
                {$orderClause}
                LIMIT ? OFFSET ?";
        
        $stmt = $this->query($sql, [$dateFrom, $dateTo, $perPage, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get today's transactions
     */
    public function getToday()
    {
        $dateColumnExpr = $this->getDateColumnExpression('si');
        $dateSelectAlias = $this->getSelectDateAlias('si');
        $sql = "SELECT si.*, 
                       m.code as material_code,
                       m.name as material_name,
                       m.unit as material_unit,
                       s.name as supplier_name,
                       u.name as user_name{$dateSelectAlias}
                FROM {$this->table} si
                LEFT JOIN materials m ON si.material_id = m.id
                LEFT JOIN suppliers s ON si.supplier_id = s.id
                LEFT JOIN users u ON si.created_by = u.id
                WHERE DATE({$dateColumnExpr}) = CURDATE()
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
        $dateColumn = $this->getDateColumnName();

        if ($dateFrom && $dateTo) {
            $where = "DATE({$dateColumn}) BETWEEN ? AND ?";
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
        $dateColumnExpr = $this->getDateColumnExpression('si');

        if ($dateFrom && $dateTo) {
            $where = "DATE({$dateColumnExpr}) BETWEEN ? AND ?";
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
        $dateColumnExpr = $this->getDateColumnExpression('si');

        if ($dateFrom && $dateTo) {
            $where = "DATE({$dateColumnExpr}) BETWEEN ? AND ?";
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
        $dateColumn = $this->getDateColumnName();
        $sql = "SELECT 
                    MONTH({$dateColumn}) as month,
                    COUNT(*) as total_transactions,
                    SUM(quantity) as total_quantity,
                    SUM(total_price) as total_value
                FROM {$this->table}
                WHERE YEAR({$dateColumn}) = ?
                GROUP BY MONTH({$dateColumn})
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
        $dateColumn = $this->getDateColumnName();

        if ($materialId) {
            $where[] = 'material_id = ?';
            $params[] = $materialId;
        }

        if ($dateFrom && $dateTo) {
            $where[] = "DATE({$dateColumn}) BETWEEN ? AND ?";
            $params[] = $dateFrom;
            $params[] = $dateTo;
        }

        $whereClause = implode(' AND ', $where);

        $sql = "SELECT COALESCE(SUM(total_price), 0) as total FROM {$this->table} WHERE {$whereClause}";
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    /**
     * Get recent stock in transactions
     */
    public function getRecent($limit = 5)
    {
        $dateColumn = $this->getDateColumnName();
        
        $sql = "SELECT 
                    si.id,
                    si.quantity,
                    si.unit_price,
                    si.{$dateColumn} as txn_date,
                    si.reference_number,
                    si.note,
                    m.name as material_name,
                    m.unit,
                    s.name as supplier_name
                FROM {$this->table} si
                INNER JOIN materials m ON si.material_id = m.id
                LEFT JOIN suppliers s ON si.supplier_id = s.id
                ORDER BY si.created_at DESC
                LIMIT ?";
        
        $stmt = $this->query($sql, [$limit]);
        return $stmt->fetchAll();
    }
}
