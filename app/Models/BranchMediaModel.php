<?php

namespace App\Models;

use CodeIgniter\Model;

class BranchMediaModel extends Model
{
    protected $table            = 'branch_media';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'tenant_id', 'branch_id', 'media_type', 'file_path', 'title_vi',
        'title_en', 'is_primary', 'sort_order', 'created_by', 'updated_by',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    public function getByBranch(int $branchId): array
    {
        return $this->where('branch_id', $branchId)
            ->where('deleted_at', null)
            ->orderBy('is_primary', 'DESC')
            ->orderBy('sort_order', 'ASC')
            ->findAll();
    }

    public function resetPrimary(int $branchId): bool
    {
        return $this->where('branch_id', $branchId)->set(['is_primary' => 0])->update();
    }
}
