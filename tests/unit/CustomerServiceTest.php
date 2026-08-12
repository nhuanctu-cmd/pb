<?php

namespace Tests\Unit;

use App\Services\CustomerService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

class CustomerServiceTest extends CIUnitTestCase
{
    private int $tenantId = 0;
    private array $createdCustomerIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $db = Database::connect();
        $tenant = $db->table('tenants')->orderBy('id', 'ASC')->get(1)->getRow();
        $this->assertNotNull($tenant, 'Test database needs a tenant.');
        $this->tenantId = (int) $tenant->id;
        if ($db->tableExists('bookings') && ! $db->fieldExists('customer_id', 'bookings')) {
            $db->query('ALTER TABLE bookings ADD COLUMN customer_id BIGINT UNSIGNED NULL AFTER player_id');
            $db->query('ALTER TABLE bookings ADD INDEX idx_test_bookings_customer (tenant_id, customer_id)');
        }
        $db->query("CREATE TABLE IF NOT EXISTS customers (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id INT UNSIGNED NOT NULL, player_id INT UNSIGNED NULL, full_name VARCHAR(255) NOT NULL, phone VARCHAR(30) NULL, email VARCHAR(255) NULL, status VARCHAR(20) NOT NULL DEFAULT 'active', source VARCHAR(30) NOT NULL DEFAULT 'booking', first_seen_at DATETIME NULL, last_seen_at DATETIME NULL, last_booking_at DATETIME NULL, last_visit_at DATETIME NULL, total_bookings INT UNSIGNED NOT NULL DEFAULT 0, completed_bookings INT UNSIGNED NOT NULL DEFAULT 0, no_show_count INT UNSIGNED NOT NULL DEFAULT 0, total_spend DECIMAL(14,2) NOT NULL DEFAULT 0, favorite_court_id INT UNSIGNED NULL, metadata JSON NULL, created_by INT UNSIGNED NULL, updated_by INT UNSIGNED NULL, created_at DATETIME NULL, updated_at DATETIME NULL, deleted_at DATETIME NULL, KEY idx_customer_tenant_phone (tenant_id, phone), KEY idx_customer_tenant_email (tenant_id, email)) ENGINE=InnoDB");
        $db->query("CREATE TABLE IF NOT EXISTS customer_timeline_events (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id INT UNSIGNED NOT NULL, customer_id BIGINT UNSIGNED NOT NULL, event_type VARCHAR(80) NOT NULL, title VARCHAR(255) NOT NULL, description TEXT NULL, source_type VARCHAR(50) NULL, source_id BIGINT UNSIGNED NULL, actor_id INT UNSIGNED NULL, payload JSON NULL, created_at DATETIME NULL, KEY idx_customer_timeline (tenant_id, customer_id, created_at)) ENGINE=InnoDB");
    }

    protected function tearDown(): void
    {
        $db = Database::connect();
        foreach ($this->createdCustomerIds as $id) {
            $db->table('customer_timeline_events')->where('customer_id', $id)->delete();
            $db->table('customers')->where('id', $id)->delete();
        }
        parent::tearDown();
    }

    public function testBookingContactIsResolvedToOneCustomerAndTimelineIsRecorded(): void
    {
        $service = new CustomerService();
        $payload = ['customer_name' => 'CRM Test Guest', 'customer_phone' => '090' . random_int(1000000, 9999999), 'customer_email' => 'crm-' . bin2hex(random_bytes(3)) . '@example.test'];
        $first = $service->resolveForBooking($this->tenantId, $payload, 1);
        $this->assertTrue($first['success']);
        $this->assertGreaterThan(0, $first['customer_id']);
        $this->createdCustomerIds[] = (int) $first['customer_id'];

        $second = $service->resolveForBooking($this->tenantId, $payload, 1);
        $this->assertSame($first['customer_id'], $second['customer_id']);
        $this->assertTrue($service->recordBooking((int) $first['customer_id'], $this->tenantId, 987, ['total_amount' => 150000], 1));

        $db = Database::connect();
        $customer = $db->table('customers')->where('id', $first['customer_id'])->get()->getRow();
        $timeline = $db->table('customer_timeline_events')->where('customer_id', $first['customer_id'])->get()->getResult();
        $this->assertSame(1, (int) $customer->total_bookings);
        $this->assertSame(150000.0, (float) $customer->total_spend);
        $this->assertCount(1, $timeline);
        $this->assertSame('booking_created', $timeline[0]->event_type);
    }
}
