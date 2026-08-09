<?php

namespace App\Models;

use CodeIgniter\Model;

class TeamMemberModel extends Model
{
    protected $table = 'team_members';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    protected $allowedFields = ['tenant_id', 'team_id', 'player_id', 'role', 'status', 'deleted_at'];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';
    protected $validationRules = [
        'tenant_id' => 'required|integer',
        'team_id' => 'required|integer',
        'player_id' => 'required|integer',
        'role' => 'permit_empty|in_list[captain,member]',
        'status' => 'permit_empty|in_list[invited,accepted,rejected,removed]',
    ];

    public function getByTeam(int $teamId, ?int $tenantId = null): array
    {
        $builder = $this->select('team_members.*, players.full_name, players.rating_score, players.level')
            ->join('players', 'players.id = team_members.player_id', 'left')
            ->where('team_members.team_id', $teamId)
            ->where('team_members.deleted_at', null);
        if ($tenantId !== null) {
            $builder->where('team_members.tenant_id', $tenantId);
        }
        return $builder->orderBy('team_members.role', 'ASC')->findAll();
    }
}
