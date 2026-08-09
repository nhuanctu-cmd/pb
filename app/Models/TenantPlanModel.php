<?php

namespace App\Models;

use CodeIgniter\Model;

class TenantPlanModel extends Model
{
    protected $table            = 'tenant_plans';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'code', 'name_vi', 'name_en', 'description_vi', 'description_en',
        'max_branches', 'max_courts', 'max_players', 'max_staff',
        'price_monthly', 'price_yearly', 'features', 'is_active',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';

    protected $validationRules = [
        'code'          => 'required|alpha_dash|max_length[50]|is_unique[tenant_plans.code,id,{id}]',
        'name_vi'       => 'required|max_length[255]',
        'name_en'       => 'required|max_length[255]',
        'max_branches'  => 'required|integer',
        'max_courts'    => 'required|integer',
        'max_players'   => 'required|integer',
        'max_staff'     => 'required|integer',
        'price_monthly' => 'permit_empty|decimal',
        'price_yearly'  => 'permit_empty|decimal',
    ];

    public function getActive(): array
    {
        return $this->where('is_active', 1)->orderBy('price_monthly', 'ASC')->findAll();
    }

    public function getByCode(string $code): ?array
    {
        $row = $this->where('code', $code)->first();
        return $row ?: null;
    }

    /**
     * Lấy danh sách tính năng của gói (decode JSON)
     */
    public function getFeatures(array $plan): array
    {
        if (empty($plan['features'])) {
            return [];
        }

        $features = json_decode($plan['features'], true);
        return is_array($features) ? $features : [];
    }
}
