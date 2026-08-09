<?php

namespace App\Services;

use App\Models\MediaFileModel;
use CodeIgniter\Files\File;
use CodeIgniter\HTTP\Files\UploadedFile;

class UploadService
{
    protected MediaFileModel $mediaFileModel;
    protected string $uploadPath;
    protected array $allowedTypes = [
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
        'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv'],
        'video' => ['mp4', 'avi', 'mov', 'wmv'],
    ];

    public function __construct()
    {
        $this->mediaFileModel = new MediaFileModel();
        $this->uploadPath = WRITEPATH . 'uploads';
    }

    public function upload(UploadedFile $file, string $type = 'image', ?int $tenantId = null, ?int $userId = null): ?array
    {
        $extension = $file->getExtension();
        $allowedExtensions = $this->getAllowedExtensions($type);

        if (!in_array(strtolower($extension), $allowedExtensions)) {
            return null;
        }

        $newName = $file->getRandomName();
        $yearMonth = date('Y/m');
        $subPath = $type . '/' . $yearMonth;
        $fullPath = $this->uploadPath . '/' . $subPath;

        if (!is_dir($fullPath)) {
            mkdir($fullPath, 0755, true);
        }

        $file->move($fullPath, $newName);

        $filePath = $subPath . '/' . $newName;
        $fileSize = $file->getSize();
        $mimeType = $file->getMimeType();

        $imageInfo = null;
        if ($type === 'image' && in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $imageInfo = getimagesize($fullPath . '/' . $newName);
        }

        $data = [
            'tenant_id'  => $tenantId,
            'user_id'    => $userId,
            'file_name'  => $file->getName(),
            'file_path'  => $filePath,
            'file_type'  => $type,
            'file_size'  => $fileSize,
            'mime_type'  => $mimeType,
            'extension'  => $extension,
            'width'      => $imageInfo ? $imageInfo[0] : null,
            'height'     => $imageInfo ? $imageInfo[1] : null,
            'status'     => 'active',
        ];

        $id = $this->mediaFileModel->insert($data);
        if (!$id) {
            return null;
        }

        return array_merge(['id' => $id], $data);
    }

    public function delete(int $id): bool
    {
        $media = $this->mediaFileModel->find($id);
        if (!$media) {
            return false;
        }

        $fullPath = $this->uploadPath . '/' . $media->file_path;
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }

        return $this->mediaFileModel->delete($id);
    }

    public function getFileUrl(string $filePath): string
    {
        return base_url('uploads/' . $filePath);
    }

    protected function getAllowedExtensions(string $type): array
    {
        return $this->allowedTypes[$type] ?? ['jpg', 'jpeg', 'png'];
    }

    public function getAllowedTypes(): array
    {
        $all = [];
        foreach ($this->allowedTypes as $extensions) {
            $all = array_merge($all, $extensions);
        }
        return $all;
    }
}
