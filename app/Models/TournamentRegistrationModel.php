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
        'tenant_id', 'tournament_id', 'category_id', 'player_id', 'team_id',
        'contact_name', 'contact_phone', 'payment_status', 'approval_status',
        'note', 'invoice_code', 'invoice_amount',
    ];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    public function getByTournament(int $tournamentId): array
    {
        return $this->select('tournament_registrations.*, tournament_categories.name_vi as category_name')
            ->join('tournament_categories', 'tournament_categories.id = tournament_registrations.category_id', 'left')
            ->where('tournament_registrations.tournament_id', $tournamentId)
            ->where('tournament_registrations.deleted_at', null)
            ->orderBy('tournament_registrations.created_at', 'DESC')
            ->findAll();
    }

    public function countApprovedByCategory(int $categoryId): int
    {
        return $this->where('category_id', $categoryId)
            ->where('approval_status', 'approved')
            ->where('deleted_at', null)
            ->countAllResults();
    }
}
