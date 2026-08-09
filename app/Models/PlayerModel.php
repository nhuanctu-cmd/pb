<?php

namespace App\Models;

use CodeIgniter\Model;

class PlayerModel extends Model
{
    protected $table            = 'players';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = \App\Entities\Player::class;
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'tenant_id', 'user_id', 'player_code', 'full_name', 'phone', 'email',
        'gender', 'birthday', 'avatar', 'region', 'home_branch_id', 'level_id',
        'level', 'rating_score', 'checkin_streak', 'best_checkin_streak',
        'last_checkin_date', 'mvp_count', 'status',
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
        'player_code' => 'permit_empty|max_length[50]',
        'full_name'   => 'required|max_length[255]',
        'phone'       => 'permit_empty|max_length[20]',
        'email'       => 'permit_empty|valid_email|max_length[255]',
        'gender'      => 'permit_empty|in_list[male,female,other]',
        'level'       => 'permit_empty|in_list[beginner,intermediate,advanced,pro]',
        'status'      => 'permit_empty|in_list[active,inactive,banned]',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    protected $allowCallbacks = true;
    protected $beforeInsert   = ['generatePlayerCode'];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    protected function generatePlayerCode(array $data)
    {
        if (empty($data['data']['player_code'])) {
            $prefix = 'P' . date('ymd');
            $max = $this->db->table($this->table)
                ->select('MAX(CAST(SUBSTRING(player_code, 8) AS UNSIGNED)) as max_num')
                ->like('player_code', $prefix, 'after')
                ->get()
                ->getRow();
            $nextNum = ($max->max_num ?? 0) + 1;
            $data['data']['player_code'] = $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
        }
        return $data;
    }

    public function getByTenant(int $tenantId, array $filters = [])
    {
        $builder = $this->where('players.tenant_id', $tenantId)
                        ->where('players.deleted_at', null);

        if (!empty($filters['level'])) {
            $builder->where('players.level', $filters['level']);
        }

        if (!empty($filters['region'])) {
            $builder->where('players.region', $filters['region']);
        }

        if (!empty($filters['home_branch_id'])) {
            $builder->where('players.home_branch_id', $filters['home_branch_id']);
        }

        if (!empty($filters['status'])) {
            $builder->where('players.status', $filters['status']);
        }

        if (!empty($filters['is_member'])) {
            $today = date('Y-m-d');
            if ($filters['is_member'] === 'yes') {
                $builder->whereIn('players.id', function ($sub) use ($today) {
                    $sub->select('player_id')
                        ->from('memberships')
                        ->where('status', 'active')
                        ->where('start_date <=', $today)
                        ->where('end_date >=', $today)
                        ->where('deleted_at', null);
                });
            } elseif ($filters['is_member'] === 'no') {
                $builder->whereNotIn('players.id', function ($sub) use ($today) {
                    $sub->select('player_id')
                        ->from('memberships')
                        ->where('status', 'active')
                        ->where('start_date <=', $today)
                        ->where('end_date >=', $today)
                        ->where('deleted_at', null);
                });
            }
        }

        if (!empty($filters['search'])) {
            $builder->groupStart()
                    ->like('players.full_name', $filters['search'])
                    ->orLike('players.phone', $filters['search'])
                    ->orLike('players.email', $filters['search'])
                    ->orLike('players.player_code', $filters['search'])
                    ->groupEnd();
        }

        return $builder->orderBy('players.created_at', 'DESC')
                       ->paginate(20);
    }

    public function getRanking(int $tenantId, string $orderBy = 'rating_score', string $orderDir = 'DESC', int $limit = 50)
    {
        return $this->select('players.*, ps.elo_rating, ps.ranking_points, ps.total_matches, ps.total_wins, ps.total_losses, ps.win_rate, ps.current_streak, ps.best_streak, ps.mvp_count as stat_mvp_count')
                    ->join('player_statistics ps', 'ps.player_id = players.id AND ps.tenant_id = players.tenant_id', 'left')
                    ->where('players.tenant_id', $tenantId)
                    ->where('players.status', 'active')
                    ->where('players.deleted_at', null)
                    ->orderBy($orderBy === 'rating_score' ? 'players.rating_score' : "ps.{$orderBy}", $orderDir)
                    ->orderBy('players.rating_score', 'DESC')
                    ->limit($limit)
                    ->find();
    }

    public function findPlayerByUser(int $userId, int $tenantId)
    {
        return $this->where('user_id', $userId)
                    ->where('tenant_id', $tenantId)
                    ->where('deleted_at', null)
                    ->first();
    }

    public function getRegions(int $tenantId): array
    {
        $rows = $this->select('region')
            ->where('tenant_id', $tenantId)
            ->where('region IS NOT NULL')
            ->where('region !=', '')
            ->groupBy('region')
            ->orderBy('region', 'ASC')
            ->findAll();

        return array_values(array_filter(array_map(fn ($row) => $row->region ?? null, $rows)));
    }
}
