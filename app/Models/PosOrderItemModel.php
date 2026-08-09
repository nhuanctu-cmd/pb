<?php

namespace App\Models;

use CodeIgniter\Model;

class PosOrderItemModel extends Model
{
    protected $table            = 'pos_order_items';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = ['tenant_id', 'order_id', 'product_id', 'quantity', 'price', 'total'];
    protected $useTimestamps    = false;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $validationRules = [
        'tenant_id'  => 'required|integer',
        'order_id'   => 'required|integer',
        'product_id' => 'required|integer',
        'quantity'   => 'required|integer',
        'price'      => 'required|decimal',
        'total'      => 'required|decimal',
    ];

    public function getByOrder(int $orderId)
    {
        return $this->select('pos_order_items.*, products.name_vi, products.unit, products.image, product_categories.name_vi as category_name')
            ->join('products', 'products.id = pos_order_items.product_id')
            ->join('product_categories', 'product_categories.id = products.category_id')
            ->where('pos_order_items.order_id', $orderId)
            ->findAll();
    }
}
