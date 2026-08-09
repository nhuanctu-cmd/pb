<?php

namespace App\Services;

use App\Models\InventoryModel;
use App\Models\InventoryMovementModel;
use App\Models\ProductModel;
use CodeIgniter\Database\Exceptions\DatabaseException;

class InventoryService
{
    protected InventoryModel $inventoryModel;
    protected InventoryMovementModel $movementModel;
    protected ProductModel $productModel;

    public function __construct()
    {
        $this->inventoryModel = new InventoryModel();
        $this->movementModel = new InventoryMovementModel();
        $this->productModel = new ProductModel();
    }

    /**
     * Check stock availability
     */
    public function checkStock(int $tenantId, int $branchId, int $productId, int $quantity = 1): bool
    {
        $inventory = $this->inventoryModel
            ->where('tenant_id', $tenantId)
            ->where('branch_id', $branchId)
            ->where('product_id', $productId)
            ->first();

        $currentQty = $inventory ? (int)$inventory['quantity'] : 0;
        return $currentQty >= $quantity;
    }

    /**
     * Get current stock quantity
     */
    public function getStock(int $tenantId, int $branchId, int $productId): int
    {
        $inventory = $this->inventoryModel
            ->where('tenant_id', $tenantId)
            ->where('branch_id', $branchId)
            ->where('product_id', $productId)
            ->first();

        return $inventory ? (int)$inventory['quantity'] : 0;
    }

    /**
     * Import stock (nhập kho)
     */
    public function importStock(int $tenantId, int $branchId, int $productId, int $quantity, ?int $userId = null, ?string $note = null): bool
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than 0');
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // Get current inventory
            $inventory = $this->inventoryModel
                ->where('tenant_id', $tenantId)
                ->where('branch_id', $branchId)
                ->where('product_id', $productId)
                ->first();

            $beforeQty = $inventory ? (int)$inventory['quantity'] : 0;
            $afterQty = $beforeQty + $quantity;

            // Update or create inventory
            if ($inventory) {
                $this->inventoryModel->update($inventory['id'], ['quantity' => $afterQty]);
            } else {
                $this->inventoryModel->insert([
                    'tenant_id' => $tenantId,
                    'branch_id' => $branchId,
                    'product_id' => $productId,
                    'quantity' => $afterQty,
                ]);
            }

            // Record movement
            $this->movementModel->insert([
                'tenant_id'    => $tenantId,
                'branch_id'    => $branchId,
                'product_id'   => $productId,
                'movement_type' => 'import',
                'quantity'     => $quantity,
                'before_qty'   => $beforeQty,
                'after_qty'    => $afterQty,
                'note'         => $note,
                'created_by'   => $userId,
                'created_at'   => date('Y-m-d H:i:s'),
            ]);

            $db->transComplete();
            return $db->transStatus();
        } catch (\Exception $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /**
     * Sale stock (bán hàng - tự trừ kho)
     */
    public function saleStock(int $tenantId, int $branchId, int $productId, int $quantity, ?string $refType = null, ?int $refId = null, ?int $userId = null): bool
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than 0');
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $inventory = $this->inventoryModel
                ->where('tenant_id', $tenantId)
                ->where('branch_id', $branchId)
                ->where('product_id', $productId)
                ->first();

            if (!$inventory) {
                throw new DatabaseException('Inventory not found for product: ' . $productId);
            }

            $beforeQty = (int)$inventory['quantity'];
            $afterQty = $beforeQty - $quantity;

            if ($afterQty < 0) {
                throw new DatabaseException('Insufficient stock for product: ' . $productId . '. Current: ' . $beforeQty . ', Requested: ' . $quantity);
            }

            // Update inventory
            $this->inventoryModel->update($inventory['id'], ['quantity' => $afterQty]);

            // Record movement
            $this->movementModel->insert([
                'tenant_id'     => $tenantId,
                'branch_id'     => $branchId,
                'product_id'    => $productId,
                'movement_type' => 'sale',
                'quantity'      => $quantity,
                'before_qty'    => $beforeQty,
                'after_qty'     => $afterQty,
                'ref_type'      => $refType,
                'ref_id'        => $refId,
                'created_by'    => $userId,
                'created_at'    => date('Y-m-d H:i:s'),
            ]);

            $db->transComplete();
            return $db->transStatus();
        } catch (\Exception $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /**
     * Return stock (hoàn kho - hủy bill)
     */
    public function returnStock(int $tenantId, int $branchId, int $productId, int $quantity, ?string $refType = null, ?int $refId = null, ?int $userId = null): bool
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than 0');
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $inventory = $this->inventoryModel
                ->where('tenant_id', $tenantId)
                ->where('branch_id', $branchId)
                ->where('product_id', $productId)
                ->first();

            $beforeQty = $inventory ? (int)$inventory['quantity'] : 0;
            $afterQty = $beforeQty + $quantity;

            if ($inventory) {
                $this->inventoryModel->update($inventory['id'], ['quantity' => $afterQty]);
            } else {
                $this->inventoryModel->insert([
                    'tenant_id' => $tenantId,
                    'branch_id' => $branchId,
                    'product_id' => $productId,
                    'quantity' => $afterQty,
                ]);
            }

            $this->movementModel->insert([
                'tenant_id'     => $tenantId,
                'branch_id'     => $branchId,
                'product_id'    => $productId,
                'movement_type' => 'return',
                'quantity'      => $quantity,
                'before_qty'    => $beforeQty,
                'after_qty'     => $afterQty,
                'ref_type'      => $refType,
                'ref_id'        => $refId,
                'created_by'    => $userId,
                'created_at'    => date('Y-m-d H:i:s'),
            ]);

            $db->transComplete();
            return $db->transStatus();
        } catch (\Exception $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /**
     * Adjust stock (điều chỉnh kho)
     */
    public function adjustStock(int $tenantId, int $branchId, int $productId, int $newQuantity, ?int $userId = null, ?string $note = null): bool
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $inventory = $this->inventoryModel
                ->where('tenant_id', $tenantId)
                ->where('branch_id', $branchId)
                ->where('product_id', $productId)
                ->first();

            $beforeQty = $inventory ? (int)$inventory['quantity'] : 0;
            $afterQty = $newQuantity;

            if ($inventory) {
                $this->inventoryModel->update($inventory['id'], ['quantity' => $afterQty]);
            } else {
                $this->inventoryModel->insert([
                    'tenant_id' => $tenantId,
                    'branch_id' => $branchId,
                    'product_id' => $productId,
                    'quantity' => $afterQty,
                ]);
            }

            $this->movementModel->insert([
                'tenant_id'     => $tenantId,
                'branch_id'     => $branchId,
                'product_id'    => $productId,
                'movement_type' => 'adjust',
                'quantity'      => $afterQty - $beforeQty,
                'before_qty'    => $beforeQty,
                'after_qty'     => $afterQty,
                'note'          => $note,
                'created_by'    => $userId,
                'created_at'    => date('Y-m-d H:i:s'),
            ]);

            $db->transComplete();
            return $db->transStatus();
        } catch (\Exception $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /**
     * Reverse sale when cancel order
     */
    public function reverseSaleStock(int $tenantId, int $branchId, int $productId, int $quantity, ?int $userId = null): bool
    {
        return $this->returnStock($tenantId, $branchId, $productId, $quantity, 'pos_order', null, $userId);
    }
}
