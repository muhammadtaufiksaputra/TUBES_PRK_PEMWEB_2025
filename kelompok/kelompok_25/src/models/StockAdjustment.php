<?php
class StockAdjustment extends Model {
    protected $table = 'stock_adjustments';

    public function getAll() {
        $sql = "SELECT sa.*, m.name as material_name, m.unit, u.name as created_by_name 
                FROM {$this->table} sa
                LEFT JOIN materials m ON sa.material_id = m.id
                LEFT JOIN users u ON sa.created_by = u.id
                ORDER BY sa.adjustment_date DESC, sa.created_at DESC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $sql = "INSERT INTO {$this->table} (material_id, old_stock, new_stock, difference, reason, adjustment_date, created_by) 
                VALUES (:material_id, :old_stock, :new_stock, :difference, :reason, :adjustment_date, :created_by)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    public function updateMaterialStock($materialId, $newStock) {
        $sql = "UPDATE materials SET current_stock = :new_stock WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['new_stock' => $newStock, 'id' => $materialId]);
    }
}
