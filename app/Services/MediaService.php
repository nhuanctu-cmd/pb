<?php

namespace App\Services;

use App\Models\MediaFileModel;
use CodeIgniter\Files\File;

/**
 * Media library: upload, resize, thumbnail, soft delete
 */
class MediaService
{
    protected MediaFileModel $mediaModel;

    public function __construct()
    {
        $this->mediaModel = new MediaFileModel();
    }

    /**
     * Upload file (ảnh tự động resize + tạo thumbnail)
     */
    public function upload(File $file, int $tenantId, ?int $userId = null, string $fileType = 'image'): ?object
    {
        $year  = date('Y');
        $month = date('m');
        $dir   = "uploads/{$year}/{$month}/";
        $path  = FCPATH . $dir;

        if (! is_dir($path)) {
            mkdir($path, 0755, true);
        }

        // Lấy metadata trước khi move (move xóa file gốc)
        $originalName = pathinfo($file->getFilename(), PATHINFO_FILENAME);
        $extension    = strtolower($file->getExtension());
        $fileSize     = $file->getSize();
        $mimeType     = $file->getMimeType();
        $clientName   = $file->getFilename();
        $newName      = $this->sanitizeFileName($originalName) . '_' . bin2hex(random_bytes(4)) . '.' . $extension;

        if (! $file->move($path, $newName)) {
            return null;
        }

        $relativePath = $dir . $newName;
        $width = $height = null;

        // Resize + thumbnail cho ảnh
        if ($this->isImage($extension) && function_exists('imagecreatetruecolor')) {
            $this->resizeImage($path . $newName, 1600, 1600);
            [$width, $height] = getimagesize($path . $newName);
            $this->createThumbnail($path . $newName, $path . 'thumb_' . $newName, 300, 300);
        }

        $data = [
            'tenant_id' => $tenantId,
            'user_id'   => $userId,
            'file_name' => $clientName,
            'file_path' => $relativePath,
            'file_type' => $fileType,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
            'extension' => $extension,
            'width'     => $width,
            'height'    => $height,
            'is_active' => 1,
            'status'    => 'active',
            'created_by'=> $userId,
        ];

        $id = $this->mediaModel->insert($data);
        if (! $id) {
            @unlink($path . $newName);
            @unlink($path . 'thumb_' . $newName);
            return null;
        }

        return $this->mediaModel->find($id);
    }

    public function getByTenant(int $tenantId, ?string $type = null): array
    {
        return $this->mediaModel->getByTenant($tenantId, $type);
    }

    public function delete(int $mediaId): bool
    {
        return (bool) $this->mediaModel->delete($mediaId);
    }

    private function isImage(string $extension): bool
    {
        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
    }

    private function sanitizeFileName(string $name): string
    {
        $name = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $name);
        return trim($name, '_') ?: 'file';
    }

    private function resizeImage(string $sourcePath, int $maxWidth, int $maxHeight): void
    {
        [$width, $height, $type] = getimagesize($sourcePath);
        if ($width <= $maxWidth && $height <= $maxHeight) {
            return;
        }

        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $newW  = (int) ($width * $ratio);
        $newH  = (int) ($height * $ratio);

        $src = $this->createImageFromFile($sourcePath, $type);
        if (! $src) return;

        $dst = imagecreatetruecolor($newW, $newH);
        if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_WEBP) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        }
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $width, $height);
        $this->saveImage($dst, $sourcePath, $type);
        imagedestroy($src);
        imagedestroy($dst);
    }

    private function createThumbnail(string $sourcePath, string $thumbPath, int $thumbW, int $thumbH): void
    {
        [$width, $height, $type] = getimagesize($sourcePath);
        $src = $this->createImageFromFile($sourcePath, $type);
        if (! $src) return;

        $ratio   = max($thumbW / $width, $thumbH / $height);
        $cropW   = (int) ($thumbW / $ratio);
        $cropH   = (int) ($thumbH / $ratio);
        $cropX   = (int) (($width - $cropW) / 2);
        $cropY   = (int) (($height - $cropH) / 2);

        $dst = imagecreatetruecolor($thumbW, $thumbH);
        if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_WEBP) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        }
        imagecopyresampled($dst, $src, 0, 0, $cropX, $cropY, $thumbW, $thumbH, $cropW, $cropH);
        $this->saveImage($dst, $thumbPath, $type);
        imagedestroy($src);
        imagedestroy($dst);
    }

    private function createImageFromFile(string $path, int $type)
    {
        return match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_PNG  => imagecreatefrompng($path),
            IMAGETYPE_GIF  => imagecreatefromgif($path),
            IMAGETYPE_WEBP => imagecreatefromwebp($path),
            default        => null,
        };
    }

    private function saveImage($image, string $path, int $type): void
    {
        match ($type) {
            IMAGETYPE_JPEG => imagejpeg($image, $path, 85),
            IMAGETYPE_PNG  => imagepng($image, $path, 6),
            IMAGETYPE_GIF  => imagegif($image, $path),
            IMAGETYPE_WEBP => imagewebp($image, $path, 85),
            default        => null,
        };
    }
}
