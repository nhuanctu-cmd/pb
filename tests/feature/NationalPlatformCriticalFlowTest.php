<?php

namespace Tests\Feature;

use App\Services\BookingService;
use App\Services\UnifiedMatchService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

class NationalPlatformCriticalFlowTest extends CIUnitTestCase
{
    private $testDb;
    private int $tenantId = 1;
    private ?int $bookingId = null;
    private ?int $matchId = null;
    private ?int $customerId = null;
    private array $playerIds = [];
    private array $ratingProfiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->testDb = Database::connect();
        $this->ensureCustomerSchema();
        $players = $this->testDb->table('players')->where('tenant_id', $this->tenantId)->where('deleted_at', null)->orderBy('id', 'ASC')->limit(2)->get()->getResult();
        $this->assertCount(2, $players, 'Critical flow needs two players in the test tenant.');
        $this->playerIds = [(int) $players[0]->id, (int) $players[1]->id];
        $this->ratingProfiles = $this->testDb->table('player_rating_profiles')->where('tenant_id', $this->tenantId)->whereIn('player_id', $this->playerIds)->get()->getResultArray();
    }

    protected function tearDown(): void
    {
        if ($this->matchId) {
            foreach (['rating_reliability_snapshots', 'rating_transactions', 'ranking_point_ledgers'] as $table) {
                if ($this->testDb->tableExists($table) && $this->testDb->fieldExists('match_id', $table)) {
                    $this->testDb->table($table)->where('match_id', $this->matchId)->delete();
                }
            }
            foreach (['match_games', 'match_result_versions', 'match_results', 'match_participants', 'match_sides'] as $table) {
                if ($this->testDb->tableExists($table) && $this->testDb->fieldExists('match_id', $table)) {
                    $this->testDb->table($table)->where('match_id', $this->matchId)->delete();
                }
            }
            if ($this->testDb->tableExists('data_provenance_records')) $this->testDb->table('data_provenance_records')->where('entity_type', 'MATCH_RESULT_VERSION')->where('source_id', (string) $this->matchId)->delete();
            $this->testDb->table('matches')->where('id', $this->matchId)->delete();
        }
        foreach ($this->ratingProfiles as $profile) {
            $id = (int) $profile['id'];
            unset($profile['id']);
            $this->testDb->table('player_rating_profiles')->where('id', $id)->update($profile);
        }
        if ($this->customerId) {
            $this->testDb->table('customer_timeline_events')->where('customer_id', $this->customerId)->delete();
            $this->testDb->table('customers')->where('id', $this->customerId)->delete();
        }
        if ($this->bookingId) {
            foreach (['booking_qr_codes', 'booking_items', 'booking_logs'] as $table) {
                if ($this->testDb->tableExists($table)) $this->testDb->table($table)->where('booking_id', $this->bookingId)->delete();
            }
            $this->testDb->table('bookings')->where('id', $this->bookingId)->delete();
        }
        parent::tearDown();
    }

    public function testVenueBookingCustomerMatchRatingRankingLineage(): void
    {
        $branch = $this->testDb->table('branches')->where('tenant_id', $this->tenantId)->where('deleted_at', null)->orderBy('id', 'ASC')->get(1)->getRow();
        $this->assertNotNull($branch);
        $bookingDate = '2099-12-31';
        $court = null;
        foreach ($this->testDb->table('courts')->where('tenant_id', $this->tenantId)->where('branch_id', $branch->id)->where('status', 'available')->where('deleted_at', null)->orderBy('id', 'ASC')->get()->getResult() as $candidate) {
            $occupied = $this->testDb->table('booking_items bi')
                ->join('bookings b', 'b.id = bi.booking_id')
                ->where('bi.court_id', (int) $candidate->id)
                ->where('b.booking_date', $bookingDate)
                ->where('b.deleted_at', null)
                ->whereIn('b.status', ['draft', 'pending', 'hold', 'reserved', 'paid', 'checked_in', 'in_progress'])
                ->where('bi.start_time <', '11:00:00')
                ->where('bi.end_time >', '10:00:00')
                ->countAllResults();
            if ($occupied === 0) {
                $court = $candidate;
                break;
            }
        }
        $this->assertNotNull($court);

        $bookingResult = (new BookingService())->createBooking([
            'tenant_id' => $this->tenantId,
            'branch_id' => (int) $branch->id,
            'customer_name' => 'Acceptance Flow Guest',
            'customer_phone' => '098' . random_int(1000000, 9999999),
            'customer_email' => 'acceptance-' . bin2hex(random_bytes(3)) . '@example.test',
            'booking_date' => $bookingDate,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'source' => 'admin',
            'status' => 'reserved',
            'created_by' => 1,
            'items' => [['court_id' => (int) $court->id, 'start_time' => '10:00:00', 'end_time' => '11:00:00']],
        ]);
        if (! empty($bookingResult['booking'])) {
            $this->bookingId = (int) ($bookingResult['booking']->id ?? 0);
            $this->customerId = (int) ($bookingResult['booking']->customer_id ?? 0);
        }
        $this->assertTrue($bookingResult['success'], json_encode($bookingResult, JSON_UNESCAPED_UNICODE));
        $this->assertGreaterThan(0, $this->customerId);
        $this->assertSame(1, (int) $this->testDb->table('customer_timeline_events')->where('customer_id', $this->customerId)->where('source_id', $this->bookingId)->countAllResults());

        $matches = new UnifiedMatchService();
        $created = $matches->create(['source_type' => 'friendly', 'discipline' => 'singles', 'participants' => [['player_id' => $this->playerIds[0], 'side' => 1], ['player_id' => $this->playerIds[1], 'side' => 2]]], $this->tenantId, 1);
        $this->assertTrue($created['success']);
        $this->matchId = (int) $created['match']['match']->id;
        $this->assertTrue($matches->submitResult($this->matchId, ['winner_side' => 1, 'games' => [['side_a_score' => 11, 'side_b_score' => 8]]], $this->tenantId, 1)['success']);
        $this->assertTrue($matches->confirmResult($this->matchId, $this->tenantId, 1)['success']);
        $published = $matches->publishOfficial($this->matchId, $this->tenantId, 1);
        $this->assertTrue($published['success']);
        $this->assertTrue($published['network']['rating_engine_v1']['success']);
        $this->assertTrue($published['network']['ranking']['success']);
        $this->assertSame(2, (int) $this->testDb->table('rating_transactions')->where('match_id', $this->matchId)->countAllResults());
        $this->assertSame(2, (int) $this->testDb->table('ranking_point_ledgers')->where('match_id', $this->matchId)->countAllResults());
        $this->assertNotEmpty(service('ratingEngine')->history($this->tenantId, $this->playerIds[0], 'singles', 10));
    }

    private function ensureCustomerSchema(): void
    {
        $bookingColumns = [
            'customer_id' => 'BIGINT UNSIGNED NULL',
            'hold_until' => 'DATETIME NULL',
            'is_hold' => 'TINYINT(1) NOT NULL DEFAULT 0',
            'timeout_minutes' => 'INT NOT NULL DEFAULT 15',
            'auto_release_at' => 'DATETIME NULL',
            'discount_amount' => 'DECIMAL(12,2) NOT NULL DEFAULT 0',
            'net_amount' => 'DECIMAL(12,2) NOT NULL DEFAULT 0',
            'pricing_rule_id' => 'INT UNSIGNED NULL',
            'price_breakdown' => 'JSON NULL',
            'is_recurring' => 'TINYINT(1) NOT NULL DEFAULT 0',
            'recurring_pattern' => 'JSON NULL',
            'recurring_parent_id' => 'INT UNSIGNED NULL',
        ];
        foreach ($bookingColumns as $column => $definition) {
            if (! $this->testDb->fieldExists($column, 'bookings')) {
                $this->testDb->query("ALTER TABLE bookings ADD COLUMN {$column} {$definition}");
            }
        }
        foreach ([
            'base_price' => 'DECIMAL(12,2) NOT NULL DEFAULT 0',
            'dynamic_price' => 'DECIMAL(12,2) NOT NULL DEFAULT 0',
            'pricing_detail' => 'JSON NULL',
        ] as $column => $definition) {
            if (! $this->testDb->fieldExists($column, 'booking_items')) {
                $this->testDb->query("ALTER TABLE booking_items ADD COLUMN {$column} {$definition}");
            }
        }
        $this->testDb->query("CREATE TABLE IF NOT EXISTS customers (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id INT UNSIGNED NOT NULL, player_id INT UNSIGNED NULL, full_name VARCHAR(255) NOT NULL, phone VARCHAR(30) NULL, email VARCHAR(255) NULL, status VARCHAR(20) NOT NULL DEFAULT 'active', source VARCHAR(30) NOT NULL DEFAULT 'booking', first_seen_at DATETIME NULL, last_seen_at DATETIME NULL, last_booking_at DATETIME NULL, last_visit_at DATETIME NULL, total_bookings INT UNSIGNED NOT NULL DEFAULT 0, completed_bookings INT UNSIGNED NOT NULL DEFAULT 0, no_show_count INT UNSIGNED NOT NULL DEFAULT 0, total_spend DECIMAL(14,2) NOT NULL DEFAULT 0, favorite_court_id INT UNSIGNED NULL, metadata JSON NULL, created_by INT UNSIGNED NULL, updated_by INT UNSIGNED NULL, created_at DATETIME NULL, updated_at DATETIME NULL, deleted_at DATETIME NULL) ENGINE=InnoDB");
        $this->testDb->query("CREATE TABLE IF NOT EXISTS customer_timeline_events (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id INT UNSIGNED NOT NULL, customer_id BIGINT UNSIGNED NOT NULL, event_type VARCHAR(80) NOT NULL, title VARCHAR(255) NOT NULL, description TEXT NULL, source_type VARCHAR(50) NULL, source_id BIGINT UNSIGNED NULL, actor_id INT UNSIGNED NULL, payload JSON NULL, created_at DATETIME NULL) ENGINE=InnoDB");
    }
}
