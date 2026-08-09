<?php

namespace App\Models;

use CodeIgniter\Model;

class TournamentScoreLogModel extends Model
{
    protected $table = 'tournament_score_logs';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $allowedFields = [
        'tenant_id', 'match_id', 'old_score_json', 'new_score_json', 'changed_by', 'reason', 'created_at',
    ];
    protected $useTimestamps = false;

    public function addLog(int $tenantId, int $matchId, array $oldScore, array $newScore, ?int $changedBy, ?string $reason): int
    {
        return (int) $this->insert([
            'tenant_id' => $tenantId,
            'match_id' => $matchId,
            'old_score_json' => json_encode($oldScore, JSON_UNESCAPED_UNICODE),
            'new_score_json' => json_encode($newScore, JSON_UNESCAPED_UNICODE),
            'changed_by' => $changedBy,
            'reason' => $reason,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
