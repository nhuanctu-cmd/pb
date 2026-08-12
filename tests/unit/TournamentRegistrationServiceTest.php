<?php

namespace Tests\Unit;

use App\Services\TournamentRegistrationService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

final class TournamentRegistrationServiceTest extends CIUnitTestCase
{
    private array $created = [];
    private array $createdExtra = [];

    protected function setUp(): void
    {
        parent::setUp();
        $db = Database::connect();
        $branch = $db->table('branches')->where('tenant_id', 1)->where('deleted_at', null)->orderBy('id')->get(1)->getRow();
        $players = $db->table('players')->where('tenant_id', 1)->where('status', 'active')->where('deleted_at', null)->orderBy('id')->get(2)->getResult();

        if (! $branch || count($players) < 2) {
            $this->markTestSkipped('Database test cần branch và tối thiểu 2 player active cho tenant 1.');
        }

        $this->created['playerA'] = (int) $players[0]->id;
        $this->created['playerB'] = (int) $players[1]->id;
        $slug = 'test-reg-' . bin2hex(random_bytes(5));

        $db->table('tournaments')->insert([
            'tenant_id' => 1,
            'branch_id' => (int) $branch->id,
            'name_vi' => 'Test Registration Tournament',
            'name_en' => 'Test Registration Tournament',
            'slug_vi' => $slug,
            'slug_en' => $slug,
            'status' => 'open',
            'start_date' => date('Y-m-d', strtotime('+3 days')),
            'end_date' => date('Y-m-d', strtotime('+4 days')),
            'registration_start' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'registration_end' => date('Y-m-d H:i:s', strtotime('+1 day')),
            'max_teams' => 1,
            'registration_fee' => 90000,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->created['tournament'] = (int) $db->insertID();

        $db->table('tournament_categories')->insert([
            'tenant_id' => 1,
            'tournament_id' => $this->created['tournament'],
            'name_vi' => 'Singles',
            'name_en' => 'Singles',
            'category_type' => 'single_male',
            'max_teams' => 1,
            'registration_fee' => 90000,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->created['category'] = (int) $db->insertID();
    }

    protected function tearDown(): void
    {
        $db = Database::connect();

        $this->cleanupTournament((int) ($this->createdExtra['tournament'] ?? 0));
        $this->cleanupTournament((int) ($this->created['tournament'] ?? 0));

        parent::tearDown();
    }

    private function cleanupTournament(int $tournamentId): void
    {
        if (! $tournamentId) {
            return;
        }

        $db = Database::connect();
        $registrationIds = array_column($db->table('tournament_registrations')->select('id')->where('tenant_id', 1)->where('tournament_id', $tournamentId)->get()->getResultArray(), 'id');

        $db->query('SET FOREIGN_KEY_CHECKS = 0');
        foreach (['tournament_registrations', 'tournament_categories', 'tournament_matches', 'tournament_schedule_locks', 'tournament_brackets', 'tournament_rules', 'tournament_sponsors'] as $table) {
            if ($db->tableExists($table)) {
                $db->table($table)->where('tournament_id', $tournamentId)->where('tenant_id', 1)->delete();
            }
        }

        if (! empty($registrationIds) && $db->tableExists('invoices')) {
            $db->table('invoices')->where('tenant_id', 1)->where('ref_type', 'tournament_registration')->whereIn('ref_id', $registrationIds)->delete();
        }

        $db->table('tournaments')->where('id', $tournamentId)->where('tenant_id', 1)->delete();
        $db->query('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function testAdminQuickApprovePassesAndApprovesRegistration(): void
    {
        $service = new TournamentRegistrationService();
        $result = $service->registerAdmin([
            'tenant_id' => 1,
            'tournament_id' => $this->created['tournament'],
            'category_id' => $this->created['category'],
            'player_id' => $this->created['playerA'],
            'contact_name' => 'Auto Test A',
            'contact_phone' => '0900111222',
            'payment_status' => 'paid',
            'quick_approve' => true,
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('approved', $result['registration']->approval_status);
        $this->assertSame('confirmed', $result['registration']->registration_status);
        $this->assertTrue($result['invoice']['success'] ?? false);

        $db = Database::connect();
        $invoice = $db->table('invoices')
            ->where('tenant_id', 1)
            ->where('ref_type', 'tournament_registration')
            ->where('ref_id', (int) $result['registration']->id)
            ->where('status', 'unpaid')
            ->get()->getRow();

        $this->assertNotNull($invoice);
        $this->assertSame((int) $result['registration']->id, (int) $invoice->ref_id);
    }

    public function testAdminWaitlistWhenCategoryCapacityReached(): void
    {
        $service = new TournamentRegistrationService();
        $service->registerAdmin([
            'tenant_id' => 1,
            'tournament_id' => $this->created['tournament'],
            'category_id' => $this->created['category'],
            'player_id' => $this->created['playerA'],
            'contact_name' => 'Auto Test A',
            'contact_phone' => '0900111222',
            'payment_status' => 'paid',
            'quick_approve' => true,
        ]);

        $second = $service->registerAdmin([
            'tenant_id' => 1,
            'tournament_id' => $this->created['tournament'],
            'category_id' => $this->created['category'],
            'player_id' => $this->created['playerB'],
            'contact_name' => 'Auto Test B',
            'contact_phone' => '0900111333',
            'payment_status' => 'paid',
            'quick_approve' => true,
        ]);

        $this->assertTrue($second['success']);
        $this->assertSame('pending', $second['registration']->approval_status);
        $this->assertSame('waitlisted', $second['registration']->registration_status);
        $this->assertTrue($second['invoice']['success'] ?? false);
    }

    public function testRegistrationInvoiceIdempotentForSameRegistration(): void
    {
        $service = new TournamentRegistrationService();
        $first = $service->registerAdmin([
            'tenant_id' => 1,
            'tournament_id' => $this->created['tournament'],
            'category_id' => $this->created['category'],
            'player_id' => $this->created['playerA'],
            'contact_name' => 'Idempotent Test A',
            'contact_phone' => '0900666777',
            'payment_status' => 'paid',
            'quick_approve' => true,
        ]);

        $firstInvoiceId = (int) ($first['invoice']['invoice_id'] ?? 0);
        $registrationId = (int) $first['registration']->id;

        $again = $service->createRegistrationInvoice($registrationId, 1);
        $this->assertTrue($again['success']);
        $this->assertSame($firstInvoiceId, (int) ($again['invoice_id'] ?? 0));
        $this->assertSame('Invoice đã tồn tại cho hồ sơ đăng ký này.', (string) $again['message']);
    }

    public function testFreeRegistrationSkipsInvoiceCreation(): void
    {
        $service = new TournamentRegistrationService();
        $free = $this->seedTournamentAndCategory('Zero Fee Registration Tournament', 0);
        $this->createdExtra['tournament'] = $free['tournament'];

        $result = $service->registerAdmin([
            'tenant_id' => 1,
            'tournament_id' => $free['tournament'],
            'category_id' => $free['category'],
            'player_id' => $this->created['playerB'],
            'contact_name' => 'Free Fee',
            'contact_phone' => '0900333444',
            'payment_status' => 'paid',
            'quick_approve' => true,
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('Không cần tạo invoice cho đăng ký miễn phí.', (string) $result['invoice']['message']);
        $this->assertSame(0.0, (float) $result['invoice']['amount']);
        $this->assertNull($result['invoice']['invoice_code'] ?? null);
        $this->assertNull($result['invoice']['invoice_id'] ?? null);
    }

    public function testAdminRejectsInvalidPartnerPlayer(): void
    {
        $service = new TournamentRegistrationService();
        $result = $service->registerAdmin([
            'tenant_id' => 1,
            'tournament_id' => $this->created['tournament'],
            'category_id' => $this->created['category'],
            'player_id' => $this->created['playerA'],
            'partner_player_id' => 999999,
            'contact_name' => 'Auto Test A',
            'contact_phone' => '0900111222',
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('không thuộc tenant', (string) $result['message']);
    }

    private function seedTournamentAndCategory(string $name, float $registrationFee): array
    {
        $db = Database::connect();
        $branch = $db->table('branches')->where('tenant_id', 1)->where('deleted_at', null)->orderBy('id')->get(1)->getRow();

        if (! $branch) {
            $this->markTestSkipped('Cần có branch cho tenant 1 để seed giải test miễn phí.');
        }

        $db->table('tournaments')->insert([
            'tenant_id' => 1,
            'branch_id' => (int) $branch->id,
            'name_vi' => $name,
            'name_en' => $name,
            'slug_vi' => 'free-reg-' . bin2hex(random_bytes(4)),
            'slug_en' => 'free-reg-' . bin2hex(random_bytes(4)),
            'status' => 'open',
            'start_date' => date('Y-m-d', strtotime('+10 days')),
            'end_date' => date('Y-m-d', strtotime('+11 days')),
            'registration_start' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'registration_end' => date('Y-m-d H:i:s', strtotime('+5 days')),
            'max_teams' => 1,
            'registration_fee' => $registrationFee,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $tournamentId = (int) $db->insertID();

        $db->table('tournament_categories')->insert([
            'tenant_id' => 1,
            'tournament_id' => $tournamentId,
            'name_vi' => 'Singles',
            'name_en' => 'Singles',
            'category_type' => 'single_male',
            'max_teams' => 1,
            'registration_fee' => $registrationFee,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return ['tournament' => $tournamentId, 'category' => (int) $db->insertID()];
    }
}
