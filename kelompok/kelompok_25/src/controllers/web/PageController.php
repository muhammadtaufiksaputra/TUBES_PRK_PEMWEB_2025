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

            // Get filters
            $filters = [
                'search' => $_GET['search'] ?? '',
                'category' => $_GET['category'] ?? '',
                'status' => $_GET['status'] ?? ''
            ];

            // Initialize Material model
            $materialModel = new Material();
            
            // Get filtered stock report
            $materials = $materialModel->getStockReport(
                $filters['search'],
                $filters['category'],
                $filters['status']
            );

            // Get summary statistics
            $summary = $materialModel->getStockSummary();
            
            // Get categories for filter dropdown
            $categories = $materialModel->getCategories();

            $this->view('reports/stock', [
                'title' => 'Laporan Stok',
                'materials' => $materials ?? [],
                'categories' => $categories ?? [],
                'summary' => $summary ?? [
                    'total_items' => 0,
                    'total_value' => 0,
                    'restock_needed' => 0,
                    'almost_empty' => 0
                ],
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
            require_once ROOT_PATH . '/models/Transaction.php';

            // Get filters
            $filters = [
                'type' => $_GET['type'] ?? 'all',
                'start_date' => $_GET['start_date'] ?? date('Y-m-01'),
                'end_date' => $_GET['end_date'] ?? date('Y-m-d')
            ];

            // Initialize Transaction model
            $transactionModel = new Transaction();
            
            // Get consolidated transactions
            $allTransactions = $transactionModel->getTransactionReport(
                $filters['start_date'],
                $filters['end_date'],
                $filters['type'] !== 'all' ? $filters['type'] : null
            );

            // Filter by type if specified
            $transactions = [];
            if (!empty($allTransactions)) {
                foreach ($allTransactions as $txn) {
                    if ($filters['type'] === 'all' || $txn['type'] === $filters['type']) {
                        // Map column names for view
                        $transactions[] = [
                            'date' => $txn['date'] ?? date('Y-m-d'),
                            'type' => $txn['type'] ?? 'unknown',
                            'material_name' => $txn['material_name'] ?? 'Unknown',
                            'quantity' => $txn['quantity'] ?? 0,
                            'unit' => $txn['unit'] ?? 'pcs',
                            'value' => $txn['value'] ?? 0
                        ];
                    }
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
                    return $t['type'] === 'stock_in' ? (float)$t['value'] : 0;
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
        try {
            require_once ROOT_PATH . '/models/Material.php';
            require_once ROOT_PATH . '/models/Category.php';

            // Get categories for filter dropdown
            $categoryModel = new Category();
            $categories = $categoryModel->getAll();

            $this->view('reports/low-stock', [
                'title' => 'Bahan Hampir Habis',
                'categories' => $categories ?? []
            ]);
        } catch (Exception $e) {
            error_log("Reports Low Stock Error: " . $e->getMessage());
            $this->view('reports/low-stock', [
                'title' => 'Bahan Hampir Habis',
                'categories' => []
            ]);
        }
    }

    public function roles()
    {
        $this->view('roles/index', ['title' => 'Manajemen Role']);
    }

    public function users()
    {
        $this->view('users/index', ['title' => 'Manajemen User']);
    }

    public function profile()
    {
        $this->renderPlaceholder('Profil Saya', 'Perbarui informasi profil pribadi Anda di halaman ini.');
    }
}
