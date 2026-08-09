<?php

namespace App\Models;

use CodeIgniter\Model;

class MediaFileModel extends Model
{
    protected $table            = 'media_files';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'tenant_id', 'branch_id', 'user_id', 'file_name', 'file_path',
        'file_type', 'file_size', 'mime_type', 'extension',
        'alt_text', 'width', 'height', 'is_active', 'status',
        'created_by', 'updated_by',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'file_name' => 'required|max_length[255]',
        'file_path' => 'required|max_length[255]',
        'file_type' => 'permit_empty|max_length[100]',
        'mime_type' => 'permit_empty|max_length[100]',
        'extension' => 'permit_empty|max_length[20]',
        'status'    => 'required|in_list[active,inactive,deleted]',
    ];

    public function getByTenant(int $tenantId, string $type = null)
    {
        $query = $this->where('tenant_id', $tenantId)->where('deleted_at', null);
        if ($type) {
            $query->where('file_type', $type);
        }
        return $query->findAll();
    }

    public function getByUser(int $userId)
    {
        return $this->where('user_id', $userId)
                    ->where('deleted_at', null)
                    ->findAll();
    }
}
