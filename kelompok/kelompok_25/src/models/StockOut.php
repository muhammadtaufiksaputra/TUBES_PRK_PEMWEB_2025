<?php
// models/StockOut.php
// Requires: Database::connect() -> PDO
// Optional: activity_logs table, materials.low_stock_threshold column

class StockOut
{
    protected PDO $db;
    protected array $usageTypes = ['production','sale','waste','transfer','other'];

    public function __construct(PDO $db)
    {
        $this->db = $db;
        // make sure exceptions are thrown
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Generate unique reference number: SO-YYYYMMDD-XXX
     */
    public function generateReferenceNumber(): string
    {
        $date = (new DateTime())->format('Ymd');
        // count today's rows
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM stock_out WHERE DATE(created_at) = CURDATE()");
        $stmt->execute();
        $count = (int)$stmt->fetchColumn() + 1;
        $ref = sprintf('SO-%s-%03d', $date, $count);

        // ensure unique (rare collision if concurrent) — loop until unique
        $i = 0;
        while ($i < 10) {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM stock_out WHERE reference_number = ?");
            $stmt->execute([$ref]);
            if ((int)$stmt->fetchColumn() === 0) {
                return $ref;
            }
            $count++;
            $ref = sprintf('SO-%s-%03d', $date, $count);
            $i++;
        }
        // fallback: append uniqid
        return 'SO-' . $date . '-' . uniqid();
    }

    /**
     * Create stock out transaction and update material stock.
     * $data should contain:
     * material_id, quantity, usage_type, transaction_date, destination (optional), notes (optional), created_by
     *
     * Throws Exception on validation/stock errors.
     * Returns inserted row id (int) (or reference_number if you prefer).
     */
    public function create(array $data): array
    {
        // basic validation
        $this->validateCreatePayload($data);

        // begin transaction
        $this->db->beginTransaction();
        try {
            // Lock material row to avoid race condition
            $stmt = $this->db->prepare("SELECT id, current_stock FROM materials WHERE id = ? FOR UPDATE");
            $stmt->execute([(int)$data['material_id']]);
            $material = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$material) {
                throw new Exception("Material not found");
            }

            $currentStock = (float)$material['current_stock'];
            $qty = (float)$data['quantity'];

            if ($qty <= 0) {
                throw new Exception("Quantity harus > 0");
            }

            if ($qty > $currentStock) {
                throw new Exception("Stok tidak mencukupi. Current stock: {$currentStock}");
            }

            // generate unique reference
            $reference = $this->generateReferenceNumber();

            // insert stock_out
            $insert = $this->db->prepare("
                INSERT INTO stock_out 
                (material_id, quantity, usage_type, reference_number, txn_date, destination, note, created_by)
                VALUES (:material_id, :quantity, :usage_type, :reference_number, :txn_date, :destination, :note, :created_by)
            ");

            $insert->execute([
                ':material_id' => (int)$data['material_id'],
                ':quantity' => $qty,
                ':usage_type' => $data['usage_type'],
                ':reference_number' => $reference,
                ':txn_date' => $data['transaction_date'],
                ':destination' => $data['destination'] ?? null,
                ':note' => $data['notes'] ?? $data['note'] ?? null,
                ':created_by' => (int)$data['created_by']
            ]);

            $stockOutId = (int)$this->db->lastInsertId();

            // update material stock
            $update = $this->db->prepare("UPDATE materials SET current_stock = current_stock - :qty WHERE id = :id");
            $update->execute([':qty' => $qty, ':id' => (int)$data['material_id']]);

            // optional low-stock alert trigger
            // Use default threshold since low_stock_threshold column doesn't exist
            $threshold = 5; // default minimal threshold
            $newStockStmt = $this->db->prepare("SELECT current_stock FROM materials WHERE id = ?");
            $newStockStmt->execute([(int)$data['material_id']]);
            $newStock = (float)$newStockStmt->fetchColumn();

            if ($newStock <= $threshold) {
                $this->triggerLowStockAlert((int)$data['material_id'], $newStock, $threshold);
            }

            // log activity
            $this->logActivity([
                'user_id' => (int)$data['created_by'],
                'action' => 'stock_out.create',
                'message' => "Stock out {$qty} (material_id={$data['material_id']}) reference={$reference}",
                'meta' => json_encode([
                    'stock_out_id' => $stockOutId,
                    'reference' => $reference,
                    'material_id' => (int)$data['material_id'],
                    'quantity' => $qty,
                    'usage_type' => $data['usage_type']
                ])
            ]);

            $this->db->commit();

            return [
                'id' => $stockOutId,
                'reference_number' => $reference,
                'material_id' => (int)$data['material_id'],
                'quantity' => $qty,
                'new_stock' => $newStock
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Validate creation payload
     */
    protected function validateCreatePayload(array $data)
    {
        if (empty($data['material_id'])) {
            throw new Exception("material_id required");
        }
        if (!isset($data['quantity'])) {
            throw new Exception("quantity required");
        }
        if (!is_numeric($data['quantity'])) {
            throw new Exception("quantity must be numeric");
        }
        if (empty($data['usage_type']) || !in_array($data['usage_type'], $this->usageTypes, true)) {
            throw new Exception("usage_type invalid");
        }
        if (empty($data['transaction_date'])) {
            throw new Exception("transaction_date required");
        }
        $date = DateTime::createFromFormat('Y-m-d', $data['transaction_date']);
        if (!$date) {
            throw new Exception("transaction_date must be yyyy-mm-dd");
        }
        // Reset time to 00:00:00 for proper date-only comparison
        $date->setTime(0, 0, 0);
        $today = new DateTime('now');
        $today->setTime(0, 0, 0);
        if ($date > $today) {
            throw new Exception("transaction_date cannot be in the future");
        }
        if (!empty($data['destination']) && mb_strlen($data['destination']) > 100) {
            throw new Exception("destination max 100 characters");
        }
        if (empty($data['created_by'])) {
            throw new Exception("created_by required");
        }
    }

    /**
     * Log activity to table activity_logs if exists, otherwise error_log.
     * $payload: [user_id, action, message, meta]
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
            // fallback to php error log (don't break main flow)
            error_log("[ActivityLogFallback] " . json_encode($payload) . " - error: " . $e->getMessage());
        }
    }

    /**
     * Trigger low stock alert (implement as needed).
     * Default: insert into low_stock_alerts if exists; otherwise error_log.
     */
    protected function triggerLowStockAlert(int $materialId, float $newStock, float $threshold)
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO low_stock_alerts (material_id, current_stock, threshold, created_at, is_notified) VALUES (?, ?, ?, NOW(), 0)");
            $stmt->execute([$materialId, $newStock, $threshold]);
        } catch (Exception $e) {
            error_log("[LowStockAlertFallback] material_id={$materialId} newStock={$newStock} threshold={$threshold}");
        }
    }

    /**
     * getAll with pagination and optional filters:
     * filters = [
     *   'material_id' => int,
     *   'usage_type' => string,
     *   'start_date' => 'YYYY-MM-DD',
     *   'end_date' => 'YYYY-MM-DD',
     *   'q' => search on reference_number or notes
     * ]
     */
    public function getAll(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $where = [];
        $params = [];

        if (!empty($filters['material_id'])) {
            $where[] = "material_id = :material_id";
            $params[':material_id'] = (int)$filters['material_id'];
        }
        if (!empty($filters['usage_type'])) {
            $where[] = "usage_type = :usage_type";
            $params[':usage_type'] = $filters['usage_type'];
        }
        if (!empty($filters['start_date'])) {
            $where[] = "txn_date >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $where[] = "txn_date <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }
        if (!empty($filters['q'])) {
            $where[] = "(reference_number LIKE :q OR note LIKE :q)";
            $params[':q'] = '%' . $filters['q'] . '%';
        }

        $whereSql = count($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

        // total count
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM stock_out s $whereSql");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        // fetch rows with material name join, alias txn_date as transaction_date for frontend
        $sql = "SELECT s.*, s.txn_date AS transaction_date, s.note AS notes, m.name AS material_name, m.code AS material_code, u.name AS created_by_name
                FROM stock_out s
                LEFT JOIN materials m ON m.id = s.material_id
                LEFT JOIN users u ON u.id = s.created_by
                $whereSql
                ORDER BY s.txn_date DESC, s.created_at DESC
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
        $stmt = $this->db->prepare("SELECT s.*, m.name AS material_name, u.name AS created_by_name FROM stock_out s LEFT JOIN materials m ON m.id = s.material_id LEFT JOIN users u ON u.id = s.created_by WHERE s.id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getByMaterial(int $materialId, ?array $dateRange = null): array
    {
        $where = "WHERE material_id = :material_id";
        $params = [':material_id' => $materialId];
        if ($dateRange && !empty($dateRange['start'])) {
            $where .= " AND transaction_date >= :start";
            $params[':start'] = $dateRange['start'];
        }
        if ($dateRange && !empty($dateRange['end'])) {
            $where .= " AND transaction_date <= :end";
            $params[':end'] = $dateRange['end'];
        }
        $stmt = $this->db->prepare("SELECT * FROM stock_out $where ORDER BY transaction_date DESC, created_at DESC");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByUsageType(string $usageType, ?array $dateRange = null): array
    {
        if (!in_array($usageType, $this->usageTypes, true)) {
            throw new Exception("Invalid usage type");
        }
        $where = "WHERE usage_type = :usage_type";
        $params = [':usage_type' => $usageType];
        if ($dateRange && !empty($dateRange['start'])) {
            $where .= " AND transaction_date >= :start";
            $params[':start'] = $dateRange['start'];
        }
        if ($dateRange && !empty($dateRange['end'])) {
            $where .= " AND transaction_date <= :end";
            $params[':end'] = $dateRange['end'];
        }
        $stmt = $this->db->prepare("SELECT * FROM stock_out $where ORDER BY transaction_date DESC, created_at DESC");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByDateRange(string $start, string $end): array
    {
        $stmt = $this->db->prepare("SELECT * FROM stock_out WHERE transaction_date BETWEEN :start AND :end ORDER BY transaction_date DESC");
        $stmt->execute([':start' => $start, ':end' => $end]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * getTotalByMaterial: total quantity out for material in given period
     * $period = ['start' => 'YYYY-MM-DD', 'end' => 'YYYY-MM-DD'] or null for all time
     */
    public function getTotalByMaterial(int $materialId, ?array $period = null): float
    {
        $sql = "SELECT SUM(quantity) as total FROM stock_out WHERE material_id = :material_id";
        $params = [':material_id' => $materialId];
        if ($period && !empty($period['start']) && !empty($period['end'])) {
            $sql .= " AND transaction_date BETWEEN :start AND :end";
            $params[':start'] = $period['start'];
            $params[':end'] = $period['end'];
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (float)$stmt->fetchColumn();
    }

    /**
     * getStats: aggregate by usage_type or total by day
     * $dateRange = ['start'=>'YYYY-MM-DD','end'=>'YYYY-MM-DD']
     */
    public function getStats(?array $dateRange = null): array
    {
        $where = "";
        $params = [];
        if ($dateRange && !empty($dateRange['start']) && !empty($dateRange['end'])) {
            $where = "WHERE transaction_date BETWEEN :start AND :end";
            $params = [':start' => $dateRange['start'], ':end' => $dateRange['end']];
        }

        $stmt = $this->db->prepare("
            SELECT usage_type, SUM(quantity) as total_quantity, COUNT(*) as count_trx
            FROM stock_out
            $where
            GROUP BY usage_type
        ");
        $stmt->execute($params);
        $byUsage = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // totals per day (last 30 days or within range)
        $stmt2 = $this->db->prepare("
            SELECT transaction_date, SUM(quantity) as total_quantity
            FROM stock_out
            $where
            GROUP BY transaction_date
            ORDER BY transaction_date ASC
        ");
        $stmt2->execute($params);
        $byDay = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        return [
            'by_usage' => $byUsage,
            'by_day' => $byDay
        ];
    }

    /**
     * Delete stock out transaction and restore material stock
     */
    public function delete(int $id): bool
    {
        try {
            $this->db->beginTransaction();

            // Get the transaction details first
            $stockOut = $this->findById($id);
            if (!$stockOut) {
                $this->db->rollBack();
                return false;
            }

            // Restore the material stock (reverse the stock out)
            $stmt = $this->db->prepare("
                UPDATE materials 
                SET current_stock = current_stock + ? 
                WHERE id = ?
            ");
            $stmt->execute([$stockOut['quantity'], $stockOut['material_id']]);

            // Delete the stock out record
            $stmt = $this->db->prepare("DELETE FROM stock_out WHERE id = ?");
            $stmt->execute([$id]);

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Get recent stock out transactions
     */
    public function getRecent(int $limit = 5): array
    {
        $sql = "SELECT 
                    so.id,
                    so.quantity,
                    so.usage_type,
                    so.txn_date,
                    so.reference_number,
                    so.note,
                    m.name as material_name,
                    m.unit
                FROM stock_out so
                INNER JOIN materials m ON so.material_id = m.id
                ORDER BY so.created_at DESC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
