<?php

/**
 * Supplier Model
 */

require_once ROOT_PATH . '/core/Model.php';

class Supplier extends Model
{
    protected $table = 'suppliers';

    /**
     * Get all active suppliers with pagination
     */
    public function getAllActive($page = 1, $perPage = 10)
    {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT * FROM {$this->table} 
                WHERE is_active = TRUE 
                ORDER BY name ASC 
                LIMIT ? OFFSET ?";
        
        $stmt = $this->query($sql, [$perPage, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get count of all active suppliers
     */
    public function countActive()
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE is_active = TRUE";
        $stmt = $this->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    /**
     * Find supplier by ID (only if active)
     */
    public function findActive($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ? AND is_active = TRUE";
        $stmt = $this->query($sql, [$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Find supplier by email
     */
    public function findByEmail($email)
    {
        return $this->findBy('email', $email);
    }

    /**
     * Find supplier by name
     */
    public function findByName($name)
    {
        return $this->findBy('name', $name);
    }

    /**
     * Search suppliers by name or contact
     */
    public function search($query, $page = 1, $perPage = 10)
    {
        $offset = ($page - 1) * $perPage;
        $searchTerm = "%$query%";
        
        $sql = "SELECT * FROM {$this->table} 
                WHERE is_active = TRUE 
                AND (name LIKE ? OR contact_person LIKE ? OR phone LIKE ? OR email LIKE ?)
                ORDER BY name ASC 
                LIMIT ? OFFSET ?";
        
        $stmt = $this->query($sql, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $perPage, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count search results
     */
    public function searchCount($query)
    {
        $searchTerm = "%$query%";
        
        $sql = "SELECT COUNT(*) as total FROM {$this->table} 
                WHERE is_active = TRUE 
                AND (name LIKE ? OR contact_person LIKE ? OR phone LIKE ? OR email LIKE ?)";
        
        $stmt = $this->query($sql, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    /**
     * Create new supplier
     */
    public function create($data)
    {
        // Set defaults
        $data['is_active'] = $data['is_active'] ?? true;
        $data['created_at'] = date('Y-m-d H:i:s');

        return $this->insert($data);
    }

    /**
     * Update supplier
     */
    public function updateSupplier($id, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->update($id, $data);
    }

    /**
     * Soft delete supplier (set is_active to false)
     */
    public function softDelete($id)
    {
        return $this->updateSupplier($id, ['is_active' => false]);
    }

    /**
     * Restore supplier (set is_active to true)
     */
    public function restore($id)
    {
        return $this->updateSupplier($id, ['is_active' => true]);
    }

    /**
     * Check if supplier exists
     */
    public function exists($supplierId)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE id = ?";
        $stmt = $this->query($sql, [$supplierId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }

    /**
     * Check if email exists (exclude current supplier)
     */
    public function emailExists($email, $exceptSupplierId = null)
    {
        if ($exceptSupplierId) {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE email = ? AND id != ?";
            $stmt = $this->query($sql, [$email, $exceptSupplierId]);
        } else {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE email = ?";
            $stmt = $this->query($sql, [$email]);
        }

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }

    /**
     * Get supplier with material count
     */
    public function findWithMaterialCount($id)
    {
        $sql = "SELECT s.*, COUNT(m.id) as material_count 
                FROM {$this->table} s
                LEFT JOIN materials m ON s.id = m.default_supplier_id
                WHERE s.id = ? AND s.is_active = TRUE
                GROUP BY s.id";
        
        $stmt = $this->query($sql, [$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get top suppliers by material count
     */
    public function getTopByMaterials($limit = 5)
    {
        $sql = "SELECT s.*, COUNT(m.id) as material_count 
                FROM {$this->table} s
                LEFT JOIN materials m ON s.id = m.default_supplier_id
                WHERE s.is_active = TRUE
                GROUP BY s.id
                ORDER BY material_count DESC
                LIMIT ?";
        
        $stmt = $this->query($sql, [$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all suppliers (including inactive)
     */
    public function getAllIncludingInactive()
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY name ASC";
        $stmt = $this->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count total suppliers
     */
    public function countAll()
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $stmt = $this->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }
}
