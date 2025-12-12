<?php
class Transaction extends Model {
    protected $table = '';

    public function getTransactionReport($type = 'all', $startDate = null, $endDate = null) {
        $sql = "SELECT 
                    'stock_in' as type,
                    si.id,
                    si.txn_date as date,
                    m.name as material_name,
                    si.quantity,
                    m.unit,
                    si.total_price as value,
                    s.name as supplier_name
                FROM stock_in si
                JOIN materials m ON si.material_id = m.id
                LEFT JOIN suppliers s ON si.supplier_id = s.id
                WHERE 1=1";
        
        $params = [];
        
        if ($startDate) {
            $sql .= " AND si.txn_date >= ?";
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $sql .= " AND si.txn_date <= ?";
            $params[] = $endDate;
        }
        
        $sql .= " UNION ALL
                SELECT 
                    'stock_out' as type,
                    so.id,
                    so.txn_date as date,
                    m.name as material_name,
                    so.quantity,
                    m.unit,
                    0 as value,
                    '-' as supplier_name
                FROM stock_out so
                JOIN materials m ON so.material_id = m.id
                WHERE 1=1";
        
        if ($startDate) {
            $sql .= " AND so.txn_date >= ?";
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $sql .= " AND so.txn_date <= ?";
            $params[] = $endDate;
        }
        
        $sql .= " UNION ALL
                SELECT 
                    'adjustment' as type,
                    sa.id,
                    sa.adjustment_date as date,
                    m.name as material_name,
                    0 as quantity,
                    m.unit,
                    0 as value,
                    sa.reason as supplier_name
                FROM stock_adjustments sa
                JOIN materials m ON sa.material_id = m.id
                WHERE 1=1";
        
        if ($startDate) {
            $sql .= " AND sa.adjustment_date >= ?";
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $sql .= " AND sa.adjustment_date <= ?";
            $params[] = $endDate;
        }
        
        if ($type && $type !== 'all') {
            $sql = "SELECT * FROM ($sql) as transactions WHERE type = ?";
            $params[] = $type;
        }
        
        $sql .= " ORDER BY date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getSummary($startDate = null, $endDate = null) {
        $whereStockIn = "1=1";
        $whereStockOut = "1=1";
        $whereAdjustments = "1=1";
        $paramsStockIn = [];
        $paramsStockOut = [];
        $paramsAdjustments = [];
        
        if ($startDate) {
            $whereStockIn .= " AND txn_date >= ?";
            $paramsStockIn[] = $startDate;
            
            $whereStockOut .= " AND txn_date >= ?";
            $paramsStockOut[] = $startDate;
            
            $whereAdjustments .= " AND adjustment_date >= ?";
            $paramsAdjustments[] = $startDate;
        }
        if ($endDate) {
            $whereStockIn .= " AND txn_date <= ?";
            $paramsStockIn[] = $endDate;
            
            $whereStockOut .= " AND txn_date <= ?";
            $paramsStockOut[] = $endDate;
            
            $whereAdjustments .= " AND adjustment_date <= ?";
            $paramsAdjustments[] = $endDate;
        }
        
        // Count total stock in
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM stock_in WHERE $whereStockIn");
        $stmt->execute($paramsStockIn);
        $countStockIn = $stmt->fetch()['total'] ?? 0;
        
        // Count total stock out
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM stock_out WHERE $whereStockOut");
        $stmt->execute($paramsStockOut);
        $countStockOut = $stmt->fetch()['total'] ?? 0;
        
        // Count total adjustments
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM stock_adjustments WHERE $whereAdjustments");
        $stmt->execute($paramsAdjustments);
        $totalAdjustments = $stmt->fetch()['total'] ?? 0;
        
        // Total all transactions
        $totalTransactions = $countStockIn + $countStockOut + $totalAdjustments;
        
        // Sum total price from stock in
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(total_price), 0) as total FROM stock_in WHERE $whereStockIn");
        $stmt->execute($paramsStockIn);
        $totalStockIn = $stmt->fetch()['total'] ?? 0;
        
        // Count stock out
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM stock_out WHERE $whereStockOut");
        $stmt->execute($paramsStockOut);
        $totalStockOut = $stmt->fetch()['total'] ?? 0;
        
        return [
            'total_transactions' => $totalTransactions,
            'total_stock_in' => $totalStockIn,
            'total_stock_out' => $totalStockOut,
            'total_adjustments' => $totalAdjustments
        ];
    }

    public function getTrendData($days = 7) {
        $sql = "SELECT 
                    DATE(txn_date) as date,
                    COALESCE(SUM(total_price), 0) as stock_in_value
                FROM stock_in
                WHERE txn_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                GROUP BY DATE(txn_date)
                ORDER BY date ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$days]);
        $stockInData = $stmt->fetchAll();
        
        $sql = "SELECT 
                    DATE(txn_date) as date,
                    COUNT(*) as stock_out_count
                FROM stock_out
                WHERE txn_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                GROUP BY DATE(txn_date)
                ORDER BY date ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$days]);
        $stockOutData = $stmt->fetchAll();
        
        return [
            'stock_in' => $stockInData,
            'stock_out' => $stockOutData
        ];
    }
}
