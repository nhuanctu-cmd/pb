<?php

namespace App\Models;

use CodeIgniter\Model;

class InventoryMovementModel extends Model
{
    protected $table            = 'inventory_movements';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = \App\Entities\InventoryMovement::class;
    protected $useSoftDeletes   = false;
    protected $allowedFields    = ['tenant_id', 'branch_id', 'product_id', 'movement_type', 'quantity', 'before_qty', 'after_qty', 'ref_type', 'ref_id', 'note', 'created_by'];
    protected $useTimestamps    = false;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $validationRules = [
        'tenant_id'    => 'required|integer',
        'branch_id'    => 'required|integer',
        'product_id'   => 'required|integer',
        'movement_type' => 'required|in_list[import,sale,return,adjust]',
        'quantity'     => 'required|integer',
        'before_qty'   => 'required|integer',
        'after_qty'    => 'required|integer',
    ];

    public function getByTenant(int $tenantId, ?int $branchId = null, ?string $type = null, int $limit = 100)
    {
        $builder = $this->select('inventory_movements.*, products.name_vi, products.sku, product_categories.name_vi as category_name')
            ->join('products', 'products.id = inventory_movements.product_id')
            ->join('product_categories', 'product_categories.id = products.category_id')
            ->where('inventory_movements.tenant_id', $tenantId);

        if ($branchId) {
            $builder->where('inventory_movements.branch_id', $branchId);
        }
        if ($type) {
            $builder->where('inventory_movements.movement_type', $type);
        }

        return $builder->orderBy('inventory_movements.created_at', 'DESC')->limit($limit)->findAll();
    }
}
