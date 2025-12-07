<?php

class StockAdjustmentController extends Controller
{
    private $stockAdjustmentModel;
    private $materialModel;

    public function __construct()
    {
        $this->stockAdjustmentModel = new StockAdjustment();
        $this->materialModel = new Material();
    }

    public function index()
    {
        $adjustments = $this->stockAdjustmentModel->getAll();
        $materials = $this->materialModel->getAll();
        
        $this->view('stock_adjustments/index', [
            'title' => 'Penyesuaian Stok',
            'adjustments' => $adjustments,
            'materials' => $materials
        ]);
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Invalid request'], 405);
            return;
        }

        $materialId = $_POST['material_id'] ?? null;
        $newStock = $_POST['new_stock'] ?? null;
        $reason = $_POST['reason'] ?? '';
        $adjustmentDate = $_POST['adjustment_date'] ?? date('Y-m-d');

        if (!$materialId || $newStock === null) {
            $this->json(['success' => false, 'message' => 'Data tidak lengkap'], 400);
            return;
        }

        $material = $this->materialModel->findById($materialId);
        if (!$material) {
            $this->json(['success' => false, 'message' => 'Bahan tidak ditemukan'], 404);
            return;
        }

        $oldStock = $material['current_stock'];
        $difference = $newStock - $oldStock;

        try {
            $this->stockAdjustmentModel->beginTransaction();

            $this->stockAdjustmentModel->create([
                'material_id' => $materialId,
                'old_stock' => $oldStock,
                'new_stock' => $newStock,
                'difference' => $difference,
                'reason' => $reason,
                'adjustment_date' => $adjustmentDate,
                'created_by' => $_SESSION['user_id'] ?? null
            ]);

            $this->stockAdjustmentModel->updateMaterialStock($materialId, $newStock);

            $this->stockAdjustmentModel->commit();
            $this->json(['success' => true, 'message' => 'Penyesuaian stok berhasil disimpan']);
        } catch (Exception $e) {
            $this->stockAdjustmentModel->rollback();
            $this->json(['success' => false, 'message' => 'Gagal menyimpan: ' . $e->getMessage()], 500);
        }
    }

    public function getMaterial($id)
    {
        $material = $this->materialModel->findById($id);
        if ($material) {
            $this->json(['success' => true, 'data' => $material]);
        } else {
            $this->json(['success' => false, 'message' => 'Material not found'], 404);
        }
    }
}
