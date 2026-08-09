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

    public function getDueOccurrences(): array
    {
        return $this->where('status', 'active')
            ->where('next_occurrence <=', date('Y-m-d'))
            ->where('deleted_at', null)
            ->findAll();
    }

    public function advanceOccurrence(int $id): bool
    {
        $template = $this->find($id);
        if (!$template) {
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
        ]);
    }
}
