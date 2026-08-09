<?php

namespace App\Models;

use CodeIgniter\Model;

class CompetitionParticipantModel extends Model
{
    protected $table = 'competition_participants';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $allowedFields = ['tenant_id', 'event_id', 'team_id', 'player_id', 'invoice_id', 'display_name', 'seed', 'status'];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    public function getByEvent(int $eventId, int $tenantId): array
    {
        return $this->select('competition_participants.*, invoices.status as invoice_status, invoices.total_amount as invoice_total_amount, invoices.paid_amount as invoice_paid_amount')->join('invoices', 'invoices.id = competition_participants.invoice_id AND invoices.tenant_id = competition_participants.tenant_id', 'left')->where('competition_participants.event_id', $eventId)->where('competition_participants.tenant_id', $tenantId)->where('competition_participants.deleted_at', null)->where('competition_participants.status', 'active')->orderBy('competition_participants.seed', 'ASC')->orderBy('competition_participants.id', 'ASC')->findAll();
    }

    public function findForTenant(int $id, int $tenantId): ?object
    {
        return $this->select('competition_participants.*, invoices.status as invoice_status, invoices.total_amount as invoice_total_amount, invoices.paid_amount as invoice_paid_amount')->join('invoices', 'invoices.id = competition_participants.invoice_id AND invoices.tenant_id = competition_participants.tenant_id', 'left')->where('competition_participants.id', $id)->where('competition_participants.tenant_id', $tenantId)->where('competition_participants.deleted_at', null)->first();
    }
}
