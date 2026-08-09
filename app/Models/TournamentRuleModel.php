<?php

namespace App\Models;

use CodeIgniter\Model;

class TournamentRuleModel extends Model
{
    protected $table = 'tournament_rules';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $allowedFields = ['tenant_id', 'tournament_id', 'rule_content_vi', 'rule_content_en'];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
