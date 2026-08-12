<?php

namespace App\Models;

use CodeIgniter\Model;

class RatingDisciplineModel extends Model
{
    protected $table = 'rating_disciplines';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $allowedFields = ['code', 'name', 'description', 'active', 'metadata'];
    protected $useTimestamps = true;
}
