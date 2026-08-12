<?php

namespace App\Services;

use Config\Database;

class SkillBandResolver
{
    protected $db;

    public function __construct() { $this->db = Database::connect(); }

    public function resolve(?float $rating): ?object
    {
        if ($rating === null) return $this->byCode('NR');
        $builder = $this->db->table('skill_level_bands')->where('active', 1)->where('min_rating <=', $rating)->groupStart()->where('max_rating >=', $rating)->orWhere('max_rating', null)->groupEnd()->orderBy('display_order', 'DESC');
        return $builder->get()->getRow() ?: $this->byCode('NR');
    }

    public function byCode(string $code): ?object
    {
        if (! $this->db->tableExists('skill_level_bands')) return null;
        return $this->db->table('skill_level_bands')->where('code', $code)->where('active', 1)->get()->getRow();
    }

    public function id(?float $rating): ?int
    {
        $band = $this->resolve($rating);
        return $band ? (int) $band->id : null;
    }

    /** Keeps a player in the previous band until they cross a small hysteresis buffer. */
    public function resolveStable(?float $rating, ?int $currentBandId, float $buffer = 0.05): ?object
    {
        if ($rating === null || ! $currentBandId) return $this->resolve($rating);
        $current = $this->db->table('skill_level_bands')->where('id', $currentBandId)->where('active', 1)->get()->getRow();
        if (! $current) return $this->resolve($rating);
        $min = $current->min_rating !== null ? (float) $current->min_rating - $buffer : -INF;
        $max = $current->max_rating !== null ? (float) $current->max_rating + $buffer : INF;
        if ($rating >= $min && $rating <= $max) return $current;
        return $this->resolve($rating);
    }
}
