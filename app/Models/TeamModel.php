<?php

namespace App\Models;

use CodeIgniter\Model;

class TeamModel extends Model
{
    protected $table = 'teams';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'tenant_id', 'club_id', 'team_name', 'captain_player_id', 'team_type', 'rating_avg', 'status',
    ];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';
    protected $validationRules = [
        'tenant_id' => 'required|integer',
        'team_name' => 'required|max_length[255]',
        'captain_player_id' => 'required|integer',
        'team_type' => 'required|in_list[male_double,female_double,mixed_double,group]',
        'status' => 'permit_empty|in_list[active,inactive,disbanded]',
    ];

    public function getByTenant(int $tenantId, array $filters = []): array
    {
        $builder = $this->select('teams.*, clubs.name_vi as club_name, players.full_name as captain_name')
            ->join('clubs', 'clubs.id = teams.club_id', 'left')
            ->join('players', 'players.id = teams.captain_player_id', 'left')
            ->where('teams.tenant_id', $tenantId)
            ->where('teams.deleted_at', null);

        if (! empty($filters['player_id'])) {
            $builder->join('team_members tm_filter', 'tm_filter.team_id = teams.id AND tm_filter.deleted_at IS NULL', 'inner')
                ->where('tm_filter.player_id', $filters['player_id'])
                ->whereIn('tm_filter.status', ['invited', 'accepted']);
        }

        if (! empty($filters['status'])) {
            $builder->where('teams.status', $filters['status']);
        }

        return $builder->groupBy('teams.id')->orderBy('teams.created_at', 'DESC')->findAll();
    }
}
