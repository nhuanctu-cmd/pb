<?php

namespace App\Models;

use CodeIgniter\Model;

class BookingRecurringTemplateModel extends Model
{
    protected $table            = 'booking_recurring_templates';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'tenant_id', 'branch_id', 'court_id', 'player_id', 'name', 'start_date',
        'end_date', 'start_time', 'end_time', 'duration_minutes', 'repeat_type',
        'repeat_interval', 'repeat_days', 'exclude_dates', 'status',
        'total_occurrences', 'completed_occurrences', 'next_occurrence',
        'created_by', 'updated_by',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    public function getByTenant(int $tenantId, array $filters = []): array
    {
        $builder = $this->where('tenant_id', $tenantId)->where('deleted_at', null);
        if (!empty($filters['status'])) {
            $builder->where('status', $filters['status']);
        }
        if (!empty($filters['branch_id'])) {
            $builder->where('branch_id', (int) $filters['branch_id']);
        }
        return $builder->orderBy('next_occurrence', 'ASC')->orderBy('id', 'ASC')->findAll();
    }

    public function findForTenant(int $id, int $tenantId): ?object
    {
        return $this->where('id', $id)->where('tenant_id', $tenantId)->where('deleted_at', null)->first();
    }

    public function findForUpdate(int $id, int $tenantId): ?object
    {
        $row = $this->db->query(
            'SELECT * FROM booking_recurring_templates WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL LIMIT 1 FOR UPDATE',
            [$id, $tenantId]
        )->getRowArray();
        return $row ? (object) $row : null;
    }

    public function getDueOccurrences(int $tenantId, int $limit = 20): array
    {
        return $this->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->where('next_occurrence <=', date('Y-m-d'))
            ->where('deleted_at', null)
            ->orderBy('next_occurrence', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll(max(1, min(100, $limit)));
    }

    public function advanceOccurrence(int $id, int $tenantId): bool
    {
        $template = $this->findForUpdate($id, $tenantId);
        if (!$template || !$template->next_occurrence) {
            return false;
        }

        $interval = max(1, (int) ($template->repeat_interval ?? 1));
        $modifier = match ($template->repeat_type) {
            'daily' => "+{$interval} day",
            'weekly' => "+{$interval} week",
            'biweekly' => '+2 week',
            'monthly' => "+{$interval} month",
            default => "+{$interval} week",
        };

        $next = date('Y-m-d', strtotime((string) $template->next_occurrence . ' ' . $modifier));
        $completed = ((int) $template->completed_occurrences) + 1;
        $status = ($template->end_date && $next > $template->end_date) ? 'completed' : 'active';

        return $this->update($id, [
            'completed_occurrences' => $completed,
            'next_occurrence'       => $next,
            'status'                => $status,
            'updated_by'            => session('userId'),
        ]);
    }
}
