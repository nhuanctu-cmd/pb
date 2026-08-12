<?php

namespace App\Services;

use Config\Database;

/** Creates review signals without allowing a club or player to alter official rating. */
class RatingIntegrityService
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function evaluate(object $match, array $participants, array $payload): array
    {
        $flags = [];
        $playerIds = array_map(static fn ($p): int => (int) ($p->player_id ?? 0), $participants);
        if (count($playerIds) !== count(array_unique($playerIds))) $flags[] = ['code' => 'DUPLICATE_PARTICIPANT', 'risk_score' => 95];
        foreach ((array) ($payload['games'] ?? []) as $game) {
            $a = (int) ($game['side_a_score'] ?? $game['team_a_score'] ?? $game['a'] ?? -1);
            $b = (int) ($game['side_b_score'] ?? $game['team_b_score'] ?? $game['b'] ?? -1);
            if ($a < 0 || $b < 0 || ($a === 0 && $b === 0) || $a > 99 || $b > 99) $flags[] = ['code' => 'INVALID_GAME_SCORE', 'risk_score' => 90, 'details' => ['game' => $game]];
        }
        $date = strtotime((string) ($match->completed_at ?? $match->created_at ?? 'now'));
        if ($date && $date < strtotime('-365 days')) $flags[] = ['code' => 'BACKDATED_RESULT', 'risk_score' => 30, 'details' => ['completed_at' => $match->completed_at ?? null]];
        return ['review_required' => (bool) $flags, 'flags' => $flags];
    }

    public function record(int $tenantId, int $matchId, array $flags): void
    {
        if (! $flags || $tenantId <= 0 || ! $this->db->tableExists('rating_integrity_flags')) return;
        foreach ($flags as $flag) {
            $exists = $this->db->table('rating_integrity_flags')->where('match_id', $matchId)->where('code', $flag['code'])->whereIn('status', ['open', 'approved'])->countAllResults();
            if ($exists) continue;
            $this->db->table('rating_integrity_flags')->insert(['tenant_id' => $tenantId, 'match_id' => $matchId, 'code' => $flag['code'], 'risk_score' => $flag['risk_score'], 'status' => 'open', 'details' => json_encode($flag['details'] ?? [], JSON_UNESCAPED_UNICODE), 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
        }
    }
}
