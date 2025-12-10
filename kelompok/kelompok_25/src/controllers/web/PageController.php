<?php

/**
 * Generic placeholder controller for menu pages
 */

require_once ROOT_PATH . '/core/Controller.php';
require_once ROOT_PATH . '/middleware/AuthMiddleware.php';

class PageController extends Controller
{
    public function __construct()
    {
        AuthMiddleware::check();
    }

    private function renderPlaceholder($pageTitle, $description = 'Halaman ini masih dalam pengembangan.')
    {
        $this->view('pages/placeholder', [
            'title' => $pageTitle,
            'pageTitle' => $pageTitle,
            'description' => $description,
        ]);
    }

    public function materials()
    {
        $this->view('materials/index', ['title' => 'Data Bahan Baku']);
    }

    public function suppliers()
    {
        $this->view('suppliers/index', ['title' => 'Data Supplier']);
    }

    public function categories()
    {
        $this->view('categories/index', ['title' => 'Data Kategori']);
    }

    public function stockIn()
    {
        $this->view('stock-in/index', ['title' => 'Stok Masuk']);
    }

    public function stockOut()
    {
        $this->view('stock-out/index', ['title' => 'Stok Keluar']);
    }

    public function stockAdjustments()
    {
        $this->view('stock-adjustments/index', ['title' => 'Penyesuaian Stok']);
    }

    public function reportsStock()
    {
        try {
            require_once ROOT_PATH . '/models/Material.php';
            require_once ROOT_PATH . '/models/Category.php';
            require_once ROOT_PATH . '/config/database.php';

            $db = Database::getInstance()->getConnection();

            // Get filters
            $filters = [
                'search' => $_GET['search'] ?? '',
                'category' => $_GET['category'] ?? '',
                'status' => $_GET['status'] ?? ''
            ];

            // Use Material model's getStockReport method
            $materialModel = new Material();
            $allMaterials = $materialModel->getStockReport(
                $filters['search'],
                $filters['category'],
                $filters['status']
            );

            // Calculate summary
            $summary = [
                'total_items' => count($allMaterials),
                'total_value' => array_sum(array_map(function($m) {
                    return $m['total_value'] ?? 0;
                }, $allMaterials)),
                'restock_needed' => count(array_filter($allMaterials, function($m) {
                    return ($m['current_stock'] ?? 0) <= 0;
                })),
                'almost_empty' => count(array_filter($allMaterials, function($m) {
                    return ($m['current_stock'] ?? 0) > 0 && ($m['current_stock'] ?? 0) <= ($m['min_stock'] ?? 0);
                }))
            ];

            // Get all categories for filter dropdown
            $categoryModel = new Category();
            $categories = $categoryModel->getAll() ?? [];

            $this->view('reports/stock', [
                'title' => 'Laporan Stok',
                'materials' => $allMaterials,
                'categories' => $categories,
                'summary' => $summary,
                'filters' => $filters
            ]);
        } catch (Exception $e) {
            error_log("Reports Stock Error: " . $e->getMessage());
            $this->view('reports/stock', [
                'title' => 'Laporan Stok',
                'materials' => [],
                'categories' => [],
                'summary' => [
                    'total_items' => 0,
                    'total_value' => 0,
                    'restock_needed' => 0,
                    'almost_empty' => 0
                ],
                'filters' => ['search' => '', 'category' => '', 'status' => '']
            ]);
        }
    }

    public function reportsTransactions()
    {
        try {
            require_once ROOT_PATH . '/models/StockIn.php';
            require_once ROOT_PATH . '/models/StockOut.php';
            require_once ROOT_PATH . '/models/StockAdjustment.php';
            require_once ROOT_PATH . '/config/database.php';

            $db = Database::getInstance()->getConnection();

            // Get filters
            $filters = [
                'type' => $_GET['type'] ?? 'all',
                'start_date' => $_GET['start_date'] ?? date('Y-m-01'),
                'end_date' => $_GET['end_date'] ?? date('Y-m-d')
            ];

            $transactions = [];

            // Get stock in transactions
            if ($filters['type'] === 'all' || $filters['type'] === 'stock_in') {
                $stockInModel = new StockIn();
                $stockIns = $stockInModel->getByDateRange($filters['start_date'], $filters['end_date']);
                if (!empty($stockIns)) {
                    foreach ($stockIns as $item) {
                        $transactions[] = [
                            'date' => $item['transaction_date'] ?? $item['created_at'],
                            'type' => 'stock_in',
                            'material_name' => $item['material_name'] ?? 'Unknown',
                            'quantity' => $item['quantity'] ?? 0,
                            'unit' => $item['unit'] ?? 'pcs',
                            'value' => ($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0)
                        ];
                    }
                }
            }

            // Get stock out transactions
            if ($filters['type'] === 'all' || $filters['type'] === 'stock_out') {
                $stockOutModel = new StockOut($db);
                $stockOuts = $stockOutModel->getByDateRange($filters['start_date'], $filters['end_date']);
                if (!empty($stockOuts)) {
                    foreach ($stockOuts as $item) {
                        $transactions[] = [
                            'date' => $item['transaction_date'] ?? $item['created_at'],
                            'type' => 'stock_out',
                            'material_name' => $item['material_name'] ?? 'Unknown',
                            'quantity' => $item['quantity'] ?? 0,
                            'unit' => $item['unit'] ?? 'pcs',
                            'value' => 0
                        ];
                    }
                }
            }

            // Get adjustments
            if ($filters['type'] === 'all' || $filters['type'] === 'adjustment') {
                $stockAdjustmentModel = new StockAdjustment($db);
                try {
                    $adjustmentsResult = $stockAdjustmentModel->getAll(1, 9999, [
                        'start_date' => $filters['start_date'],
                        'end_date' => $filters['end_date']
                    ]);
                    if (!empty($adjustmentsResult['data'])) {
                        foreach ($adjustmentsResult['data'] as $item) {
                            $transactions[] = [
                                'date' => $item['adjustment_date'] ?? $item['created_at'],
                                'type' => 'adjustment',
                                'material_name' => $item['material_name'] ?? 'Unknown',
                                'quantity' => abs($item['difference'] ?? 0),
                                'unit' => $item['unit'] ?? 'pcs',
                                'value' => 0
                            ];
                        }
                    }
                } catch (Exception $e) {
                    error_log("Error getting adjustments: " . $e->getMessage());
                }
            }

            // Sort by date descending
            usort($transactions, function($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });

            // Calculate summary
            $summary = [
                'total_transactions' => count($transactions),
                'total_stock_in' => array_sum(array_map(function($t) {
                    return $t['type'] === 'stock_in' ? $t['value'] : 0;
                }, $transactions)),
                'total_stock_out' => count(array_filter($transactions, function($t) {
                    return $t['type'] === 'stock_out';
                })),
                'total_adjustments' => count(array_filter($transactions, function($t) {
                    return $t['type'] === 'adjustment';
                }))
            ];

            $this->view('reports/transactions', [
                'title' => 'Laporan Transaksi',
                'transactions' => $transactions,
                'summary' => $summary,
                'filters' => $filters
            ]);
        } catch (Exception $e) {
            error_log("Reports Transactions Error: " . $e->getMessage());
            $this->view('reports/transactions', [
                'title' => 'Laporan Transaksi',
                'transactions' => [],
                'summary' => [
                    'total_transactions' => 0,
                    'total_stock_in' => 0,
                    'total_stock_out' => 0,
                    'total_adjustments' => 0
                ],
                'filters' => ['type' => 'all', 'start_date' => date('Y-m-01'), 'end_date' => date('Y-m-d')]
            ]);
        }
    }

    public function reportsLowStock()
    {
        $this->renderPlaceholder('Bahan Hampir Habis', 'Pantau bahan yang perlu restock pada halaman ini.');
    }

    public function roles()
    {
        $this->renderPlaceholder('Manajemen Role', 'Pengaturan role & akses pengguna akan dibuat di sini.');
    }

    public function profile()
    {
        $this->renderPlaceholder('Profil Saya', 'Perbarui informasi profil pribadi Anda di halaman ini.');
    }
}
