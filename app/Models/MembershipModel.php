<?php

namespace App\Models;

use CodeIgniter\Model;

class MembershipModel extends Model
{
    protected $table            = 'memberships';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = \App\Entities\Membership::class;
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'tenant_id', 'player_id', 'package_id', 'start_date', 'end_date', 'status',
        'created_by', 'updated_by',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'tenant_id'  => 'required|integer',
        'player_id'  => 'required|integer',
        'package_id' => 'required|integer',
        'start_date' => 'required|valid_date',
        'end_date'   => 'required|valid_date',
        'status'     => 'required|in_list[active,expired,cancelled]',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    public function getActiveByPlayer(int $playerId, ?int $tenantId = null)
    {
        $today = date('Y-m-d');
        $builder = $this->select('memberships.*, membership_packages.name_vi as package_name_vi, membership_packages.name_en as package_name_en, membership_packages.discount_percent, membership_packages.booking_priority')
                        ->join('membership_packages', 'membership_packages.id = memberships.package_id', 'left')
                        ->where('memberships.player_id', $playerId)
                        ->where('memberships.status', 'active')
                        ->where('memberships.start_date <=', $today)
                        ->where('memberships.end_date >=', $today)
                        ->where('memberships.deleted_at', null);

        if ($tenantId !== null) {
            $builder->where('memberships.tenant_id', $tenantId);
        }

        return $builder->orderBy('memberships.end_date', 'DESC')->first();
    }

    public function getByPlayer(int $playerId)
    {
        return $this->select('memberships.*, membership_packages.name_vi as package_name_vi, membership_packages.name_en as package_name_en')
                    ->join('membership_packages', 'membership_packages.id = memberships.package_id', 'left')
                    ->where('memberships.player_id', $playerId)
                    ->where('memberships.deleted_at', null)
                    ->orderBy('memberships.created_at', 'DESC')
                    ->findAll();
    }

    public function getByTenant(int $tenantId, array $filters = [])
    {
        $builder = $this->select('memberships.*, players.full_name, players.player_code, players.phone, membership_packages.name_vi as package_name_vi, membership_packages.name_en as package_name_en')
                        ->join('players', 'players.id = memberships.player_id')
                        ->join('membership_packages', 'membership_packages.id = memberships.package_id')
                        ->where('memberships.tenant_id', $tenantId)
                        ->where('memberships.deleted_at', null);

        if (!empty($filters['status'])) {
            $builder->where('memberships.status', $filters['status']);
        }

        if (!empty($filters['player_id'])) {
            $builder->where('memberships.player_id', $filters['player_id']);
        }

        return $builder->orderBy('memberships.created_at', 'DESC')
                       ->paginate(20);
    }

    public function expireOverdue()
    {
        $today = date('Y-m-d');
        return $this->where('status', 'active')
                    ->where('end_date <', $today)
                    ->where('deleted_at', null)
                    ->set(['status' => 'expired'])
                    ->update();
    }
}
