<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * TC M3 — Branch CRUD + giờ mở cửa mặc định + chặn xóa khi còn sân
 */
class BranchFeatureTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private array $loginSession = [
        'isLoggedIn'    => true,
        'userId'        => 2,
        'is_superadmin' => false,
        'tenant_id'     => 1,
        'branch_id'     => 2,
        'locale'        => 'vi',
    ];

    private array $createdBranchIds = [];
    private array $createdCourtIds  = [];

    protected function tearDown(): void
    {
        $db = \Config\Database::connect();
        if (! empty($this->createdCourtIds)) {
            $db->table('courts')->whereIn('id', $this->createdCourtIds)->delete();
        }
        if (! empty($this->createdBranchIds)) {
            $db->table('branch_opening_hours')->whereIn('branch_id', $this->createdBranchIds)->delete();
            $db->table('branches')->whereIn('id', $this->createdBranchIds)->delete();
        }
        parent::tearDown();
    }

    public function testBranchListLoads(): void
    {
        $result = $this->withSession($this->loginSession)->get('/admin/branches');
        $result->assertOK();
    }

    public function testCreateBranchSeedsDefaultHours(): void
    {
        $result = $this->withSession($this->loginSession)->post('/admin/branches/create', [
            'code'   => 'CN-TEST',
            'name'   => 'Chi nhánh Test',
            'phone'  => '0901234567',
            'status' => 'active',
        ]);

        $result->assertRedirect();

        $db = \Config\Database::connect();
        $branch = $db->table('branches')->where('code', 'CN-TEST')->where('tenant_id', 1)->get()->getRowArray();
        $this->assertNotNull($branch);
        $this->createdBranchIds[] = (int) $branch['id'];

        // 7 ngày giờ mở cửa mặc định
        $hours = $db->table('branch_opening_hours')->where('branch_id', $branch['id'])->countAllResults();
        $this->assertSame(7, $hours);
    }

    public function testCreateBranchValidationFails(): void
    {
        $result = $this->withSession($this->loginSession)->post('/admin/branches/create', [
            'code' => '', 'name' => '', 'status' => 'active',
        ]);

        $result->assertRedirect(); // redirect back with errors
    }

    public function testDeleteBranchWithCourtsIsBlocked(): void
    {
        $db = \Config\Database::connect();

        // Tạo branch tenant 1 + 1 sân thuộc branch đó
        $db->table('branches')->insert([
            'tenant_id' => 1, 'code' => 'CN-BLOCK', 'name' => 'CN có sân',
            'status' => 'active', 'is_active' => 1, 'created_by' => 2,
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $branchId = (int) $db->insertID();
        $this->createdBranchIds[] = $branchId;

        $courtTypeRow = $db->table('court_types')->select('id')->get()->getRowArray();
        $courtTypeId  = (int) ($courtTypeRow['id'] ?? 1);
        $db->table('courts')->insert([
            'tenant_id' => 1, 'branch_id' => $branchId, 'court_type_id' => $courtTypeId,
            'code' => 'C-BLOCK', 'name_vi' => 'Sân chặn xóa', 'status' => 'available',
            'created_by' => 2,
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->createdCourtIds[] = (int) $db->insertID();

        $result = $this->withSession($this->loginSession)->post('/admin/branches/delete/' . $branchId);
        $result->assertRedirect();

        $branch = $db->table('branches')->where('id', $branchId)->where('deleted_at', null)->get()->getRowArray();
        $this->assertNotNull($branch, 'Chi nhánh còn sân không được xóa');
    }

    public function testDeleteEmptyBranchSucceeds(): void
    {
        $db = \Config\Database::connect();
        $db->table('branches')->insert([
            'tenant_id' => 1, 'code' => 'CN-EMPTY', 'name' => 'CN trống',
            'status' => 'active', 'is_active' => 1, 'created_by' => 2,
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $branchId = (int) $db->insertID();

        $result = $this->withSession($this->loginSession)->post('/admin/branches/delete/' . $branchId);
        $result->assertRedirect();

        $branch = $db->table('branches')->where('id', $branchId)->where('deleted_at', null)->get()->getRowArray();
        $this->assertNull($branch, 'Chi nhánh trống phải bị xóa mềm');

        // Dọn cứng
        $db->table('branches')->where('id', $branchId)->delete();
    }

    public function testHoursPageLoads(): void
    {
        $result = $this->withSession($this->loginSession)->get('/admin/branches/hours/2');
        $result->assertOK();
    }
}
