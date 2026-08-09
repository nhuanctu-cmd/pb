<?php

namespace App\Models;

use CodeIgniter\Model;

class BranchHolidayModel extends Model
{
    protected $table            = 'branch_holidays';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'tenant_id', 'branch_id', 'holiday_date', 'name_vi', 'name_en', 'is_closed', 'note',
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
        'tenant_id'    => 'required|integer',
        'branch_id'    => 'required|integer',
        'holiday_date' => 'required|valid_date[Y-m-d]',
        'name_vi'      => 'required|max_length[255]',
        'name_en'      => 'permit_empty|max_length[255]',
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

    public function getByBranch(int $branchId, int|string|null $year = null)
    {
        $builder = $this->where('branch_id', $branchId)
                        ->where('deleted_at', null);

        if ($year) {
            $builder->where('YEAR(holiday_date)', $year);
        }

        return $builder->orderBy('holiday_date', 'ASC')->findAll();
    }

    public function isHoliday(int $branchId, string $date): bool
    {
        return $this->where('branch_id', $branchId)
                    ->where('holiday_date', $date)
                    ->where('deleted_at', null)
                    ->countAllResults() > 0;
    }
}
