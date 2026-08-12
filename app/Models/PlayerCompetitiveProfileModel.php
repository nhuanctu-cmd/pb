<?php

namespace App\Models;

use CodeIgniter\Model;

class PlayerCompetitiveProfileModel extends Model
{
    protected $table = 'player_competitive_profiles';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'player_id', 'national_player_id', 'display_name', 'country_code', 'administrative_area_code', 'slug', 'avatar_url',
        'province_id', 'city_id', 'club_id', 'gender_category', 'age_category_public',
        'internal_rating_summary', 'external_rating_summary', 'national_rank_summary',
        'reliability_score', 'privacy_level', 'status', 'verified_at',
    ];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    protected $validationRules = [
        'player_id' => 'required|integer',
        'national_player_id' => 'required|max_length[40]|is_unique[player_competitive_profiles.national_player_id,id,{id}]',
        'privacy_level' => 'permit_empty|in_list[public,club,private]',
        'status' => 'permit_empty|in_list[unverified,verified,official,suspended]',
    ];

    public function findByPlayerId(int $playerId): ?object
    {
        return $this->where('player_id', $playerId)->where('deleted_at', null)->first();
    }

    public function findByNationalId(string $nationalPlayerId): ?object
    {
        return $this->where('national_player_id', $nationalPlayerId)->where('deleted_at', null)->first();
    }

    public function findBySlug(string $slug): ?object
    {
        return $this->where('slug', $slug)->where('deleted_at', null)->first();
    }

    public function generateNationalPlayerId(): string
    {
        // Existing VN-PKL-* values remain valid. New public IDs are random so
        // they do not reveal player volume/order and are safe under concurrency.
        do {
            $candidate = 'VNP-' . str_pad((string) random_int(1, 99999999999), 11, '0', STR_PAD_LEFT);
        } while ($this->where('national_player_id', $candidate)->withDeleted()->first());

        return $candidate;
    }
}
