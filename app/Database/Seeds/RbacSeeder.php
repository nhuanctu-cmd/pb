<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * RBAC chuẩn: ma trận quyền đầy đủ theo module + gán quyền cho từng vai trò.
 * Idempotent: chạy lại nhiều lần không nhân đôi dữ liệu (upsert theo slug,
 * reset role_permissions theo map chuẩn).
 */
class RbacSeeder extends Seeder
{
    /**
     * Ma trận quyền: module => [action => Tên hiển thị]
     */
    private const PERMISSIONS = [
        'dashboard' => [
            'view' => 'Xem trang tổng quan',
        ],
        'tenants' => [
            'view'   => 'Xem khách thuê',
            'create' => 'Thêm khách thuê',
            'edit'   => 'Sửa khách thuê',
            'delete' => 'Xóa khách thuê',
        ],
        'plans' => [
            'view'   => 'Xem gói dịch vụ',
            'manage' => 'Quản lý gói dịch vụ',
        ],
        'facilities' => [
            'view'   => 'Xem cụm sân',
            'create' => 'Thêm cụm sân',
            'edit'   => 'Sửa cụm sân',
            'delete' => 'Xóa cụm sân',
        ],
        'branches' => [
            'view'   => 'Xem chi nhánh',
            'create' => 'Thêm chi nhánh',
            'edit'   => 'Sửa chi nhánh',
            'delete' => 'Xóa chi nhánh',
        ],
        'courts' => [
            'view'        => 'Xem sân',
            'create'      => 'Thêm sân',
            'edit'        => 'Sửa sân',
            'delete'      => 'Xóa sân',
            'maintenance' => 'Quản lý bảo trì sân',
        ],
        'bookings' => [
            'view'    => 'Xem đặt sân',
            'create'  => 'Tạo đặt sân',
            'edit'    => 'Sửa/đổi lịch đặt sân',
            'cancel'  => 'Hủy đặt sân',
            'checkin' => 'Check-in khách',
        ],
        'pos' => [
            'access' => 'Bán hàng tại quầy',
            'manage' => 'Quản lý sản phẩm & kho',
        ],
        'payments' => [
            'view'   => 'Xem hóa đơn/thanh toán',
            'pay'    => 'Thu tiền',
            'refund' => 'Hoàn tiền',
            'config' => 'Cấu hình thanh toán',
        ],
        'tournaments' => [
            'view'      => 'Xem giải đấu',
            'create'    => 'Tạo giải đấu',
            'edit'      => 'Sửa giải đấu',
            'delete'    => 'Xóa giải đấu',
            'manage'    => 'Điều hành giải (duyệt/xếp lịch)',
            'sanction'  => 'Phê duyệt giải tính BXH',
            'checkin'   => 'Check-in VĐV giải đấu',
        ],
        'ranking' => [
            'view'       => 'Xem bảng xếp hạng',
            'policy'     => 'Quản lý chính sách BXH',
            'recalculate'=> 'Tính lại BXH',
            'adjust'     => 'Điều chỉnh điểm BXH',
            'audit'      => 'Kiểm tra BXH',
        ],
        'rating' => [
            'view'        => 'Xem điểm trình',
            'policy'      => 'Quản lý chính sách điểm trình',
            'recalculate' => 'Tính lại điểm trình',
            'fraud'       => 'Xem cảnh báo gian lận',
            'review'      => 'Duyệt claim/import và cảnh báo rating',
            'adjust'      => 'Điều chỉnh rating thủ công',
            'imports'     => 'Quản lý dữ liệu import rating',
        ],
        'player-identity' => [
            'merge'  => 'Gộp tài khoản VĐV',
            'verify' => 'Xác thực VĐV',
        ],
        'scores' => [
            'view'  => 'Xem tỷ số',
            'input' => 'Nhập tỷ số trận',
        ],
        'pricing-rules' => [
            'view'   => 'Xem bảng giá động',
            'create' => 'Thêm quy tắc giá',
            'edit'   => 'Sửa quy tắc giá',
            'delete' => 'Xóa quy tắc giá',
        ],
        'players' => [
            'view'   => 'Xem người chơi',
            'create' => 'Thêm người chơi',
            'edit'   => 'Sửa người chơi',
            'delete' => 'Xóa người chơi',
            'wallet' => 'Quản lý ví người chơi',
        ],
        'memberships' => [
            'view'   => 'Xem hội viên',
            'create' => 'Tạo hội viên',
            'manage' => 'Quản lý gói hội viên',
        ],
        'clubs' => [
            'view'   => 'Xem câu lạc bộ',
            'manage' => 'Quản lý câu lạc bộ',
        ],
        'teams' => [
            'view'   => 'Xem đội',
            'manage' => 'Quản lý đội',
        ],
        'matches' => [
            'view'   => 'Xem kèo mở',
            'manage' => 'Duyệt/điều phối kèo',
        ],
        'users' => [
            'view'   => 'Xem người dùng',
            'create' => 'Thêm người dùng',
            'edit'   => 'Sửa người dùng',
            'delete' => 'Xóa người dùng',
        ],
        'roles' => [
            'view'   => 'Xem vai trò',
            'create' => 'Thêm vai trò',
            'edit'   => 'Sửa vai trò & phân quyền',
            'delete' => 'Xóa vai trò',
        ],
        'settings' => [
            'view' => 'Xem thiết lập',
            'edit' => 'Sửa thiết lập',
        ],
        'audit-logs' => [
            'view' => 'Xem nhật ký hệ thống',
        ],
        'media' => [
            'view'   => 'Xem thư viện media',
            'upload' => 'Upload file media',
            'delete' => 'Xóa file media',
        ],
        'notifications' => [
            'view'   => 'Xem thông báo',
            'manage' => 'Quản lý mẫu thông báo',
        ],
        'reports' => [
            'view'   => 'Xem báo cáo',
            'export' => 'Xuất báo cáo',
        ],
    ];

    /**
     * Map vai trò => danh sách quyền ('*' = tất cả)
     */
    private const ROLE_MAP = [
        'super-admin' => ['*'],

        'owner' => [
            'dashboard.view',
            'plans.view',
            'facilities.*',
            'branches.*',
            'courts.*',
            'bookings.*',
            'pos.*',
            'payments.*',
            'tournaments.*',
            'scores.*',
            'pricing-rules.*',
            'players.*',
            'memberships.*',
            'clubs.*',
            'teams.*',
            'matches.*',
            'users.*',
            'roles.view',
            'settings.*',
            'audit-logs.view',
            'media.*',
            'notifications.*',
            'reports.*',
            'rating.*',
        ],

        'branch-manager' => [
            'dashboard.view',
            'branches.view',
            'courts.view', 'courts.edit', 'courts.maintenance',
            'bookings.*',
            'pos.access',
            'payments.view', 'payments.pay',
            'tournaments.view',
            'scores.view',
            'pricing-rules.view',
            'players.view', 'players.create', 'players.edit', 'players.wallet',
            'memberships.view', 'memberships.create',
            'clubs.view', 'teams.view', 'matches.view', 'matches.manage',
            'settings.view',
            'media.view', 'media.upload',
            'notifications.view',
            'reports.view',
        ],

        'staff' => [
            'dashboard.view',
            'courts.view',
            'bookings.view', 'bookings.create', 'bookings.checkin',
            'pos.access',
            'payments.view', 'payments.pay',
            'tournaments.view',
            'scores.view',
            'players.view', 'players.create',
            'memberships.view',
            'matches.view',
        ],

        'referee' => [
            'dashboard.view',
            'scores.view', 'scores.input',
            'tournaments.view',
            'bookings.view',
        ],

        'player' => [], // chỉ dùng cổng người chơi, không vào admin
    ];

    public function run()
    {
        $now = date('Y-m-d H:i:s');
        $db  = $this->db;

        // 1. Upsert toàn bộ permissions theo slug
        foreach (self::PERMISSIONS as $module => $actions) {
            foreach ($actions as $action => $name) {
                $slug = "{$module}.{$action}";
                $existing = $db->table('permissions')->where('slug', $slug)->get()->getRowArray();

                if ($existing) {
                    $db->table('permissions')->where('id', $existing['id'])->update([
                        'name'       => $name,
                        'module'     => $module,
                        'updated_at' => $now,
                    ]);
                } else {
                    $db->table('permissions')->insert([
                        'name'       => $name,
                        'slug'       => $slug,
                        'module'     => $module,
                        'is_active'  => 1,
                        'status'     => 'active',
                        'created_by' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }

        // 2. Reset role_permissions theo map chuẩn
        $permIds = [];
        foreach ($db->table('permissions')->select('id, slug')->where('deleted_at', null)->get()->getResultArray() as $row) {
            $permIds[$row['slug']] = (int) $row['id'];
        }

        $allSlugs = array_keys($permIds);

        foreach (self::ROLE_MAP as $roleSlug => $grants) {
            $role = $db->table('roles')->where('slug', $roleSlug)->get()->getRowArray();
            if (! $role) {
                continue;
            }

            // Giải quyền mở rộng dạng 'module.*' và '*'
            $slugs = [];
            foreach ($grants as $grant) {
                if ($grant === '*') {
                    $slugs = $allSlugs;
                    break;
                }
                if (str_ends_with($grant, '.*')) {
                    $module = substr($grant, 0, -2);
                    foreach ($allSlugs as $slug) {
                        if (str_starts_with($slug, $module . '.')) {
                            $slugs[] = $slug;
                        }
                    }
                } else {
                    $slugs[] = $grant;
                }
            }
            $slugs = array_values(array_unique($slugs));

            // Xóa quyền cũ, gán quyền mới (transaction)
            $db->transStart();

            $db->table('role_permissions')->where('role_id', $role['id'])->delete();

            foreach ($slugs as $slug) {
                if (! isset($permIds[$slug])) {
                    continue; // quyền chưa tồn tại — bỏ qua an toàn
                }
                $db->table('role_permissions')->insert([
                    'role_id'       => $role['id'],
                    'permission_id' => $permIds[$slug],
                    'created_by'    => 1,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]);
            }

            $db->transComplete();
        }

        $totalPerms = count($allSlugs);
        echo "RBAC: {$totalPerms} quyền, " . count(self::ROLE_MAP) . " vai trò đã đồng bộ.\n";
    }
}
