<?php

namespace App\Models;

use CodeIgniter\Model;

class SkillLevelBandModel extends Model
{
    protected $table = 'skill_level_bands';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $allowedFields = ['code', 'name', 'min_rating', 'max_rating', 'display_order', 'active', 'metadata'];
    protected $useTimestamps = true;
}
