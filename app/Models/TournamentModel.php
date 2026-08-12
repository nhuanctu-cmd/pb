<?php

namespace App\Models;

use CodeIgniter\Model;

class TournamentModel extends Model
{
    protected $table = 'tournaments';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'tenant_id', 'branch_id', 'organizer_id', 'name_vi', 'name_en', 'slug_vi', 'slug_en',
        'description_vi', 'description_en', 'banner', 'start_date', 'end_date',
        'registration_start', 'registration_end', 'max_teams', 'registration_fee', 'status',
        'verification_level', 'organizer_reputation_score',
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    protected $validationRules = [
        'tenant_id' => 'required|integer',
        'branch_id' => 'required|integer',
        'name_vi' => 'required|max_length[255]',
        'slug_vi' => 'required|max_length[255]',
        'status' => 'required|in_list[draft,open,closed,running,completed,cancelled]',
        'verification_level' => 'permit_empty|in_list[community,club,verified,official,national]',
    ];

    public function getByTenant(int $tenantId, array $filters = []): array
    {
        $builder = $this->select('tournaments.*, branches.name as branch_name')
            ->join('branches', 'branches.id = tournaments.branch_id AND branches.tenant_id = tournaments.tenant_id', 'left')
            ->where('tournaments.tenant_id', $tenantId)
            ->where('tournaments.deleted_at', null);

        if (! empty($filters['status'])) {
            $builder->where('tournaments.status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $builder->groupStart()
                ->like('tournaments.name_vi', $filters['search'])
                ->orLike('tournaments.name_en', $filters['search'])
                ->orLike('tournaments.slug_vi', $filters['search'])
                ->groupEnd();
        }
        if (! empty($filters['date_from'])) $builder->where('tournaments.start_date >=', $filters['date_from']);
        if (! empty($filters['date_to'])) $builder->where('tournaments.start_date <=', $filters['date_to']);

        return $builder->orderBy('tournaments.start_date', 'DESC')
            ->orderBy('tournaments.id', 'DESC')
            ->findAll();
    }

    public function getByTenantPaginated(int $tenantId, int $page = 1, int $perPage = 15, array $filters = []): array
    {
        $page = max(1, $page);
        $perPage = in_array($perPage, [15, 30, 50, 100], true) ? $perPage : 15;
        $apply = function ($builder) use ($tenantId, $filters) {
            $builder->where('tournaments.tenant_id', $tenantId)->where('tournaments.deleted_at', null);
            if (! empty($filters['status'])) $builder->where('tournaments.status', $filters['status']);
            if (! empty($filters['search'])) $builder->groupStart()->like('tournaments.name_vi', $filters['search'])->orLike('tournaments.name_en', $filters['search'])->orLike('tournaments.slug_vi', $filters['search'])->groupEnd();
            if (! empty($filters['date_from'])) $builder->where('tournaments.start_date >=', $filters['date_from']);
            if (! empty($filters['date_to'])) $builder->where('tournaments.start_date <=', $filters['date_to']);
            return $builder;
        };
        $total = (int) $apply($this->builder())->countAllResults();
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);
        $items = $apply($this->select('tournaments.*, branches.name as branch_name')->join('branches', 'branches.id = tournaments.branch_id AND branches.tenant_id = tournaments.tenant_id', 'left'))
            ->orderBy('tournaments.start_date', 'DESC')->orderBy('tournaments.id', 'DESC')
            ->findAll($perPage, ($page - 1) * $perPage);
        return compact('items', 'total', 'page', 'perPage', 'pages');
    }

    public function findBySlug(string $slug, ?int $tenantId = null): ?object
    {
        $builder = $this->select('tournaments.*, branches.name as branch_name, branches.address as branch_address')
            ->join('branches', 'branches.id = tournaments.branch_id AND branches.tenant_id = tournaments.tenant_id', 'left')
            ->where('tournaments.deleted_at', null)
            ->groupStart()
                ->where('tournaments.slug_vi', $slug)
                ->orWhere('tournaments.slug_en', $slug)
            ->groupEnd();

        if ($tenantId !== null) {
            $builder->where('tournaments.tenant_id', $tenantId);
        }

        return $builder->first();
    }

    public function generateUniqueSlug(string $name, int $tenantId, ?int $excludeId = null): string
    {
        $base = generate_slug($name) ?: strtolower(generate_code('tournament-', 6));
        $slug = $base;
        $i = 2;

        while ($this->slugExists($slug, $tenantId, $excludeId)) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    private function slugExists(string $slug, int $tenantId, ?int $excludeId): bool
    {
        $builder = $this->where('tenant_id', $tenantId)
            ->where('deleted_at', null)
            ->groupStart()
                ->where('slug_vi', $slug)
                ->orWhere('slug_en', $slug)
            ->groupEnd();

        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }

        return $builder->countAllResults() > 0;
    }

    public function findForTenant(int $tournamentId, int $tenantId): ?object
    {
        return $this->where('id', $tournamentId)
            ->where('tenant_id', $tenantId)
            ->where('deleted_at', null)
            ->first();
    }

    public function findForUpdate(int $tournamentId, int $tenantId): ?object
    {
        $row = $this->db->query(
            'SELECT * FROM tournaments WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL LIMIT 1 FOR UPDATE',
            [$tournamentId, $tenantId]
        )->getRowArray();
        return $row ? (object) $row : null;
    }
}
