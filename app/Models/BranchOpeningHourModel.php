<?php

namespace App\Models;

use CodeIgniter\Model;

class BranchOpeningHourModel extends Model
{
    protected $table            = 'branch_opening_hours';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'tenant_id', 'branch_id', 'day_of_week', 'open_time', 'close_time', 'is_closed',
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
        'tenant_id'   => 'required|integer',
        'branch_id'   => 'required|integer',
        'day_of_week' => 'required|integer|greater_than_equal_to[0]|less_than[7]',
        // valid_date() is for calendar dates and rejects valid MySQL TIME
        // values such as 07:00:00. Keep the boundary strict with a time
        // expression instead.
        'open_time'   => 'permit_empty|regex_match[/^(?:[01]\d|2[0-3]):[0-5]\d:[0-5]\d$/]',
        'close_time'  => 'permit_empty|regex_match[/^(?:[01]\d|2[0-3]):[0-5]\d:[0-5]\d$/]',
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

    public function getByBranch(int $branchId)
    {
        return $this->where('branch_id', $branchId)
                    ->where('deleted_at', null)
                    ->orderBy('day_of_week', 'ASC')
                    ->findAll();
    }

    public function deleteByBranch(int $branchId)
    {
        return $this->where('branch_id', $branchId)
                    ->delete();
    }

    public static function getDayName(int $dayOfWeek, string $locale = 'vi'): string
    {
        $days = [
            0 => ['vi' => 'Chủ nhật', 'en' => 'Sunday'],
            1 => ['vi' => 'Thứ hai', 'en' => 'Monday'],
            2 => ['vi' => 'Thứ ba', 'en' => 'Tuesday'],
            3 => ['vi' => 'Thứ tư', 'en' => 'Wednesday'],
            4 => ['vi' => 'Thứ năm', 'en' => 'Thursday'],
            5 => ['vi' => 'Thứ sáu', 'en' => 'Friday'],
            6 => ['vi' => 'Thứ bảy', 'en' => 'Saturday'],
        ];
        return $days[$dayOfWeek][$locale] ?? '';
    }
}
