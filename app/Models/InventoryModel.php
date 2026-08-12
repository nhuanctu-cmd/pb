<?php

namespace App\Models;

use CodeIgniter\Model;

class InventoryModel extends Model
{
    protected $table            = 'inventories';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
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
        return $this->select('inventories.*, products.name_vi, products.sku, products.unit, products.cost_price, products.sale_price, products.status, product_categories.name_vi as category_name')
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

    public function findForUpdate(int $tenantId, int $branchId, int $productId): ?array
    {
        return $this->db->query(
            'SELECT * FROM inventories WHERE tenant_id = ? AND branch_id = ? AND product_id = ? LIMIT 1 FOR UPDATE',
            [$tenantId, $branchId, $productId]
        )->getRowArray() ?: null;
    }
}
