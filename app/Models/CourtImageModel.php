<?php

namespace App\Models;

use CodeIgniter\Model;

class CourtImageModel extends Model
{
    protected $table            = 'court_images';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'tenant_id', 'court_id', 'file_path', 'is_primary', 'sort_order',
        'created_by', 'updated_by',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'tenant_id'  => 'required|integer',
        'court_id'   => 'required|integer',
        'file_path'  => 'required|max_length[255]',
        'is_primary' => 'permit_empty|in_list[0,1]',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = ['logAudit'];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = ['logAudit'];
    protected $afterDelete    = ['logAudit'];

    public function logAudit(array $data)
    {
        if (function_exists('log_audit')) {
            log_audit($data);
        }
        return $data;
    }

    public function getByCourt(int $courtId)
    {
        return $this->where('court_id', $courtId)
                    ->where('deleted_at', null)
                    ->orderBy('is_primary', 'DESC')
                    ->orderBy('sort_order', 'ASC')
                    ->findAll();
    }

    public function getPrimary(int $courtId)
    {
        return $this->where('court_id', $courtId)
                    ->where('is_primary', 1)
                    ->where('deleted_at', null)
                    ->first();
    }

    public function resetPrimary(int $courtId)
    {
        return $this->where('court_id', $courtId)
                    ->set(['is_primary' => 0])
                    ->update();
    }
}
