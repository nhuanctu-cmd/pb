<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductCategoryModel extends Model
{
    protected $table            = 'product_categories';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = \App\Entities\ProductCategory::class;
    protected $useSoftDeletes   = false;
    protected $allowedFields    = ['tenant_id', 'name_vi', 'name_en', 'status'];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $beforeInsert     = [];
    protected $afterInsert      = [];
    protected $beforeUpdate     = [];
    protected $afterUpdate      = [];
    protected $beforeFind       = [];
    protected $afterFind        = [];
    protected $beforeDelete     = [];
    protected $afterDelete      = [];

    protected $validationRules = [
        'tenant_id' => 'required|integer',
        'name_vi' => 'required|max_length[255]',
        'name_en' => 'required|max_length[255]',
        'status' => 'required|in_list[active,inactive]',
    ];

    public function getByTenant(int $tenantId, bool $activeOnly = true)
    {
        $builder = $this->where('tenant_id', $tenantId);
        if ($activeOnly) {
            $builder->where('status', 'active');
        }
        return $builder->orderBy('name_vi', 'ASC')->findAll();
    }
}
