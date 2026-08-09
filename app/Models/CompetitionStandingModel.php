<?php

namespace App\Models;

use CodeIgniter\Model;

class CompetitionStandingModel extends Model
{
    protected $table = 'competition_standings';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $allowedFields = ['tenant_id', 'event_id', 'participant_id', 'played', 'wins', 'draws', 'losses', 'points_for', 'points_against', 'points', 'rank_no'];
    protected $useTimestamps = false;

    public function getByEvent(int $eventId, int $tenantId): array
    {
        return $this->select('competition_standings.*, competition_participants.display_name')->join('competition_participants', 'competition_participants.id = competition_standings.participant_id', 'left')->where('competition_standings.event_id', $eventId)->where('competition_standings.tenant_id', $tenantId)->orderBy('rank_no')->findAll();
    }
}
