<?php

namespace App\Models;

use CodeIgniter\Model;

class TournamentCheckinModel extends Model
{
    protected $table = 'tournament_checkins';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'tenant_id', 'tournament_id', 'category_id', 'registration_id', 'player_id',
        'qr_code', 'status', 'checked_in_by', 'checked_in_at', 'note',
    ];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function findByRegistration(int $registrationId, int $playerId): ?object
    {
        return $this->where('registration_id', $registrationId)
            ->where('player_id', $playerId)
            ->first();
    }

    public function findByQrCode(string $qrCode): ?object
    {
        return $this->where('qr_code', $qrCode)->first();
    }

    public function countCheckedInByCategory(int $categoryId): int
    {
        return $this->where('category_id', $categoryId)
            ->where('status', 'checked_in')
            ->countAllResults();
    }
}
