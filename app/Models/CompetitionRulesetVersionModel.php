<?php

namespace App\Models;

use CodeIgniter\Model;

class CompetitionRulesetVersionModel extends Model
{
    protected $table = 'competition_ruleset_versions';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $allowedFields = ['ruleset_id', 'version', 'configuration', 'effective_from', 'status', 'created_by'];
    protected $useTimestamps = true;

    public function activeFor(int $rulesetId): array { return $this->where('ruleset_id', $rulesetId)->whereIn('status', ['active', 'locked'])->orderBy('id', 'DESC')->findAll(); }
}
