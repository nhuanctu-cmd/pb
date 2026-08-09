<?php

namespace App\Models;

use CodeIgniter\Model;

class CourtSessionModel extends Model
{
    protected $table            = 'court_sessions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = \App\Entities\CourtSession::class;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'tenant_id', 'branch_id', 'court_id', 'booking_id', 'start_time',
        'expected_end_time', 'actual_end_time', 'player_count', 'player_names',
        'status', 'is_overtime', 'overtime_minutes', 'delay_minutes',
        'checked_in_by', 'created_at', 'updated_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getActiveSessions(int $branchId): array
    {
        return $this->where('branch_id', $branchId)
            ->whereIn('status', ['active', 'extended'])
            ->orderBy('start_time', 'ASC')
            ->findAll();
    }

    public function getActiveByCourt(int $courtId)
    {
        return $this->where('court_id', $courtId)
            ->whereIn('status', ['active', 'extended'])
            ->orderBy('start_time', 'DESC')
            ->first();
    }

    public function getTodaySessions(int $branchId, ?int $courtId = null): array
    {
        $builder = $this->where('branch_id', $branchId)
            ->where('DATE(start_time)', date('Y-m-d'));

        if ($courtId) {
            $builder->where('court_id', $courtId);
        }

        return $builder->orderBy('start_time', 'ASC')->findAll();
    }

    public function completeSession(int $sessionId): bool
    {
        return $this->update($sessionId, [
            'status'          => 'completed',
            'actual_end_time' => date('Y-m-d H:i:s'),
        ]);
    }

    public function checkOvertime(): array
    {
        $sessions = $this->whereIn('status', ['active', 'extended'])
            ->where('expected_end_time <', date('Y-m-d H:i:s'))
            ->findAll();

        foreach ($sessions as $session) {
            $minutes = (int) floor((time() - strtotime((string) $session->expected_end_time)) / 60);
            $this->update($session->id, [
                'is_overtime'      => 1,
                'overtime_minutes' => max(0, $minutes),
            ]);
        }

        return $sessions;
    }

    public function getUtilizationStats(int $branchId, string $date): array
    {
        $sessions = $this->select('court_id, COUNT(*) as session_count, SUM(TIMESTAMPDIFF(MINUTE, start_time, COALESCE(actual_end_time, expected_end_time))) as total_minutes')
            ->where('branch_id', $branchId)
            ->where('DATE(start_time)', $date)
            ->whereIn('status', ['active', 'extended', 'completed'])
            ->groupBy('court_id')
            ->findAll();

        return ['date' => $date, 'sessions' => $sessions];
    }
}
