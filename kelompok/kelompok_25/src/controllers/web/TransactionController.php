<?php
require_once __DIR__ . '/../../models/Transaction.php';

class TransactionController extends Controller {
    private $transactionModel;

    public function __construct() {
        $this->transactionModel = new Transaction();
    }

    public function report() {
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        $type = $_GET['type'] ?? 'all';

        $transactions = $this->transactionModel->getTransactionReport($startDate, $endDate, $type);
        $summary = $this->transactionModel->getSummary($startDate, $endDate);

        $this->view('reports/transactions', [
            'transactions' => $transactions,
            'summary' => $summary,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'type' => $type
            ]
        ]);
    }

    public function getTrendData() {
        $days = $_GET['days'] ?? 7;
        $data = $this->transactionModel->getTrendData($days);
        
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    public function exportCSV() {
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        $type = $_GET['type'] ?? 'all';

        $transactions = $this->transactionModel->getTransactionReport($startDate, $endDate, $type);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=laporan_transaksi_' . date('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Tanggal', 'Jenis', 'Nama Bahan', 'Jumlah', 'Nilai']);

        foreach ($transactions as $txn) {
            fputcsv($output, [
                $txn['date'],
                $txn['type'] === 'stock_in' ? 'Stok Masuk' : ($txn['type'] === 'stock_out' ? 'Stok Keluar' : 'Penyesuaian'),
                $txn['material_name'],
                $txn['quantity'] . ' ' . $txn['unit'],
                'Rp ' . number_format($txn['value'], 0, ',', '.')
            ]);
        }

        fclose($output);
        exit;
    }
}
