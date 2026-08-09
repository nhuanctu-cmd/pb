<?php

namespace App\Models;

use CodeIgniter\Model;

class CompetitionFixtureModel extends Model
{
    protected $table = 'competition_fixtures';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $allowedFields = ['tenant_id', 'event_id', 'round_no', 'match_no', 'participant_a_id', 'participant_b_id', 'scheduled_date', 'start_time', 'court_id', 'score_a', 'score_b', 'winner_id', 'status'];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function getByEvent(int $eventId, int $tenantId): array
    {
        return $this->select('competition_fixtures.*, pa.display_name as participant_a_name, pb.display_name as participant_b_name')->join('competition_participants pa', 'pa.id = competition_fixtures.participant_a_id', 'left')->join('competition_participants pb', 'pb.id = competition_fixtures.participant_b_id', 'left')->where('competition_fixtures.event_id', $eventId)->where('competition_fixtures.tenant_id', $tenantId)->orderBy('round_no')->orderBy('match_no')->findAll();
    }

    public function findForUpdate(int $id, int $tenantId): ?object
    {
        $row = $this->db->query('SELECT * FROM competition_fixtures WHERE id = ? AND tenant_id = ? LIMIT 1 FOR UPDATE', [$id, $tenantId])->getRowArray();
        return $row ? (object) $row : null;
    }
}
