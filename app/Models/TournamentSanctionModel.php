<?php

namespace App\Models;

use CodeIgniter\Model;

class TournamentSanctionModel extends Model
{
    protected $table = 'tournament_sanctions';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'tournament_id', 'ranking_authority_id', 'sanction_id', 'status',
        'tier_id', 'point_multiplier', 'approved_by', 'approved_at', 'expires_at',
        'authority_id', 'workflow_status', 'submitted_by', 'submitted_at',
        'ruleset_version_id', 'policy_snapshot',
    ];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function findByTournament(int $tournamentId, ?int $rankingAuthorityId = null): ?object
    {
        $builder = $this->where('tournament_id', $tournamentId);
        if ($rankingAuthorityId !== null) {
            $builder->where('ranking_authority_id', $rankingAuthorityId);
        }
        return $builder->first();
    }

    public function generateSanctionId(int $rankingAuthorityId, string $year, string $provinceCode): string
    {
        $count = $this->where('ranking_authority_id', $rankingAuthorityId)
            ->where(" sanction_id LIKE ? ", ["%{$year}-{$provinceCode}-%"])
            ->countAllResults();
        $seq = str_pad((string) ($count + 1), 5, '0', STR_PAD_LEFT);
        return strtoupper("VNPKL-{$year}-{$provinceCode}-{$seq}");
    }
}
