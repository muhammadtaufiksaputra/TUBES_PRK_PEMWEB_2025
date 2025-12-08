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
        $this->renderPlaceholder('Penyesuaian Stok', 'Fitur penyesuaian stok akan segera hadir.');
    }

    public function reportsStock()
    {
        $this->renderPlaceholder('Laporan Stok', 'Laporan stok akan ditampilkan di halaman ini.');
    }

    public function reportsTransactions()
    {
        $this->renderPlaceholder('Laporan Transaksi', 'Rekap transaksi stok akan tersedia di sini.');
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
