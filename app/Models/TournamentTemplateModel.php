<?php

namespace App\Models;

use CodeIgniter\Model;

class TournamentTemplateModel extends Model
{
    protected $table = 'tournament_templates';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $allowedFields = [
        'tenant_id', 'source_tournament_id', 'name', 'description', 'snapshot',
        'is_active', 'created_by',
    ];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';

    public function getByTenant(int $tenantId): array
    {
        return $this->where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    public function getByTenantPaginated(int $tenantId, int $page = 1, int $perPage = 12, string $search = ''): array
    {
        $page = max(1, $page);
        $perPage = in_array($perPage, [12, 24, 48], true) ? $perPage : 12;
        $apply = function ($builder) use ($tenantId, $search) {
            $builder->where('tournament_templates.tenant_id', $tenantId)->where('tournament_templates.is_active', 1);
            if ($search !== '') $builder->groupStart()->like('tournament_templates.name', $search)->orLike('tournament_templates.description', $search)->groupEnd();
            return $builder;
        };
        $total = (int) $apply($this->builder())->countAllResults();
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);
        $items = $apply($this->select('tournament_templates.*, tournaments.name_vi AS source_tournament_name')->join('tournaments', 'tournaments.id = tournament_templates.source_tournament_id AND tournaments.tenant_id = tournament_templates.tenant_id', 'left'))
            ->orderBy('tournament_templates.created_at', 'DESC')->findAll($perPage, ($page - 1) * $perPage);
        return compact('items', 'total', 'page', 'perPage', 'pages', 'search');
    }

    public function findForTenant(int $id, int $tenantId): ?object
    {
        return $this->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->first();
    }
}
