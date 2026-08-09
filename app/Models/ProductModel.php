<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductModel extends Model
{
    protected $table            = 'products';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = ['tenant_id', 'category_id', 'sku', 'name_vi', 'name_en', 'unit', 'cost_price', 'sale_price', 'image', 'status'];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $validationRules = [
        'tenant_id'   => 'required|integer',
        'category_id' => 'required|integer',
        'name_vi'     => 'required|max_length[255]',
        'name_en'     => 'required|max_length[255]',
        'sale_price'  => 'required|decimal',
        'status'      => 'required|in_list[active,inactive]',
    ];

    public function getByTenant(int $tenantId, bool $activeOnly = true)
    {
        $builder = $this->select('products.*, product_categories.name_vi as category_name')
            ->join('product_categories', 'product_categories.id = products.category_id')
            ->where('products.tenant_id', $tenantId);
        if ($activeOnly) {
            $builder->where('products.status', 'active');
        }
        return $builder->orderBy('products.category_id', 'ASC')->orderBy('products.name_vi', 'ASC')->findAll();
    }

    public function getWithStock(int $tenantId, ?int $branchId = null)
    {
        $builder = $this->select('products.*, COALESCE(SUM(inventories.quantity), 0) as stock')
            ->join('inventories', 'inventories.product_id = products.id', 'left');
        $builder->where('products.tenant_id', $tenantId);
        $builder->where('products.status', 'active');
        if ($branchId) {
            $builder->where('inventories.branch_id', $branchId);
        }
        $builder->groupBy('products.id');
        return $builder->findAll();
    }
}
