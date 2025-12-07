<?php

require_once __DIR__ . '/../../models/Material.php';

class ReportController extends Controller {
    
    public function stockReport() {
        $materialModel = new Material();
        
        $search = $_GET['search'] ?? '';
        $categoryFilter = $_GET['category'] ?? '';
        $statusFilter = $_GET['status'] ?? '';
        
        $materials = $materialModel->getStockReport($search, $categoryFilter, $statusFilter);
        $summary = $materialModel->getStockSummary();
        $categories = $materialModel->getCategories();
        
        $this->view('reports/stock', [
            'materials' => $materials,
            'summary' => $summary,
            'categories' => $categories,
            'filters' => [
                'search' => $search,
                'category' => $categoryFilter,
                'status' => $statusFilter
            ]
        ]);
    }

    public function exportExcel() {
        $materialModel = new Material();
        
        $search = $_GET['search'] ?? '';
        $categoryFilter = $_GET['category'] ?? '';
        $statusFilter = $_GET['status'] ?? '';
        
        $materials = $materialModel->getStockReport($search, $categoryFilter, $statusFilter);
        
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="laporan_stok_' . date('Y-m-d') . '.xls"');
        header('Cache-Control: max-age=0');
        
        echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
        echo '<head><meta charset="UTF-8"></head>';
        echo '<body>';
        echo '<table border="1">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Nama Bahan</th>';
        echo '<th>Kategori</th>';
        echo '<th>Stok Tersedia</th>';
        echo '<th>Stok Minimum</th>';
        echo '<th>Harga/Unit</th>';
        echo '<th>Total Nilai</th>';
        echo '<th>Status</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        foreach ($materials as $material) {
            $status = $material['current_stock'] > $material['min_stock'] ? 'Aman' : 
                     ($material['current_stock'] > 0 ? 'Hampir Habis' : 'Perlu Restock');
            
            echo '<tr>';
            echo '<td>' . htmlspecialchars($material['name']) . '</td>';
            echo '<td>' . htmlspecialchars($material['category_name'] ?? '-') . '</td>';
            echo '<td>' . number_format($material['current_stock'], 0, ',', '.') . ' ' . $material['unit'] . '</td>';
            echo '<td>' . number_format($material['min_stock'], 0, ',', '.') . ' ' . $material['unit'] . '</td>';
            echo '<td>Rp ' . number_format($material['unit_price'], 0, ',', '.') . '</td>';
            echo '<td>Rp ' . number_format($material['total_value'], 0, ',', '.') . '</td>';
            echo '<td>' . $status . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</body>';
        echo '</html>';
        exit;
    }
}
