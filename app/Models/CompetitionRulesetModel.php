<?php

namespace App\Models;

use CodeIgniter\Model;

class CompetitionRulesetModel extends Model
{
    protected $table = 'competition_rulesets';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $allowedFields = ['code', 'name_en', 'name_vi', 'discipline', 'status'];
    protected $useTimestamps = true;

    public function active(): array { return $this->where('status', 'active')->orderBy('name_en')->findAll(); }
    public function byCode(string $code): ?object { return $this->where('code', $code)->where('status', 'active')->first(); }
}
