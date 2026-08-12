<?php

namespace App\Models;

use CodeIgniter\Model;

class PlatformCountryModel extends Model
{
    protected $table = 'platform_countries';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $allowedFields = ['code', 'name_en', 'name_vi', 'default_currency', 'default_timezone', 'status'];
    protected $useTimestamps = true;

    public function active(): array { return $this->where('status', 'active')->orderBy('name_en')->findAll(); }
    public function byCode(string $code): ?object { return $this->where('code', strtoupper(trim($code)))->where('status', 'active')->first(); }
}
