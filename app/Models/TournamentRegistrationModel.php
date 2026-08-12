<?php

namespace App\Models;

use CodeIgniter\Model;

class TournamentRegistrationModel extends Model
{
    protected $table = 'tournament_registrations';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'tenant_id', 'tournament_id', 'category_id', 'player_id', 'team_id', 'partner_player_id',
        'contact_name', 'contact_phone', 'payment_status', 'approval_status',
        'registration_status', 'eligibility_status',
        'checked_in_at', 'checkin_status', 'no_show', 'waitlist_position',
        'note', 'invoice_code', 'invoice_amount',
    ];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    public function getByTournament(int $tournamentId, ?int $tenantId = null): array
    {
        $builder = $this->select('tournament_registrations.*, tournament_categories.name_vi as category_name')
            ->join('tournament_categories', 'tournament_categories.id = tournament_registrations.category_id AND tournament_categories.tenant_id = tournament_registrations.tenant_id', 'left')
            ->where('tournament_registrations.tournament_id', $tournamentId)
            ->where('tournament_registrations.deleted_at', null);
        if ($tenantId !== null) {
            $builder->where('tournament_registrations.tenant_id', $tenantId);
        }
        return $builder->orderBy('tournament_registrations.created_at', 'DESC')->findAll();
    }

    public function getByTournamentPaginated(int $tournamentId, int $tenantId, int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $page = max(1, $page);
        $perPage = in_array($perPage, [20, 50, 100], true) ? $perPage : 20;
        $apply = function ($builder) use ($tournamentId, $tenantId, $filters) {
            $builder->where('tournament_registrations.tournament_id', $tournamentId)
                ->where('tournament_registrations.tenant_id', $tenantId)
                ->where('tournament_registrations.deleted_at', null);
            if (! empty($filters['category_id'])) $builder->where('tournament_registrations.category_id', (int) $filters['category_id']);
            if (! empty($filters['approval_status'])) $builder->where('tournament_registrations.approval_status', $filters['approval_status']);
            if (! empty($filters['payment_status'])) $builder->where('tournament_registrations.payment_status', $filters['payment_status']);
            if (! empty($filters['checkin_status'])) $builder->where('tournament_registrations.checkin_status', $filters['checkin_status']);
            if (! empty($filters['search'])) $builder->groupStart()->like('tournament_registrations.contact_name', $filters['search'])->orLike('tournament_registrations.contact_phone', $filters['search'])->orLike('tournament_registrations.invoice_code', $filters['search'])->groupEnd();
            return $builder;
        };
        $total = (int) $apply($this->builder())->countAllResults();
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);
        $items = $apply($this->select('tournament_registrations.*, tournament_categories.name_vi as category_name')->join('tournament_categories', 'tournament_categories.id = tournament_registrations.category_id AND tournament_categories.tenant_id = tournament_registrations.tenant_id', 'left'))
            ->orderBy('tournament_registrations.created_at', 'DESC')->findAll($perPage, ($page - 1) * $perPage);
        return compact('items', 'total', 'page', 'perPage', 'pages');
    }

    public function countApprovedByCategory(int $categoryId, ?int $tenantId = null): int
    {
        $builder = $this->where('category_id', $categoryId)
            ->where('approval_status', 'approved')
            ->where('deleted_at', null);
        if ($tenantId !== null) {
            $builder->where('tenant_id', $tenantId);
        }
        return $builder->countAllResults();
    }

    public function countConfirmedByCategory(int $categoryId, ?int $tenantId = null): int
    {
        $builder = $this->where('category_id', $categoryId)
            ->where('registration_status', 'confirmed')
            ->where('deleted_at', null);
        if ($tenantId !== null) {
            $builder->where('tenant_id', $tenantId);
        }
        return $builder->countAllResults();
    }

    public function getNextWaitlistPosition(int $categoryId, ?int $tenantId = null): int
    {
        $builder = $this->where('category_id', $categoryId)
            ->where('registration_status', 'waitlisted')
            ->where('deleted_at', null);
        if ($tenantId !== null) {
            $builder->where('tenant_id', $tenantId);
        }
        $max = $builder->selectMax('waitlist_position')->get()->getRow();
        return ($max && $max->waitlist_position) ? ((int) $max->waitlist_position + 1) : 1;
    }
}
