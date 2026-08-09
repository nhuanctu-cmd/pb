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
        $this->assertTrue($this->service->addItem($this->tenantId, $this->productId, 2));
        $this->assertTrue($this->service->checkout(100000));

        $saved = $this->service->loadOrder($this->orderId, $this->tenantId);
        $this->assertSame('completed', $saved['status']);
        $stock = (int) \Config\Database::connect()->table('inventories')
            ->where('tenant_id', 1)->where('branch_id', 1)->where('product_id', 1)
            ->get()->getRow('quantity');
        $this->assertSame($this->initialStock - 2, $stock);

        $this->expectException(InvalidArgumentException::class);
        $this->service->checkout((float) $saved['total_amount']);
    }
}
