<?php

namespace App\Models;

use CodeIgniter\Model;

class InventoryModel extends Model
{
    protected $table            = 'inventories';
    protected $primaryKey       = ['tenant_id', 'branch_id', 'product_id'];
    protected $useAutoIncrement = false;
    protected $returnType       = \App\Entities\Inventory::class;
    protected $useSoftDeletes   = false;
    protected $allowedFields    = ['tenant_id', 'branch_id', 'product_id', 'quantity'];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $validationRules = [
        'tenant_id'  => 'required|integer',
        'branch_id'  => 'required|integer',
        'product_id' => 'required|integer',
        'quantity'   => 'required|integer',
    ];

    public function getByBranch(int $tenantId, int $branchId)
    {
        return $this->select('inventories.*, products.name_vi, products.sku, products.unit, products.sale_price, product_categories.name_vi as category_name')
            ->join('products', 'products.id = inventories.product_id')
            ->join('product_categories', 'product_categories.id = products.category_id')
            ->where('inventories.tenant_id', $tenantId)
            ->where('inventories.branch_id', $branchId)
            ->orderBy('product_categories.name_vi', 'ASC')
            ->orderBy('products.name_vi', 'ASC')
            ->findAll();
    }

    public function getByProduct(int $tenantId, int $productId)
    {
        return $this->where('tenant_id', $tenantId)
            ->where('product_id', $productId)
            ->findAll();
    }
}
