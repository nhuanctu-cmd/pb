<?php

namespace Tests\Unit;

use App\Models\CourtModel;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

class FacilityClubIntegrationTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $db = Database::connect();
        if ($db->tableExists('courts') && ! $db->fieldExists('club_id', 'courts')) {
            $db->query('ALTER TABLE `courts` ADD COLUMN `club_id` INT UNSIGNED NULL AFTER `tenant_id`');
            $db->query('ALTER TABLE `courts` ADD INDEX `idx_test_courts_club` (`tenant_id`, `club_id`)');
        }
    }

    public function testCourtGridLoadsClubNameAndCanFilterByClub(): void
    {
        $db = Database::connect();
        $branch = $db->table('branches')->where('deleted_at', null)->orderBy('id', 'ASC')->get(1)->getRow();
        $this->assertNotNull($branch, 'Test database needs at least one branch.');
        $court = $db->table('courts')->where('branch_id', $branch->id)->where('deleted_at', null)->orderBy('id', 'ASC')->get(1)->getRow();
        $this->assertNotNull($court, 'Test database needs at least one court.');

        $db->table('clubs')->insert(['tenant_id' => $branch->tenant_id, 'name_vi' => 'CLB Court Integration ' . bin2hex(random_bytes(3)), 'name_en' => 'Court Integration Club', 'status' => 'active']);
        $clubId = (int) $db->insertID();
        $oldClubId = $court->club_id ?? null;
        $db->table('courts')->where('id', $court->id)->update(['club_id' => $clubId]);

        $rows = (new CourtModel())->getByBranch((int) $branch->id, ['club_id' => $clubId]);
        $this->assertNotEmpty($rows);
        $this->assertSame($clubId, (int) $rows[0]->club_id);
        $this->assertSame('Court Integration Club', $rows[0]->club_name_en);

        $db->table('courts')->where('id', $court->id)->update(['club_id' => $oldClubId]);
        $db->table('clubs')->where('id', $clubId)->delete();
    }
}
