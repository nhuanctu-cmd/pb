<?php

namespace Tests\Unit;

use App\Services\PosService;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;

class PosServiceTest extends CIUnitTestCase
{
    private PosService $service;
    private ?int $orderId = null;
    private int $tenantId = 1;
    private int $branchId = 1;
    private int $productId = 1;
    private int $initialStock = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PosService();
        $db = \Config\Database::connect();
        $this->initialStock = (int) ($db->table('inventories')
            ->where('tenant_id', $this->tenantId)->where('branch_id', $this->branchId)
            ->where('product_id', $this->productId)->get()->getRow('quantity') ?? 0);
    }

    protected function tearDown(): void
    {
        if ($this->orderId !== null) {
            $db = \Config\Database::connect();
            $db->table('inventory_movements')->where('ref_type', 'pos_order')->where('ref_id', $this->orderId)->delete();
            $db->table('pos_order_items')->where('order_id', $this->orderId)->delete();
            $db->table('pos_orders')->where('id', $this->orderId)->delete();
            $db->table('inventories')->where('tenant_id', $this->tenantId)
                ->where('branch_id', $this->branchId)->where('product_id', $this->productId)
                ->update(['quantity' => $this->initialStock]);
        }
        parent::tearDown();
    }

    public function testCheckoutDeductsStockAndCannotBeRepeated(): void
    {
        $order = $this->service->createOrder($this->tenantId, $this->branchId, 2);
        $this->orderId = (int) $order['id'];

        // Ensure a product and inventory exist for tenant/branch 1 before adding
        $db = \Config\Database::connect();
        $product = $db->table('products')->where('tenant_id', $this->tenantId)->get()->getRowArray();
        if (! $product) {
            $catId = $db->table('product_categories')->insert([
                'tenant_id' => $this->tenantId,
                'name_vi' => 'Demo',
                'name_en' => 'Demo',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $db->table('products')->insert([
                'tenant_id' => $this->tenantId,
                'category_id' => $catId,
                'name_vi' => 'Nước suối Demo',
                'sku' => 'DEMO001',
                'unit' => 'chai',
                'cost_price' => 3000,
                'sale_price' => 5000,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $this->productId = (int) $db->insertID();
            $db->table('inventories')->insert([
                'tenant_id' => $this->tenantId,
                'branch_id' => $this->branchId,
                'product_id' => $this->productId,
                'quantity' => 100,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $this->initialStock = 100;
        } else {
            $this->productId = (int) $product['id'];
            $inv = $db->table('inventories')
                ->where('tenant_id', $this->tenantId)
                ->where('branch_id', $this->branchId)
                ->where('product_id', $this->productId)
                ->get()->getRowArray();
            if (! $inv) {
                $db->table('inventories')->insert([
                    'tenant_id' => $this->tenantId,
                    'branch_id' => $this->branchId,
                    'product_id' => $this->productId,
                    'quantity' => 100,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $this->initialStock = 100;
            } else {
                $this->initialStock = (int) $inv['quantity'];
            }
        }

        $this->assertTrue($this->service->addItem($this->tenantId, $this->productId, 2));
        $this->assertTrue($this->service->checkout(100000));

        $saved = $this->service->loadOrder($this->orderId, $this->tenantId);
        $this->assertSame('completed', $saved['status']);
        $stock = (int) \Config\Database::connect()->table('inventories')
            ->where('tenant_id', $this->tenantId)
            ->where('branch_id', $this->branchId)
            ->where('product_id', $this->productId)
            ->get()->getRow('quantity');
        $this->assertSame($this->initialStock - 2, $stock);

        $this->expectException(InvalidArgumentException::class);
        $this->service->checkout((float) $saved['total_amount']);
    }
}
