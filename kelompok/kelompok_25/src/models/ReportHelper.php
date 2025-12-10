<?php

require_once ROOT_PATH . '/core/Model.php';

class ReportHelper extends Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get inventory summary for dashboard
     */
    public function getInventorySummary()
    {
        // Total materials
        $totalMaterials = $this->query("SELECT COUNT(*) as count FROM materials WHERE is_active = 1")->fetch()['count'];

        // Total stock value
        $stockValue = $this->query("
            SELECT COALESCE(SUM(m.current_stock * COALESCE(latest_price.unit_price, 0)), 0) as total_value
            FROM materials m
            LEFT JOIN (
                SELECT material_id, unit_price,
                       ROW_NUMBER() OVER (PARTITION BY material_id ORDER BY created_at DESC) as rn
                FROM stock_in WHERE unit_price IS NOT NULL
            ) latest_price ON m.id = latest_price.material_id AND latest_price.rn = 1
            WHERE m.is_active = 1
        ")->fetch()['total_value'];

        // Low stock count
        $lowStockCount = $this->query("
            SELECT COUNT(*) as count FROM materials 
            WHERE is_active = 1 AND current_stock <= min_stock
        ")->fetch()['count'];

        // Out of stock count
        $outOfStockCount = $this->query("
            SELECT COUNT(*) as count FROM materials 
            WHERE is_active = 1 AND current_stock = 0
        ")->fetch()['count'];

        // Recent stock in (7 days)
        $recentStockIn = $this->query("
            SELECT COALESCE(SUM(quantity), 0) as total_quantity,
                   COALESCE(SUM(total_price), 0) as total_value
            FROM stock_in 
            WHERE txn_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ")->fetch();

        // Recent stock out (7 days)
        $recentStockOut = $this->query("
            SELECT COALESCE(SUM(quantity), 0) as total_quantity
            FROM stock_out 
            WHERE txn_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ")->fetch();

        return [
            'total_materials' => (int)$totalMaterials,
            'total_stock_value' => (float)$stockValue,
            'low_stock_count' => (int)$lowStockCount,
            'out_of_stock_count' => (int)$outOfStockCount,
            'recent_stock_in' => [
                'quantity' => (float)$recentStockIn['total_quantity'],
                'value' => (float)$recentStockIn['total_value']
            ],
            'recent_stock_out' => [
                'quantity' => (float)$recentStockOut['total_quantity']
            ]
        ];
    }

    /**
     * Get transaction summary by date range
     */
    public function getTransactionSummary($startDate, $endDate)
    {
        // Stock in summary
        $stockInData = $this->query("
            SELECT 
                COALESCE(SUM(quantity), 0) as total_quantity,
                COALESCE(SUM(total_price), 0) as total_value,
                COUNT(*) as transaction_count
            FROM stock_in 
            WHERE txn_date BETWEEN ? AND ?
        ", [$startDate, $endDate])->fetch();

        // Stock out summary
        $stockOutData = $this->query("
            SELECT 
                COALESCE(SUM(quantity), 0) as total_quantity,
                COUNT(*) as transaction_count
            FROM stock_out 
            WHERE txn_date BETWEEN ? AND ?
        ", [$startDate, $endDate])->fetch();

        // Top materials by stock in
        $topMaterialsIn = $this->query("
            SELECT m.name, SUM(si.quantity) as total_quantity, SUM(si.total_price) as total_value
            FROM stock_in si
            JOIN materials m ON si.material_id = m.id
            WHERE si.txn_date BETWEEN ? AND ?
            GROUP BY m.id, m.name
            ORDER BY total_quantity DESC
            LIMIT 10
        ", [$startDate, $endDate])->fetchAll();

        // Top suppliers
        $topSuppliers = $this->query("
            SELECT s.name, COUNT(*) as transaction_count, SUM(si.total_price) as total_value
            FROM stock_in si
            JOIN suppliers s ON si.supplier_id = s.id
            WHERE si.txn_date BETWEEN ? AND ?
            GROUP BY s.id, s.name
            ORDER BY total_value DESC
            LIMIT 10
        ", [$startDate, $endDate])->fetchAll();

        return [
            'total_stock_in' => [
                'quantity' => (float)$stockInData['total_quantity'],
                'value' => (float)$stockInData['total_value'],
                'transaction_count' => (int)$stockInData['transaction_count']
            ],
            'total_stock_out' => [
                'quantity' => (float)$stockOutData['total_quantity'],
                'transaction_count' => (int)$stockOutData['transaction_count']
            ],
            'net_change' => (float)$stockInData['total_quantity'] - (float)$stockOutData['total_quantity'],
            'by_material' => $topMaterialsIn,
            'by_supplier' => $topSuppliers
        ];
    }

    /**
     * Get material trend data for chart
     */
    public function getMaterialTrend($materialId, $days = 30)
    {
        $sql = "
            SELECT 
                DATE(created_at) as date,
                'stock_in' as type,
                SUM(quantity) as quantity
            FROM stock_in 
            WHERE material_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            GROUP BY DATE(created_at)
            
            UNION ALL
            
            SELECT 
                DATE(created_at) as date,
                'stock_out' as type,
                SUM(quantity) as quantity
            FROM stock_out 
            WHERE material_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            GROUP BY DATE(created_at)
            
            ORDER BY date ASC
        ";

        return $this->query($sql, [$materialId, $days, $materialId, $days])->fetchAll();
    }

    /**
     * Get category distribution for pie chart
     */
    public function getCategoryDistribution()
    {
        return $this->query("
            SELECT 
                COALESCE(c.name, 'Uncategorized') as category_name,
                COUNT(m.id) as material_count,
                SUM(m.current_stock) as total_stock
            FROM materials m
            LEFT JOIN categories c ON m.category_id = c.id
            WHERE m.is_active = 1
            GROUP BY c.id, c.name
            ORDER BY material_count DESC
        ")->fetchAll();
    }

    /**
     * Get supplier performance ranking
     */
    public function getSupplierPerformance($startDate, $endDate)
    {
        return $this->query("
            SELECT 
                s.name,
                COUNT(si.id) as transaction_count,
                SUM(si.quantity) as total_quantity,
                SUM(si.total_price) as total_value,
                AVG(si.unit_price) as avg_unit_price
            FROM suppliers s
            JOIN stock_in si ON s.id = si.supplier_id
            WHERE si.txn_date BETWEEN ? AND ?
            GROUP BY s.id, s.name
            ORDER BY total_value DESC
        ", [$startDate, $endDate])->fetchAll();
    }

    /**
     * Get materials with low stock
     */
    public function getLowStockMaterials()
    {
        return $this->query("
            SELECT 
                m.id,
                m.code,
                m.name,
                m.unit,
                m.current_stock,
                m.min_stock,
                c.name as category_name
            FROM materials m
            LEFT JOIN categories c ON m.category_id = c.id
            WHERE m.is_active = 1 AND m.current_stock <= m.min_stock
            ORDER BY (m.current_stock / NULLIF(m.min_stock, 0)) ASC
        ")->fetchAll();
    }

    /**
     * Get stock movement detail for specific material
     */
    public function getStockMovement($materialId, $startDate, $endDate)
    {
        $sql = "
            SELECT 
                'IN' as type,
                si.txn_date as date,
                si.quantity,
                si.unit_price,
                si.total_price,
                si.reference_number,
                s.name as supplier_name,
                si.note,
                si.created_at
            FROM stock_in si
            LEFT JOIN suppliers s ON si.supplier_id = s.id
            WHERE si.material_id = ? AND si.txn_date BETWEEN ? AND ?
            
            UNION ALL
            
            SELECT 
                'OUT' as type,
                so.txn_date as date,
                so.quantity,
                NULL as unit_price,
                NULL as total_price,
                so.reference_number,
                so.usage_type as supplier_name,
                so.note,
                so.created_at
            FROM stock_out so
            WHERE so.material_id = ? AND so.txn_date BETWEEN ? AND ?
            
            ORDER BY date DESC, created_at DESC
        ";

        return $this->query($sql, [$materialId, $startDate, $endDate, $materialId, $startDate, $endDate])->fetchAll();
    }

    /**
     * Get top materials by criteria
     */
    public function getTopMaterials($type = 'value', $limit = 10)
    {
        switch ($type) {
            case 'quantity':
                $orderBy = 'm.current_stock DESC';
                break;
            case 'usage':
                $orderBy = 'total_usage DESC';
                break;
            default:
                $orderBy = 'stock_value DESC';
        }

        return $this->query("
            SELECT 
                m.id,
                m.code,
                m.name,
                m.current_stock,
                COALESCE(latest_price.unit_price, 0) as unit_price,
                (m.current_stock * COALESCE(latest_price.unit_price, 0)) as stock_value,
                COALESCE(usage_data.total_usage, 0) as total_usage
            FROM materials m
            LEFT JOIN (
                SELECT material_id, unit_price,
                       ROW_NUMBER() OVER (PARTITION BY material_id ORDER BY created_at DESC) as rn
                FROM stock_in WHERE unit_price IS NOT NULL
            ) latest_price ON m.id = latest_price.material_id AND latest_price.rn = 1
            LEFT JOIN (
                SELECT material_id, SUM(quantity) as total_usage
                FROM stock_out
                WHERE txn_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY material_id
            ) usage_data ON m.id = usage_data.material_id
            WHERE m.is_active = 1
            ORDER BY {$orderBy}
            LIMIT ?
        ", [$limit])->fetchAll();
    }

    /**
     * Get stock value by category
     */
    public function getStockValueByCategory()
    {
        return $this->query("
            SELECT 
                COALESCE(c.name, 'Uncategorized') as category_name,
                COUNT(m.id) as material_count,
                SUM(m.current_stock * COALESCE(latest_price.unit_price, 0)) as total_value
            FROM materials m
            LEFT JOIN categories c ON m.category_id = c.id
            LEFT JOIN (
                SELECT material_id, unit_price,
                       ROW_NUMBER() OVER (PARTITION BY material_id ORDER BY created_at DESC) as rn
                FROM stock_in WHERE unit_price IS NOT NULL
            ) latest_price ON m.id = latest_price.material_id AND latest_price.rn = 1
            WHERE m.is_active = 1
            GROUP BY c.id, c.name
            ORDER BY total_value DESC
        ")->fetchAll();
    }
}