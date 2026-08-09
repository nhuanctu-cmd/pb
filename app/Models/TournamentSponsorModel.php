<?php

namespace App\Models;

use CodeIgniter\Model;

class TournamentSponsorModel extends Model
{
    protected $table = 'tournament_sponsors';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $allowedFields = ['tenant_id', 'tournament_id', 'sponsor_name', 'logo', 'website', 'sort_order', 'status'];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    public function getByTournament(int $tournamentId): array
    {
        return $this->where('tournament_id', $tournamentId)
            ->where('deleted_at', null)
            ->orderBy('sort_order', 'ASC')
            ->findAll();
    }
}
