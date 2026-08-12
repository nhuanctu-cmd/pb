<?php

namespace App\Models;

use CodeIgniter\Model;

class TournamentCategoryModel extends Model
{
    protected $table = 'tournament_categories';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'tenant_id', 'tournament_id', 'name_vi', 'name_en', 'category_type',
        'discipline', 'gender_category', 'age_category', 'team_size',
        'max_teams', 'entry_capacity', 'waitlist_capacity',
        'min_rating', 'max_rating', 'registration_fee', 'status',
        'eligibility_rules',
    ];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    public function getByTournament(int $tournamentId): array
    {
        return $this->where('tournament_id', $tournamentId)
            ->where('deleted_at', null)
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    public function findForTenant(int $categoryId, int $tenantId): ?object
    {
        return $this->where('id', $categoryId)
            ->where('tenant_id', $tenantId)
            ->where('deleted_at', null)
            ->first();
    }
}
