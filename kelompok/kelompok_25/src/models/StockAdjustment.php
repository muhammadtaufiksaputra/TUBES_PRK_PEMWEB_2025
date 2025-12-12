<?php
// models/StockAdjustment.php
// Requires: Database::connect() -> PDO
// Optional: activity_logs table, materials.low_stock_threshold column

class StockAdjustment
{
    protected PDO $db;
    protected array $reasons = [
        'count_correction','damage','expiry','theft','system_error','other'
    ];

    public function __construct(PDO $db)
    {
        $this->db = $db;
        // ensure exceptions
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Create a stock adjustment:
     * $data: [
     *   material_id (int), new_stock (numeric), reason (string), notes (string), created_by (int)
     * ]
     *
     * Returns inserted adjustment row array.
     * Throws Exception on validation or DB error.
     */
    public function create(array $data): array
    {
        $this->validateCreatePayload($data);

        $this->db->beginTransaction();
        try {
            // lock material row
            $stmt = $this->db->prepare("SELECT id, current_stock FROM materials WHERE id = ? FOR UPDATE");
            $stmt->execute([(int)$data['material_id']]);
            $material = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$material) {
                throw new Exception("Material not found");
            }

            $oldStock = (float)$material['current_stock'];
            $newStock = (float)$data['new_stock'];
            if ($newStock < 0) {
                throw new Exception("new_stock cannot be negative");
            }

            $difference = $newStock - $oldStock;

            // insert adjustment record
            $ins = $this->db->prepare("
                INSERT INTO stock_adjustments
                (material_id, old_stock, new_stock, difference, reason, adjustment_date, created_by)
                VALUES (:material_id, :old_stock, :new_stock, :difference, :reason, CURDATE(), :created_by)
            ");
            $ins->execute([
                ':material_id' => (int)$data['material_id'],
                ':old_stock' => $oldStock,
                ':new_stock' => $newStock,
                ':difference' => $difference,
                ':reason' => $data['reason'],
                ':created_by' => (int)$data['created_by']
            ]);

            $adjustmentId = (int)$this->db->lastInsertId();

            // update materials.current_stock
            $upd = $this->db->prepare("UPDATE materials SET current_stock = :new_stock WHERE id = :id");
            $upd->execute([':new_stock' => $newStock, ':id' => (int)$data['material_id']]);

            // log critical activity
            $this->logActivity([
                'user_id' => (int)$data['created_by'],
                'action' => 'stock_adjustment.create',
                'message' => "Adjustment #{$adjustmentId} material_id={$data['material_id']} old={$oldStock} new={$newStock} diff={$difference}",
                'meta' => json_encode([
                    'adjustment_id' => $adjustmentId,
                    'material_id' => (int)$data['material_id'],
                    'old_stock' => $oldStock,
                    'new_stock' => $newStock,
                    'difference' => $difference,
                    'reason' => $data['reason']
                ])
            ]);

            $this->db->commit();

            return $this->findById($adjustmentId);
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Validate payload for create
     */
    protected function validateCreatePayload(array $data)
    {
        if (empty($data['material_id'])) {
            throw new Exception("material_id required");
        }
        if (!isset($data['new_stock'])) {
            throw new Exception("new_stock required");
        }
        if (!is_numeric($data['new_stock'])) {
            throw new Exception("new_stock must be numeric");
        }
        if (empty($data['reason']) || !in_array($data['reason'], $this->reasons, true)) {
            throw new Exception("reason invalid or required");
        }
        // notes column doesn't exist in table, removed validation
        if (empty($data['created_by'])) {
            throw new Exception("created_by required");
        }
    }

    /**
     * Log activity to activity_logs if exists else fallback to error_log
     */
    protected function logActivity(array $payload)
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO activity_logs (user_id, action, message, meta, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([
                $payload['user_id'],
                $payload['action'],
                $payload['message'],
                $payload['meta'] ?? null
            ]);
        } catch (Exception $e) {
            error_log("[ActivityLogFallback] " . json_encode($payload) . " - " . $e->getMessage());
        }
    }

    /**
     * Get paginated list with filters
     * filters: material_id, reason, start_date, end_date, q
     */
    public function getAll(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $where = [];
        $params = [];

        if (!empty($filters['material_id'])) {
            $where[] = "sa.material_id = :material_id";
            $params[':material_id'] = (int)$filters['material_id'];
        }
        if (!empty($filters['reason'])) {
            $where[] = "sa.reason = :reason";
            $params[':reason'] = $filters['reason'];
        }
        if (!empty($filters['start_date'])) {
            $where[] = "sa.adjustment_date >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $where[] = "sa.adjustment_date <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }
        if (!empty($filters['q'])) {
            $where[] = "(sa.notes LIKE :q OR u.name LIKE :q)";
            $params[':q'] = '%' . $filters['q'] . '%';
        }

        $whereSql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // total
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM stock_adjustments sa $whereSql");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $sql = "SELECT sa.*, m.name as material_name, m.code as material_code, u.name as adjusted_by_name
                FROM stock_adjustments sa
                LEFT JOIN materials m ON m.id = sa.material_id
                LEFT JOIN users u ON u.id = sa.created_by
                $whereSql
                ORDER BY sa.adjustment_date DESC, sa.created_at DESC
                LIMIT :offset, :perPage";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->bindValue(':perPage', (int)$perPage, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data' => $rows,
            'meta' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $total,
                'lastPage' => ceil($total / $perPage)
            ]
        ];
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT sa.*, m.name as material_name, u.name as adjusted_by_name FROM stock_adjustments sa LEFT JOIN materials m ON m.id = sa.material_id LEFT JOIN users u ON u.id = sa.created_by WHERE sa.id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getByMaterial(int $materialId, ?array $dateRange = null): array
    {
        $where = "WHERE sa.material_id = :material_id";
        $params = [':material_id' => $materialId];
        if ($dateRange && !empty($dateRange['start'])) {
            $where .= " AND sa.adjusted_at >= :start";
            $params[':start'] = $dateRange['start'] . ' 00:00:00';
        }
        if ($dateRange && !empty($dateRange['end'])) {
            $where .= " AND sa.adjusted_at <= :end";
            $params[':end'] = $dateRange['end'] . ' 23:59:59';
        }
        $stmt = $this->db->prepare("SELECT sa.*, u.name as adjusted_by_name FROM stock_adjustments sa LEFT JOIN users u ON u.id = sa.created_by $where ORDER BY sa.created_at DESC");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByReason(string $reason, ?array $dateRange = null): array
    {
        if (!in_array($reason, $this->reasons, true)) {
            throw new Exception("Invalid reason");
        }
        $where = "WHERE sa.reason = :reason";
        $params = [':reason' => $reason];
        if ($dateRange && !empty($dateRange['start'])) {
            $where .= " AND sa.adjusted_at >= :start";
            $params[':start'] = $dateRange['start'] . ' 00:00:00';
        }
        if ($dateRange && !empty($dateRange['end'])) {
            $where .= " AND sa.adjusted_at <= :end";
            $params[':end'] = $dateRange['end'] . ' 23:59:59';
        }
        $stmt = $this->db->prepare("SELECT sa.*, m.name as material_name, u.name as adjusted_by_name FROM stock_adjustments sa LEFT JOIN materials m ON m.id = sa.material_id LEFT JOIN users u ON u.id = sa.created_by $where ORDER BY sa.created_at DESC");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * getStats: return summary counts and totals for adjustments
     * dateRange: ['start'=>'YYYY-MM-DD','end'=>'YYYY-MM-DD']
     */
    public function getStats(?array $dateRange = null): array
    {
        $where = "";
        $params = [];
        if ($dateRange && !empty($dateRange['start']) && !empty($dateRange['end'])) {
            $where = "WHERE adjusted_at BETWEEN :start AND :end";
            $params = [':start' => $dateRange['start'] . ' 00:00:00', ':end' => $dateRange['end'] . ' 23:59:59'];
        }

        $stmt = $this->db->prepare("SELECT reason, COUNT(*) as count_adj, SUM(difference) as sum_difference FROM stock_adjustments $where GROUP BY reason");
        $stmt->execute($params);
        $byReason = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt2 = $this->db->prepare("SELECT COUNT(*) as total_adjustments, SUM(ABS(difference)) as total_magnitude FROM stock_adjustments $where");
        $stmt2->execute($params);
        $totals = $stmt2->fetch(PDO::FETCH_ASSOC);

        return [
            'by_reason' => $byReason,
            'totals' => $totals
        ];
    }

    /**
     * Delete stock adjustment (soft delete or hard delete based on business logic)
     */
    public function delete(int $id): bool
    {
        try {
            $this->db->beginTransaction();

            // Get the adjustment details first
            $adjustment = $this->findById($id);
            if (!$adjustment) {
                $this->db->rollBack();
                return false;
            }

            // Reverse the adjustment: restore original stock (current_stock)
            $stmt = $this->db->prepare("
                UPDATE materials 
                SET current_stock = current_stock - ? 
                WHERE id = ?
            ");
            $stmt->execute([$adjustment['difference'], $adjustment['material_id']]);

            // Delete the adjustment record
            $stmt = $this->db->prepare("DELETE FROM stock_adjustments WHERE id = ?");
            $stmt->execute([$id]);

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
