<?php

/**
 * MaterialImage API Controller
 * Menangani upload dan manajemen gambar material
 */

class MaterialImageApiController
{
    private $imageModel;
    private $materialModel;
    private $uploadDir;
    private $maxFileSize = 5242880; // 5MB
    private $maxImagesPerMaterial = 5;
    private $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

    public function __construct()
    {
        $this->imageModel = new MaterialImage();
        $this->materialModel = new Material();
        $this->uploadDir = ROOT_PATH . '/public/uploads/materials';
        
        // Create upload directory if not exists
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * GET /api/materials/:materialId/images
     * Get all images for a material
     */
    public function index($materialId)
    {
        try {
            $material = $this->materialModel->findById($materialId);

            if (!$material) {
                Response::error('Material not found', 404);
                return;
            }

            $images = $this->imageModel->getByMaterial($materialId);

            // Add full URL to each image
            foreach ($images as &$image) {
                $image['url'] = $this->getImageUrl($image['path']);
            }

            Response::success(['images' => $images]);

        } catch (Exception $e) {
            Response::error('Failed to fetch images: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/materials/:materialId/images
     * Upload new image
     */
    public function upload($materialId)
    {
        try {
            $material = $this->materialModel->findById($materialId);

            if (!$material) {
                Response::error('Material not found', 404);
                return;
            }

            // Check if file uploaded
            if (!isset($_FILES['image'])) {
                Response::error('No image file provided', 422);
                return;
            }

            $file = $_FILES['image'];

            // Check for upload errors
            if ($file['error'] !== UPLOAD_ERR_OK) {
                Response::error('File upload failed', 422);
                return;
            }

            // Validate file size
            if ($file['size'] > $this->maxFileSize) {
                Response::error('File size exceeds 5MB limit', 422);
                return;
            }

            // Validate file type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mimeType, $this->allowedTypes)) {
                Response::error('Invalid file type. Allowed: JPG, PNG, WEBP', 422);
                return;
            }

            // Check max images limit
            $currentCount = $this->imageModel->countByMaterial($materialId);
            if ($currentCount >= $this->maxImagesPerMaterial) {
                Response::error("Maximum {$this->maxImagesPerMaterial} images per material", 422);
                return;
            }

            // Generate unique filename
            $extension = $this->getExtensionFromMime($mimeType);
            $filename = uniqid('mat_' . $materialId . '_') . '.' . $extension;
            $relativePath = 'uploads/materials/' . $filename;
            $fullPath = $this->uploadDir . '/' . $filename;

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
                Response::error('Failed to save file', 500);
                return;
            }

            // Resize image if needed
            $this->resizeImage($fullPath, $mimeType);

            // Set as primary if it's the first image
            $isPrimary = $currentCount === 0;

            // Save to database
            $imageId = $this->imageModel->create([
                'material_id' => $materialId,
                'filename' => $filename,
                'path' => $relativePath,
                'file_size' => filesize($fullPath),
                'mime_type' => $mimeType,
                'is_primary' => $isPrimary
            ]);

            if ($imageId) {
                $image = $this->imageModel->findById($imageId);
                $image['url'] = $this->getImageUrl($image['path']);

                $this->logActivity('upload', 'material_image', $imageId, 
                    "Uploaded image for material: {$material['name']}");

                Response::success([
                    'message' => 'Image uploaded successfully',
                    'image' => $image
                ], 201);
            } else {
                // Clean up file if database insert fails
                unlink($fullPath);
                Response::error('Failed to save image record', 500);
            }

        } catch (Exception $e) {
            Response::error('Failed to upload image: ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/materials/:materialId/images/:id/primary
     * Set image as primary
     */
    public function setPrimary($materialId, $id)
    {
        try {
            $material = $this->materialModel->findById($materialId);

            if (!$material) {
                Response::error('Material not found', 404);
                return;
            }

            $image = $this->imageModel->findById($id);

            if (!$image) {
                Response::error('Image not found', 404);
                return;
            }

            if ($image['material_id'] != $materialId) {
                Response::error('Image does not belong to this material', 400);
                return;
            }

            $success = $this->imageModel->setPrimary($id, $materialId);

            if ($success) {
                $this->logActivity('update', 'material_image', $id, 
                    "Set primary image for material: {$material['name']}");

                Response::success(['message' => 'Primary image updated successfully']);
            } else {
                Response::error('Failed to update primary image', 500);
            }

        } catch (Exception $e) {
            Response::error('Failed to update primary image: ' . $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/materials/:materialId/images/:id
     * Delete image
     */
    public function delete($materialId, $id)
    {
        try {
            $material = $this->materialModel->findById($materialId);

            if (!$material) {
                Response::error('Material not found', 404);
                return;
            }

            $image = $this->imageModel->findById($id);

            if (!$image) {
                Response::error('Image not found', 404);
                return;
            }

            if ($image['material_id'] != $materialId) {
                Response::error('Image does not belong to this material', 400);
                return;
            }

            // Delete file from filesystem
            $fullPath = ROOT_PATH . '/public/' . $image['path'];
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }

            // If deleting primary image, set another as primary
            if ($image['is_primary']) {
                $images = $this->imageModel->getByMaterial($materialId);
                if (count($images) > 1) {
                    // Set the next image as primary
                    foreach ($images as $img) {
                        if ($img['id'] != $id) {
                            $this->imageModel->setPrimary($img['id'], $materialId);
                            break;
                        }
                    }
                }
            }

            // Delete from database
            $success = $this->imageModel->delete($id);

            if ($success) {
                $this->logActivity('delete', 'material_image', $id, 
                    "Deleted image for material: {$material['name']}");

                Response::success(['message' => 'Image deleted successfully']);
            } else {
                Response::error('Failed to delete image', 500);
            }

        } catch (Exception $e) {
            Response::error('Failed to delete image: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Resize image to max 1200x1200
     */
    private function resizeImage($path, $mimeType)
    {
        $maxWidth = 1200;
        $maxHeight = 1200;

        // Get current dimensions
        list($width, $height) = getimagesize($path);

        // Check if resize needed
        if ($width <= $maxWidth && $height <= $maxHeight) {
            return;
        }

        // Calculate new dimensions
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $newWidth = (int)($width * $ratio);
        $newHeight = (int)($height * $ratio);

        // Create new image
        $newImage = imagecreatetruecolor($newWidth, $newHeight);

        // Load original image
        switch ($mimeType) {
            case 'image/jpeg':
                $source = imagecreatefromjpeg($path);
                break;
            case 'image/png':
                $source = imagecreatefrompng($path);
                // Preserve transparency
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);
                break;
            case 'image/webp':
                $source = imagecreatefromwebp($path);
                break;
            default:
                return;
        }

        // Resize
        imagecopyresampled($newImage, $source, 0, 0, 0, 0, 
            $newWidth, $newHeight, $width, $height);

        // Save resized image
        switch ($mimeType) {
            case 'image/jpeg':
                imagejpeg($newImage, $path, 85);
                break;
            case 'image/png':
                imagepng($newImage, $path, 8);
                break;
            case 'image/webp':
                imagewebp($newImage, $path, 85);
                break;
        }

        // Clean up
        imagedestroy($source);
        imagedestroy($newImage);
    }

    /**
     * Get extension from MIME type
     */
    private function getExtensionFromMime($mimeType)
    {
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp'
        ];

        return $extensions[$mimeType] ?? 'jpg';
    }

    /**
     * Get full image URL
     */
    private function getImageUrl($path)
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        return "$protocol://$host/$path";
    }

    /**
     * Log activity
     */
    private function logActivity($action, $entity, $entityId, $description)
    {
        try {
            $userId = Auth::id();
            
            $sql = "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
            
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare($sql);
            $stmt->execute([$userId, $action, $entity, $entityId, $description]);
        } catch (Exception $e) {
            error_log("Failed to log activity: " . $e->getMessage());
        }
    }
}
