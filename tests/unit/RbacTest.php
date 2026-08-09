<?php

namespace Tests\Unit;

use App\Services\PermissionService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * TC — RBAC chuẩn: ma trận quyền theo vai trò, helper can(), cache
 * Dữ liệu: pickball_test đã seed RbacSeeder + AccountSeeder
 */
class RbacTest extends CIUnitTestCase
{
    protected PermissionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PermissionService();
        PermissionService::clearCache();
    }

    private function userIdByEmail(string $email): int
    {
        $row = \Config\Database::connect()
            ->table('users')->where('email', $email)->get()->getRowArray();

        return (int) ($row['id'] ?? 0);
    }

    /** Ma trận quyền: số lượng quyền mỗi vai trò đúng thiết kế */
    public function testRolePermissionCounts(): void
    {
        $db = \Config\Database::connect();
        $counts = [];
        foreach ($db->table('roles')->get()->getResultArray() as $role) {
            $counts[$role['slug']] = $db->table('role_permissions')
                ->where('role_id', $role['id'])->countAllResults();
        }

        $this->assertSame(68, $counts['super-admin'], 'Super admin phải có đủ 68 quyền');
        $this->assertSame(60, $counts['owner']);
        $this->assertSame(28, $counts['branch-manager']);
        $this->assertSame(14, $counts['staff']);
        $this->assertSame(5, $counts['referee']);
        $this->assertSame(0, $counts['player'], 'Player không có quyền admin');
    }

    /** Staff: được tạo booking, KHÔNG được xóa user/quản lý roles */
    public function testStaffPermissions(): void
    {
        $staffId = $this->userIdByEmail('staff@pickleballpro.com');

        $this->assertTrue($this->service->hasPermission($staffId, 'bookings.view'));
        $this->assertTrue($this->service->hasPermission($staffId, 'bookings.create'));
        $this->assertTrue($this->service->hasPermission($staffId, 'bookings.checkin'));
        $this->assertTrue($this->service->hasPermission($staffId, 'pos.access'));

        $this->assertFalse($this->service->hasPermission($staffId, 'users.delete'));
        $this->assertFalse($this->service->hasPermission($staffId, 'roles.edit'));
        $this->assertFalse($this->service->hasPermission($staffId, 'settings.edit'));
        $this->assertFalse($this->service->hasPermission($staffId, 'payments.refund'));
    }

    /** Referee: chỉ nhập tỷ số + xem */
    public function testRefereePermissions(): void
    {
        $refereeId = $this->userIdByEmail('referee@pickleballpro.com');

        $this->assertTrue($this->service->hasPermission($refereeId, 'scores.input'));
        $this->assertTrue($this->service->hasPermission($refereeId, 'scores.view'));
        $this->assertTrue($this->service->hasPermission($refereeId, 'tournaments.view'));

        $this->assertFalse($this->service->hasPermission($refereeId, 'pos.access'));
        $this->assertFalse($this->service->hasPermission($refereeId, 'players.create'));
    }

    /** Owner: toàn quyền tenant nhưng KHÔNG quản lý tenants (chỉ super-admin) */
    public function testOwnerPermissions(): void
    {
        $ownerId = $this->userIdByEmail('owner@pickleballpro.com');

        $this->assertTrue($this->service->hasPermission($ownerId, 'users.create'));
        $this->assertTrue($this->service->hasPermission($ownerId, 'payments.refund'));
        $this->assertTrue($this->service->hasPermission($ownerId, 'pos.manage'));
        $this->assertTrue($this->service->hasPermission($ownerId, 'plans.view'));

        $this->assertFalse($this->service->hasPermission($ownerId, 'tenants.create'));
        $this->assertFalse($this->service->hasPermission($ownerId, 'tenants.delete'));
        $this->assertFalse($this->service->hasPermission($ownerId, 'plans.manage'));
    }

    /** Player: không có quyền admin nào */
    public function testPlayerHasNoAdminPermission(): void
    {
        $playerId = $this->userIdByEmail('player@pickleballpro.com');

        $this->assertFalse($this->service->hasPermission($playerId, 'bookings.view'));
        $this->assertFalse($this->service->hasPermission($playerId, 'dashboard.view'));
    }

    /** Helper can(): super admin luôn true; staff theo đúng ma trận */
    public function testCanHelper(): void
    {
        helper('app');

        $session = service('session');

        // Super admin
        $session->set(['isLoggedIn' => true, 'userId' => 1, 'is_superadmin' => true]);
        $this->assertTrue(can('tenants.delete'));

        // Staff
        $session->set([
            'isLoggedIn'    => true,
            'userId'        => $this->userIdByEmail('staff@pickleballpro.com'),
            'is_superadmin' => false,
        ]);
        PermissionService::clearCache();

        $this->assertTrue(can('bookings.create'));
        $this->assertFalse(can('users.delete'));

        // Khách (chưa đăng nhập) — remove() từng key vì destroy()
        // không xóa $_SESSION trong môi trường test CLI
        $session->remove('isLoggedIn');
        $session->remove('userId');
        $session->remove('is_superadmin');
        $this->assertFalse(can('bookings.view'));
    }

    /** Cache quyền: gọi 2 lần cùng kết quả, clearCache reset được */
    public function testPermissionCache(): void
    {
        $staffId = $this->userIdByEmail('staff@pickleballpro.com');

        $first  = $this->service->getUserPermissions($staffId);
        $second = $this->service->getUserPermissions($staffId);

        $this->assertSame(array_keys($first), array_keys($second));

        PermissionService::clearCache($staffId);
        $third = $this->service->getUserPermissions($staffId);
        $this->assertSame(array_keys($first), array_keys($third));
    }
}
