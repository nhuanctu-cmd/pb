<?php

namespace App\Services;

use App\Models\PosOrderModel;
use App\Models\PosOrderItemModel;
use App\Models\ProductModel;
use App\Models\BookingModel;
use App\Models\PlayerModel;
use App\Models\ProductCategoryModel;
use App\Entities\PosOrder;
use App\Entities\PosOrderItem;
use CodeIgniter\Database\Exceptions\DatabaseException;

class PosService
{
    protected PosOrderModel $orderModel;
    protected PosOrderItemModel $itemModel;
    protected ProductModel $productModel;
    protected BookingModel $bookingModel;
    protected PlayerModel $playerModel;
    protected ProductCategoryModel $categoryModel;
    protected InventoryService $inventoryService;

    private ?array $currentOrder = null;

    public function __construct()
    {
        $this->orderModel = new PosOrderModel();
        $this->itemModel = new PosOrderItemModel();
        $this->productModel = new ProductModel();
        $this->bookingModel = new BookingModel();
        $this->playerModel = new PlayerModel();
        $this->categoryModel = new ProductCategoryModel();
        $this->inventoryService = new InventoryService();
    }

    /**
     * Create new order
     */
    public function createOrder(int $tenantId, int $branchId, ?int $userId = null): array
    {
        $orderCode = $this->generateOrderCode($tenantId, $branchId);

        $orderData = [
            'tenant_id'     => $tenantId,
            'branch_id'     => $branchId,
            'order_code'    => $orderCode,
            'total_amount'  => 0,
            'discount_amount' => 0,
            'paid_amount'   => 0,
            'payment_status'=> 'pending',
            'status'        => 'pending',
            'created_by'    => $userId,
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ];

        $orderId = $this->orderModel->insert($orderData);
        $this->currentOrder = $this->orderModel->find($orderId);

        return $this->currentOrder;
    }

    /**
     * Get current order
     */
    public function getCurrentOrder(): ?array
    {
        return $this->currentOrder;
    }

    /**
     * Load existing order
     */
    public function loadOrder(int $orderId): ?array
    {
        $this->currentOrder = $this->orderModel->find($orderId);
        return $this->currentOrder;
    }

    /**
     * Add item to order
     */
    public function addItem(int $tenantId, int $productId, int $quantity = 1): bool
    {
        if (!$this->currentOrder) {
            throw new \InvalidArgumentException('No active order. Create order first.');
        }

        $product = $this->productModel->find($productId);
        if (!$product || $product['status'] !== 'active') {
            throw new \InvalidArgumentException('Product not found or inactive');
        }

        // Check stock if allow_negative_stock is false
        if (!$this->isNegativeStockAllowed($tenantId)) {
            $hasStock = $this->inventoryService->checkStock(
                $tenantId,
                $this->currentOrder['branch_id'],
                $productId,
                $quantity
            );
            if (!$hasStock) {
                throw new DatabaseException('Insufficient stock for product: ' . $product['name_vi']);
            }
        }

        // Check if item already exists in order
        $existingItem = $this->itemModel
            ->where('order_id', $this->currentOrder['id'])
            ->where('product_id', $productId)
            ->first();

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            if ($existingItem) {
                $newQty = $existingItem['quantity'] + $quantity;
                $total = $newQty * $product['sale_price'];

                $this->itemModel->update($existingItem['id'], [
                    'quantity' => $newQty,
                    'total' => $total,
                ]);
            } else {
                $this->itemModel->insert([
                    'tenant_id'  => $tenantId,
                    'order_id'   => $this->currentOrder['id'],
                    'product_id' => $productId,
                    'quantity'   => $quantity,
                    'price'      => $product['sale_price'],
                    'total'      => $quantity * $product['sale_price'],
                ]);
            }

            // Recalculate order totals
            $this->recalculateOrder();

            $db->transComplete();
            return $db->transStatus();
        } catch (\Exception $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /**
     * Remove item from order
     */
    public function removeItem(int $itemId): bool
    {
        if (!$this->currentOrder) {
            throw new \InvalidArgumentException('No active order');
        }

        $item = $this->itemModel->find($itemId);
        if (!$item || $item['order_id'] != $this->currentOrder['id']) {
            throw new \InvalidArgumentException('Item not found in current order');
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $this->itemModel->delete($itemId);
            $this->recalculateOrder();

            $db->transComplete();
            return $db->transStatus();
        } catch (\Exception $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /**
     * Update item quantity
     */
    public function updateItemQuantity(int $itemId, int $newQuantity): bool
    {
        if (!$this->currentOrder) {
            throw new \InvalidArgumentException('No active order');
        }

        if ($newQuantity <= 0) {
            return $this->removeItem($itemId);
        }

        $item = $this->itemModel->find($itemId);
        if (!$item || $item['order_id'] != $this->currentOrder['id']) {
            throw new \InvalidArgumentException('Item not found in current order');
        }

        // Check stock
        $product = $this->productModel->find($item['product_id']);

        if (!$this->isNegativeStockAllowed($this->currentOrder['tenant_id'])) {
            // For update, we check if the additional quantity needed is available
            $currentInOrder = $item['quantity'];
            $additionalNeeded = $newQuantity - $currentInOrder;

            if ($additionalNeeded > 0) {
                $hasStock = $this->inventoryService->checkStock(
                    $this->currentOrder['tenant_id'],
                    $this->currentOrder['branch_id'],
                    $item['product_id'],
                    $additionalNeeded
                );
                if (!$hasStock) {
                    throw new DatabaseException('Insufficient stock. Available: ' .
                        $this->inventoryService->getStock(
                            $this->currentOrder['tenant_id'],
                            $this->currentOrder['branch_id'],
                            $item['product_id']
                        )
                    );
                }
            }
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $total = $newQuantity * $item['price'];
            $this->itemModel->update($itemId, [
                'quantity' => $newQuantity,
                'total' => $total,
            ]);

            $this->recalculateOrder();

            $db->transComplete();
            return $db->transStatus();
        } catch (\Exception $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /**
     * Checkout order (thanh toán)
     */
    public function checkout(int $paidAmount, ?string $note = null): bool
    {
        if (!$this->currentOrder) {
            throw new \InvalidArgumentException('No active order');
        }

        if ($this->currentOrder['status'] !== 'pending') {
            throw new \InvalidArgumentException('Order already processed');
        }

        $items = $this->itemModel->where('order_id', $this->currentOrder['id'])->findAll();
        if (empty($items)) {
            throw new \InvalidArgumentException('Order has no items');
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // Deduct stock for each item
            foreach ($items as $item) {
                $movementRefId = $this->currentOrder['id'];
                $this->inventoryService->saleStock(
                    $this->currentOrder['tenant_id'],
                    $this->currentOrder['branch_id'],
                    $item['product_id'],
                    $item['quantity'],
                    'pos_order',
                    $movementRefId,
                    $this->currentOrder['created_by']
                );
            }

            // Update order status
            $this->orderModel->update($this->currentOrder['id'], [
                'paid_amount'   => $paidAmount,
                'payment_status'=> $paidAmount >= $this->currentOrder['total_amount'] ? 'paid' : 'pending',
                'status'        => 'completed',
                'note'          => $note,
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);

            $this->currentOrder = $this->orderModel->find($this->currentOrder['id']);

            $db->transComplete();
            return $db->transStatus();
        } catch (\Exception $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /**
     * Cancel order and restore stock
     */
    public function cancelOrder(int $orderId, ?string $reason = null): bool
    {
        $order = $this->orderModel->find($orderId);
        if (!$order) {
            throw new \InvalidArgumentException('Order not found');
        }

        if ($order['status'] === 'cancelled') {
            throw new \InvalidArgumentException('Order already cancelled');
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // If order was completed, restore stock
            if ($order['status'] === 'completed') {
                $items = $this->itemModel->where('order_id', $orderId)->findAll();

                foreach ($items as $item) {
                    $this->inventoryService->returnStock(
                        $order['tenant_id'],
                        $order['branch_id'],
                        $item['product_id'],
                        $item['quantity'],
                        'pos_order',
                        $orderId,
                        $order['created_by']
                    );
                }
            }

            // Update order status
            $this->orderModel->update($orderId, [
                'status'     => 'cancelled',
                'note'       => $reason,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            if ($this->currentOrder && $this->currentOrder['id'] == $orderId) {
                $this->currentOrder = $this->orderModel->find($orderId);
            }

            $db->transComplete();
            return $db->transStatus();
        } catch (\Exception $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /**
     * Attach booking to order
     */
    public function attachBooking(int $bookingId): bool
    {
        if (!$this->currentOrder) {
            throw new \InvalidArgumentException('No active order');
        }

        $booking = $this->bookingModel->find($bookingId);
        if (!$booking) {
            throw new \InvalidArgumentException('Booking not found');
        }

        $this->orderModel->update($this->currentOrder['id'], [
            'booking_id' => $bookingId,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->currentOrder['booking_id'] = $bookingId;
        return true;
    }

    /**
     * Attach player to order
     */
    public function attachPlayer(int $playerId): bool
    {
        if (!$this->currentOrder) {
            throw new \InvalidArgumentException('No active order');
        }

        $player = $this->playerModel->find($playerId);
        if (!$player) {
            throw new \InvalidArgumentException('Player not found');
        }

        $this->orderModel->update($this->currentOrder['id'], [
            'player_id' => $playerId,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->currentOrder['player_id'] = $playerId;
        return true;
    }

    /**
     * Recalculate order totals
     */
    protected function recalculateOrder(): void
    {
        if (!$this->currentOrder) {
            return;
        }

        $items = $this->itemModel->where('order_id', $this->currentOrder['id'])->findAll();
        $totalAmount = 0;

        foreach ($items as $item) {
            $totalAmount += $item['total'];
        }

        $this->orderModel->update($this->currentOrder['id'], [
            'total_amount' => $totalAmount,
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        $this->currentOrder['total_amount'] = $totalAmount;
    }

    /**
     * Generate unique order code
     */
    protected function generateOrderCode(int $tenantId, int $branchId): string
    {
        $date = date('Ymd');
        $prefix = "POS{$tenantId}{$branchId}{$date}";

        $lastOrder = $this->orderModel
            ->where('order_code LIKE', $prefix . '%')
            ->orderBy('id', 'DESC')
            ->first();

        if ($lastOrder) {
            $lastNumber = (int)substr($lastOrder['order_code'], -6);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Check if negative stock is allowed for tenant
     */
    protected function isNegativeStockAllowed(int $tenantId): bool
    {
        // TODO: Check tenant setting
        // For now, default to false
        return false;
    }

    /**
     * Get order items with details
     */
    public function getOrderItems(int $orderId): array
    {
        return $this->itemModel->getByOrder($orderId);
    }

    /**
     * Get all categories with products and stock
     */
    public function getCategoriesWithProducts(int $tenantId, int $branchId): array
    {
        $categories = $this->categoryModel->getByTenant($tenantId);
        $result = [];

        foreach ($categories as $category) {
            $products = $this->productModel
                ->select('products.*, COALESCE(SUM(inventories.quantity), 0) as stock')
                ->join('inventories', 'inventories.product_id = products.id', 'left')
                ->where('products.tenant_id', $tenantId)
                ->where('products.category_id', $category['id'])
                ->where('products.status', 'active')
                ->where('inventories.branch_id', $branchId)
                ->groupBy('products.id')
                ->findAll();

            if (!empty($products)) {
                $category['products'] = $products;
                $result[] = $category;
            }
        }

        return $result;
    }

    /**
     * Search products by keyword
     */
    public function searchProducts(int $tenantId, int $branchId, string $keyword): array
    {
        return $this->productModel
            ->select('products.*, COALESCE(SUM(inventories.quantity), 0) as stock')
            ->join('inventories', 'inventories.product_id = products.id', 'left')
            ->where('products.tenant_id', $tenantId)
            ->where('products.status', 'active')
            ->groupStart()
                ->like('products.name_vi', $keyword)
                ->orLike('products.name_en', $keyword)
                ->orLike('products.sku', $keyword)
            ->groupEnd()
            ->where('inventories.branch_id', $branchId)
            ->groupBy('products.id')
            ->findAll();
    }
}
