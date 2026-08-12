<?php

namespace Tests\Unit;

use App\Services\CrmCampaignService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

final class CrmCampaignServiceTest extends CIUnitTestCase
{
    private array $created = [];

    protected function tearDown(): void
    {
        $db = Database::connect();
        if (! empty($this->created)) {
            foreach (['tournament_registrations', 'tournament_categories', 'tournaments', 'customers', 'crm_campaign_recipients', 'crm_campaigns', 'players'] as $table) {
                if (! $db->tableExists($table)) {
                    continue;
                }

                if ($table === 'crm_campaign_recipients' && ! empty($this->created['campaign'])) {
                    $db->table($table)->where('campaign_id', (int) $this->created['campaign'])->delete();
                    continue;
                }

                if ($table === 'crm_campaigns' && ! empty($this->created['campaign'])) {
                    $db->table($table)->where('id', (int) $this->created['campaign'])->delete();
                    continue;
                }

                if (in_array($table, ['tournament_registrations', 'tournament_categories', 'tournament_matches', 'tournament_schedule_locks', 'tournament_brackets', 'tournament_rules', 'tournament_sponsors'], true) && ! empty($this->created['tournament'])) {
                    $db->table($table)->where('tenant_id', 1)->where('tournament_id', (int) $this->created['tournament'])->delete();
                    continue;
                }

                if ($table === 'tournaments' && ! empty($this->created['tournament'])) {
                    $db->table($table)->where('tenant_id', 1)->where('id', (int) $this->created['tournament'])->delete();
                    continue;
                }

                if ($table === 'customers' && ! empty($this->created['customers'])) {
                    $db->table($table)->whereIn('id', $this->created['customers'])->delete();
                }

                if ($table === 'players' && ! empty($this->created['players'])) {
                    $db->table($table)->whereIn('id', $this->created['players'])->delete();
                }
            }

            $this->created = [];
        }

        parent::tearDown();
    }

    public function testSegmentOptionsIncludeTournamentBasedSegments(): void
    {
        $options = (new CrmCampaignService())->segmentOptions();
        $this->assertArrayHasKey('recently_active', $options);
        $this->assertArrayHasKey('tournament_participants', $options);
        $this->assertArrayHasKey('manual', $options);
    }

    public function testTournamentParticipantsSegmentOnlyRecentRegistrations(): void
    {
        $db = Database::connect();
        if (! $db->tableExists('crm_campaigns')) {
            $db->query("CREATE TABLE crm_campaigns ("
                . "id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,"
                . "tenant_id INT UNSIGNED NOT NULL,"
                . "branch_id INT UNSIGNED NULL,"
                . "name VARCHAR(150) NOT NULL,"
                . "channel VARCHAR(40) NOT NULL,"
                . "segment VARCHAR(60) NOT NULL,"
                . "status VARCHAR(30) NOT NULL DEFAULT 'draft',"
                . "subject VARCHAR(200) NULL,"
                . "message TEXT NULL,"
                . "created_by INT UNSIGNED NULL,"
                . "created_at DATETIME NULL,"
                . "updated_at DATETIME NULL,"
                . "updated_by INT UNSIGNED NULL"
                . ")");
        }
        if (! $db->tableExists('crm_campaign_recipients')) {
            $db->query("CREATE TABLE crm_campaign_recipients ("
                . "id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,"
                . "tenant_id INT UNSIGNED NOT NULL,"
                . "campaign_id BIGINT UNSIGNED NOT NULL,"
                . "customer_id INT UNSIGNED NOT NULL,"
                . "channel VARCHAR(40) NOT NULL,"
                . "status VARCHAR(20) NOT NULL DEFAULT 'pending',"
                . "created_at DATETIME NULL,"
                . "updated_at DATETIME NULL,"
                . "UNIQUE KEY uniq_campaign_customer_channel (campaign_id, customer_id, channel)"
                . ")");
        }

        $branch = $db->table('branches')->where('tenant_id', 1)->where('deleted_at', null)->orderBy('id')->get(1)->getRow();
        if (! $branch) {
            $this->markTestSkipped('Cần branch và ít nhất 2 player active cho tenant 1.');
        }

        $players = [];
        for ($i = 1; $i <= 2; $i++) {
            $playerCode = 'crm-seg-' . bin2hex(random_bytes(6));
            $playerName = 'Segment Player ' . $i . ' ' . bin2hex(random_bytes(2));
            $db->table('players')->insert([
                'tenant_id' => 1,
                'user_id' => null,
                'player_code' => $playerCode,
                'full_name' => $playerName,
                'phone' => null,
                'email' => null,
                'gender' => 'other',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $playerId = (int) $db->insertID();
            $players[] = (object) ['id' => $playerId, 'full_name' => $playerName];
        }
        $this->created['players'] = array_map(static fn ($player) => (int) $player->id, $players);

        $tournament = 'segment-part-' . bin2hex(random_bytes(4));
        $db->table('tournaments')->insert([
            'tenant_id' => 1,
            'branch_id' => (int) $branch->id,
            'name_vi' => $tournament,
            'name_en' => $tournament,
            'slug_vi' => $tournament,
            'slug_en' => $tournament,
            'status' => 'open',
            'start_date' => date('Y-m-d', strtotime('+5 days')),
            'end_date' => date('Y-m-d', strtotime('+6 days')),
            'registration_start' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'registration_end' => date('Y-m-d H:i:s', strtotime('+3 days')),
            'max_teams' => 20,
            'registration_fee' => 0,
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
            'max_teams' => 20,
            'registration_fee' => 0,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $categoryId = (int) $db->insertID();

        $customerAName = 'Segment Test A ' . bin2hex(random_bytes(3));
        $customerBName = 'Segment Test B ' . bin2hex(random_bytes(3));
        $db->table('customers')->insert([
            'tenant_id' => 1,
            'player_id' => (int) $players[0]->id,
            'full_name' => $customerAName,
            'phone' => '0890' . random_int(100000, 999999),
            'status' => 'active',
            'source' => 'player',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $customerAId = (int) $db->insertID();
        $db->table('customers')->insert([
            'tenant_id' => 1,
            'player_id' => (int) $players[1]->id,
            'full_name' => $customerBName,
            'phone' => '0890' . random_int(100000, 999999),
            'status' => 'active',
            'source' => 'player',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $customerBId = (int) $db->insertID();

        $db->table('tournament_registrations')->insert([
            'tenant_id' => 1,
            'tournament_id' => $tournamentId,
            'category_id' => $categoryId,
            'player_id' => (int) $players[0]->id,
            'contact_name' => $customerAName,
            'contact_phone' => '089900011',
            'approval_status' => 'approved',
            'registration_status' => 'confirmed',
            'payment_status' => 'paid',
            'checkin_status' => 'checked_in',
            'created_at' => date('Y-m-d H:i:s', strtotime('-10 days')),
            'updated_at' => date('Y-m-d H:i:s', strtotime('-10 days')),
        ]);
        $db->table('tournament_registrations')->insert([
            'tenant_id' => 1,
            'tournament_id' => $tournamentId,
            'category_id' => $categoryId,
            'player_id' => (int) $players[1]->id,
            'contact_name' => $customerBName,
            'contact_phone' => '089900012',
            'approval_status' => 'approved',
            'registration_status' => 'confirmed',
            'payment_status' => 'paid',
            'created_at' => date('Y-m-d H:i:s', strtotime('-45 days')),
            'updated_at' => date('Y-m-d H:i:s', strtotime('-45 days')),
        ]);

        $service = new CrmCampaignService();
        $campaignId = $service->createDraft(1, [
            'name' => 'Segment test campaign',
            'channel' => 'in_app',
            'segment' => 'tournament_participants',
            'subject' => 'Test',
            'message' => 'x',
        ], 1);

        $this->assertNotNull($campaignId);
        $count = $service->launch(1, (int) $campaignId, 1);
        $this->assertGreaterThanOrEqual(1, $count);
        $this->created = [
            'campaign' => (int) $campaignId,
            'tournament' => $tournamentId,
            'customers' => [$customerAId, $customerBId],
        ];

        $rows = $db->table('crm_campaign_recipients')->select('customer_id')->where('campaign_id', (int) $campaignId)->get()->getResultArray();
        $ids = array_map('intval', array_column($rows, 'customer_id'));
        $this->assertContains($customerAId, $ids);
        $this->assertNotContains($customerBId, $ids);
    }
}
