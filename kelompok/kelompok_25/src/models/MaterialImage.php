<?php

/**
 * MaterialImage Model
 * Mengelola gambar material
 */

require_once ROOT_PATH . '/core/Model.php';

class MaterialImage extends Model
{
    protected $table = 'material_images';
    protected $primaryKey = 'id';

    /**
     * Get all images for a material
     */
    public function getByMaterial($materialId)
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE material_id = ? 
                ORDER BY is_primary DESC, created_at ASC";
        
        $stmt = $this->query($sql, [$materialId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get primary image for a material
     */
    public function getPrimaryImage($materialId)
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE material_id = ? AND is_primary = TRUE 
                LIMIT 1";
        
        $stmt = $this->query($sql, [$materialId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Find image by ID
     */
    public function findById($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $stmt = $this->query($sql, [$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create new image record
     */
    public function create($data)
    {
        $sql = "INSERT INTO {$this->table} 
                (material_id, filename, path, file_size, mime_type, is_primary, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->query($sql, [
            $data['material_id'],
            $data['filename'],
            $data['path'],
            $data['file_size'],
            $data['mime_type'],
            $data['is_primary'] ?? false
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * Set primary image
     */
    public function setPrimary($id, $materialId)
    {
        // First, unset all primary images for this material
        $sql = "UPDATE {$this->table} SET is_primary = FALSE WHERE material_id = ?";
        $this->query($sql, [$materialId]);

        // Then set the new primary
        $sql = "UPDATE {$this->table} SET is_primary = TRUE WHERE id = ?";
        $stmt = $this->query($sql, [$id]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Delete image
     */
    public function delete($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->query($sql, [$id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Count images for a material
     */
    public function countByMaterial($materialId)
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE material_id = ?";
        $stmt = $this->query($sql, [$materialId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    /**
     * Delete all images for a material
     */
    public function deleteByMaterial($materialId)
    {
        $sql = "DELETE FROM {$this->table} WHERE material_id = ?";
        $stmt = $this->query($sql, [$materialId]);
        return $stmt->rowCount();
    }

    /**
     * Get total file size for a material
     */
    public function getTotalSize($materialId)
    {
        $sql = "SELECT SUM(file_size) as total_size FROM {$this->table} WHERE material_id = ?";
        $stmt = $this->query($sql, [$materialId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total_size'] ?? 0;
    }

    /**
     * Check if material has primary image
     */
    public function hasPrimaryImage($materialId)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                WHERE material_id = ? AND is_primary = TRUE";
        $stmt = $this->query($sql, [$materialId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }
}
