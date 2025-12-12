<?php

/**
 * Dashboard Controller
 */

require_once ROOT_PATH . '/core/Controller.php';
require_once ROOT_PATH . '/models/Material.php';
require_once ROOT_PATH . '/models/StockIn.php';
require_once ROOT_PATH . '/models/StockOut.php';
require_once ROOT_PATH . '/models/ActivityLog.php';
require_once ROOT_PATH . '/config/database.php';

class DashboardController extends Controller
{
    private $materialModel;
    private $stockInModel;
    private $stockOutModel;
    private $activityLogModel;

    public function __construct()
    {
        $this->materialModel = new Material();
        $this->stockInModel = new StockIn();
        // StockOut requires PDO instance
        $db = Database::getInstance()->getConnection();
        $this->stockOutModel = new StockOut($db);
        $this->activityLogModel = new ActivityLog();
    }

    /**
     * Show dashboard
     */
    public function index()
    {
        // Get statistics
        $stats = $this->getDashboardStats();
        
        // Get low stock materials
        $lowStock = $this->materialModel->getLowStockMaterials(5);
        
        // Get recent activities
        $recentActivities = $this->activityLogModel->getRecent(8);
        
        // Get recent stock transactions
        $recentStockIn = $this->stockInModel->getRecent(3);
        $recentStockOut = $this->stockOutModel->getRecent(3);

        $data = [
            'title' => 'Dashboard',
            'user' => current_user(),
            'stats' => $stats,
            'lowStock' => $lowStock,
            'recentActivities' => $recentActivities,
            'recentStockIn' => $recentStockIn,
            'recentStockOut' => $recentStockOut
        ];

        $this->view('dashboard/index', $data);
    }

    /**
     * Get dashboard statistics
     */
    private function getDashboardStats()
    {
        $db = Database::getInstance()->getConnection();
        
        // Total materials
        $stmt = $db->query("SELECT COUNT(*) as total FROM materials WHERE is_active = 1");
        $totalMaterials = $stmt->fetch()['total'];
        
        // Stock in this month
        $stmt = $db->query("SELECT COUNT(*) as total, COALESCE(SUM(quantity), 0) as total_qty 
                           FROM stock_in 
                           WHERE MONTH(txn_date) = MONTH(CURRENT_DATE()) 
                           AND YEAR(txn_date) = YEAR(CURRENT_DATE())");
        $stockInThisMonth = $stmt->fetch();
        
        // Stock out this month
        $stmt = $db->query("SELECT COUNT(*) as total, COALESCE(SUM(quantity), 0) as total_qty 
                           FROM stock_out 
                           WHERE MONTH(txn_date) = MONTH(CURRENT_DATE()) 
                           AND YEAR(txn_date) = YEAR(CURRENT_DATE())");
        $stockOutThisMonth = $stmt->fetch();
        
        // Low stock count
        $stmt = $db->query("SELECT COUNT(*) as total FROM materials 
                           WHERE is_active = 1 AND current_stock <= min_stock");
        $lowStockCount = $stmt->fetch()['total'];
        
        // Total stock value
        $stmt = $db->query("SELECT SUM(
                               m.current_stock * COALESCE(
                                   (SELECT unit_price FROM stock_in 
                                    WHERE material_id = m.id 
                                    ORDER BY created_at DESC LIMIT 1), 0
                               )
                           ) as total_value
                           FROM materials m WHERE m.is_active = 1");
        $totalValue = $stmt->fetch()['total_value'] ?? 0;
        
        // Categories count
        $stmt = $db->query("SELECT COUNT(*) as total FROM categories");
        $totalCategories = $stmt->fetch()['total'];
        
        // Suppliers count
        $stmt = $db->query("SELECT COUNT(*) as total FROM suppliers WHERE is_active = 1");
        $totalSuppliers = $stmt->fetch()['total'];
        
        // Out of stock
        $stmt = $db->query("SELECT COUNT(*) as total FROM materials 
                           WHERE is_active = 1 AND current_stock = 0");
        $outOfStock = $stmt->fetch()['total'];

        return [
            'totalMaterials' => $totalMaterials,
            'stockInCount' => $stockInThisMonth['total'],
            'stockInQty' => $stockInThisMonth['total_qty'],
            'stockOutCount' => $stockOutThisMonth['total'],
            'stockOutQty' => $stockOutThisMonth['total_qty'],
            'lowStockCount' => $lowStockCount,
            'totalValue' => $totalValue,
            'totalCategories' => $totalCategories,
            'totalSuppliers' => $totalSuppliers,
            'outOfStock' => $outOfStock
        ];
    }
}
