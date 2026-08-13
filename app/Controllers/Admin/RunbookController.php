<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BranchModel;
use App\Models\ClubModel;
use App\Models\CourtModel;
use App\Models\FacilityModel;

class RunbookController extends BaseController
{
    public function index(): string
    {
        $today = date('Y-m-d');
        $tenantId = (int) (current_tenant_id() ?: 1);

        $facility = $this->firstFacility($tenantId);
        $branch = $this->firstBranch($tenantId);
        $court = $this->firstCourt($tenantId);
        $club = $this->firstClub($tenantId);

        $facilityId = (int) ($facility->id ?? 0);
        $branchId = (int) ($branch->id ?? 0);
        $courtId = (int) ($court->id ?? 0);
        $clubId = (int) ($club->id ?? 0);

        $this->viewData['pageTitle'] = 'Runbook vận hành thương mại (15 module production)';
        $this->viewData['today'] = $today;
        $this->viewData['coreFlow'] = [
            ['label' => 'Front Desk', 'url' => '/admin/front-desk?date=' . $today],
            ['label' => 'Owner Dashboard', 'url' => '/admin/owner-dashboard?date=' . $today],
            ['label' => 'Daily Closing', 'url' => '/admin/daily-closing?date=' . $today],
            ['label' => 'Membership Renewal', 'url' => '/admin/memberships/renewals?days=30&status=active'],
            ['label' => 'CRM Campaign', 'url' => '/admin/crm-campaigns'],
        ];
        $this->viewData['flow'] = [
            [
                'title'       => '1) Venue Control Room',
                'description' => 'Màn hình điều phối sân theo ngày: sân đang đánh, sân trống, sân trễ, chưa check-in.',
                'steps'       => [
                    ['label' => 'Tổng quan vận hành', 'url' => '/admin/venue-operations?date=' . $today],
                    ['label' => 'Export PDF', 'url' => '/admin/venue-operations?date=' . $today . '&export=pdf'],
                    ['label' => 'Export CSV', 'url' => '/admin/venue-operations?date=' . $today . '&export=csv'],
                ],
            ],
            [
                'title'       => '2) TV/LED Mode (Live Board)',
                'description' => 'Gắn URL ở TV/LED và tự chuyển LIVE → NEXT → CALL PLAYER → KẾT QUẢ.',
                'steps'       => [
                    ['label' => 'Tường hình điều khiển TV', 'url' => '/admin/tournaments/control-room'],
                    ['label' => 'TV live-score', 'url' => '/live-scores/tv'],
                    ['label' => 'Print/board fallback', 'url' => '/admin/print-center'],
                ],
            ],
            [
                'title'       => '3) Print Center',
                'description' => 'In tập trung: badge, bảng tên, lịch, phiếu trận, bảng hạng & chứng nhận.',
                'steps'       => [
                    ['label' => 'Print Center', 'url' => '/admin/print-center'],
                    ['label' => 'In nhanh', 'url' => '/admin/print-center/print'],
                    ['label' => 'Xem lịch giải trên print', 'url' => '/admin/tournaments'],
                ],
            ],
            [
                'title'       => '4) Tournament Template',
                'description' => 'Tái sử dụng mẫu giải cũ: đổi ngày → mở đăng ký nhanh.',
                'steps'       => [
                    ['label' => 'Template list', 'url' => '/admin/tournament-templates'],
                    ['label' => 'Tạo từ template', 'url' => '/admin/tournament-templates'],
                    ['label' => 'Tournament list', 'url' => '/admin/tournaments'],
                ],
            ],
            [
                'title'       => '5) Front Desk',
                'description' => 'Queue hold/no-show/check-in nhanh theo slot giờ thực.',
                'steps'       => [
                    ['label' => 'Danh sách quầy hôm nay', 'url' => '/admin/front-desk?date=' . $today],
                    ['label' => 'Sắp xếp hold', 'url' => '/admin/front-desk?date=' . $today . '&status=hold'],
                    ['label' => 'Lịch chưa xử lý', 'url' => '/admin/waitlist'],
                    ['label' => 'Queue background', 'url' => '/admin/queue'],
                ],
            ],
            [
                'title'       => '6) Owner Dashboard',
                'description' => 'KPI MTD/YTD, công nợ chênh lệch, cảnh báo giờ cao điểm.',
                'steps'       => [
                    ['label' => 'Dashboard Owner', 'url' => '/admin/owner-dashboard?date=' . $today],
                    ['label' => 'Dashboard admin', 'url' => '/admin/dashboard?date=' . $today],
                    ['label' => 'Payments', 'url' => '/admin/payments'],
                ],
            ],
            [
                'title'       => '7) Daily Closing',
                'description' => 'Chốt ca theo ngày, ký điện tử quầy, khóa số liệu tenant+branch+date.',
                'steps'       => [
                    ['label' => 'Ngày hôm nay', 'url' => '/admin/daily-closing?date=' . $today],
                    ['label' => 'Đóng ca', 'url' => '/admin/daily-closing'],
                    ['label' => 'Print', 'url' => '/admin/daily-closing/print?date=' . $today],
                    ['label' => 'PDF', 'url' => '/admin/daily-closing/pdf?date=' . $today],
                ],
            ],
            [
                'title'       => '8) Membership Renewal',
                'description' => 'Danh sách cần gia hạn, log gia hạn, nhắc tự động SMS/Zalo/Email.',
                'steps'       => [
                    ['label' => 'Gia hạn gần đến hạn (30 ngày)', 'url' => '/admin/memberships/renewals?days=30'],
                    ['label' => 'Gia hạn gần đến hạn (60 ngày)', 'url' => '/admin/memberships/renewals?days=60'],
                    ['label' => 'Export lịch sử gia hạn', 'url' => '/admin/memberships/renewals/export?days=30'],
                    ['label' => 'Lọc danh sách gia hạn', 'url' => '/admin/memberships/renewals?status=active&days=30'],
                ],
            ],
            [
                'title'       => '9) CRM Campaign',
                'description' => 'Template biến, test gửi, rate-limit và retry.',
                'steps'       => [
                    ['label' => 'Danh sách campaign', 'url' => '/admin/crm-campaigns'],
                    ['label' => 'Gửi thử campaign', 'url' => '/admin/crm-campaigns'],
                    ['label' => 'Dispatch queue', 'url' => '/admin/crm-campaigns'],
                ],
            ],
            [
                'title'       => '10) Club Membership',
                'description' => 'Quản lý membership theo CLB, gia hạn, lịch sử trạng thái.',
                'steps'       => [
                    ['label' => 'Danh sách membership', 'url' => '/admin/memberships'],
                    ['label' => 'Packages membership', 'url' => '/admin/memberships/packages'],
                    ['label' => 'Members theo CLB', 'url' => $clubId > 0 ? ('/api/v1/clubs/' . $clubId . '/members?tenant_id=' . $tenantId . '&status=active') : '/api/v1/clubs?tenant_id=' . $tenantId],
                    ['label' => 'Lịch sử membership', 'url' => $clubId > 0 ? ('/api/v1/clubs/' . $clubId . '/memberships/history?tenant_id=' . $tenantId) : ('/api/v1/clubs?tenant_id=' . $tenantId)],
                ],
            ],
            [
                'title'       => '11) Facility Management',
                'description' => 'Quản lý cụm sân tenant-level + dashboard theo cơ sở.',
                'steps'       => [
                    ['label' => 'Danh sách cụm sân', 'url' => '/admin/facilities'],
                    ['label' => 'Tạo cụm sân', 'url' => '/admin/facilities/create'],
                    ['label' => 'Dashboard cụm sân', 'url' => $facilityId > 0 ? ('/admin/facilities/dashboard/' . $facilityId) : '/admin/facilities'],
                ],
            ],
            [
                'title'       => '12) Branch & Court Ops',
                'description' => 'Chi nhánh, sân, lịch bảo trì, thiết bị và lịch hoạt động.',
                'steps'       => [
                    ['label' => 'Branches', 'url' => '/admin/branches'],
                    ['label' => 'Branch giờ mở cửa', 'url' => $branchId > 0 ? ('/admin/branches/hours/' . $branchId) : '/admin/branches'],
                    ['label' => 'Courts', 'url' => '/admin/courts'],
                    ['label' => 'Court Maintenance', 'url' => $courtId > 0 ? ('/admin/courts/maintenance/' . $courtId) : '/admin/courts'],
                ],
            ],
            [
                'title'       => '13) Club Management',
                'description' => 'Quản lý hồ sơ CLB, trạng thái, liên kết với public portal.',
                'steps'       => [
                    ['label' => 'Danh sách CLB', 'url' => '/admin/clubs'],
                    ['label' => 'Tạo CLB mới', 'url' => '/admin/clubs/create'],
                    ['label' => 'Chi tiết CLB mẫu', 'url' => $clubId > 0 ? ('/admin/clubs/edit/' . $clubId) : '/admin/clubs'],
                    ['label' => 'Public club', 'url' => $clubId > 0 ? ('/clubs/' . $clubId) : '/clubs'],
                ],
            ],
            [
                'title'       => '14) Facility-Club Partnership',
                'description' => 'Gán CLB cho cụm sân sâu, ưu tiên booking, tỷ lệ chia doanh thu.',
                'steps'       => [
                    ['label' => 'Phân phối CLB theo cụm sân', 'url' => $facilityId > 0 ? ('/admin/facilities/clubs/' . $facilityId) : '/admin/facilities'],
                    ['label' => 'API gán CLB cho cơ sở', 'url' => $facilityId > 0 ? ('/api/v1/facilities/' . $facilityId . '/clubs?tenant_id=' . $tenantId) : ('/api/v1/facilities?tenant_id=' . $tenantId)],
                    ['label' => 'API danh sách gán CLB', 'url' => $facilityId > 0 ? ('/api/v1/facilities/' . $facilityId . '/clubs?tenant_id=' . $tenantId) : ('/api/v1/facilities?tenant_id=' . $tenantId)],
                ],
            ],
            [
                'title'       => '15) Public Venue & Club + API chuẩn + Security hardening',
                'description' => 'Đảm bảo public portal + API pagination/filter/sort/export + kiểm soát quyền, audit, log thao tác.',
                'steps'       => [
                    ['label' => 'GET facilities', 'url' => '/api/v1/facilities?tenant_id=' . $tenantId . '&page=1&limit=5&sort_by=name_vi&sort_dir=ASC'],
                    ['label' => 'GET branches', 'url' => '/api/v1/branches?tenant_id=' . $tenantId . '&page=1&limit=5&sort_by=name&sort_dir=ASC'],
                    ['label' => 'GET courts', 'url' => '/api/v1/courts?tenant_id=' . $tenantId . '&page=1&limit=5&sort_by=sort_order&sort_dir=ASC'],
                    ['label' => 'GET clubs', 'url' => '/api/v1/clubs?tenant_id=' . $tenantId . '&page=1&limit=5&sort_by=name_vi&sort_dir=ASC'],
                    ['label' => 'Members của CLB', 'url' => '/api/v1/clubs/' . ($clubId > 0 ? $clubId : 1) . '/members?tenant_id=' . $tenantId],
                    ['label' => 'Export CSV facilities', 'url' => '/api/v1/facilities?tenant_id=' . $tenantId . '&format=csv'],
                    ['label' => 'Public venue', 'url' => '/venues'],
                    ['label' => 'Public club detail', 'url' => $clubId > 0 ? ('/clubs/' . $clubId) : '/clubs'],
                    ['label' => 'Public player profile', 'url' => '/players'],
                    ['label' => 'Audit logs', 'url' => '/admin/audit-logs'],
                    ['label' => 'Vai trò / phân quyền', 'url' => '/admin/roles'],
                    ['label' => 'Người dùng', 'url' => '/admin/users'],
                    ['label' => 'Integrations / API keys', 'url' => '/admin/integrations'],
                    ['label' => 'Settings', 'url' => '/admin/settings'],
                ],
            ],
        ];

        return $this->render('admin/runbook/index', $this->viewData);
    }

    private function firstFacility(int $tenantId): ?object
    {
        if ($tenantId <= 0) {
            return null;
        }

        return (new FacilityModel())->where('tenant_id', $tenantId)->where('deleted_at', null)->orderBy('id', 'ASC')->first();
    }

    private function firstBranch(int $tenantId): ?object
    {
        if ($tenantId <= 0) {
            return null;
        }

        return (new BranchModel())->where('tenant_id', $tenantId)->where('deleted_at', null)->orderBy('id', 'ASC')->first();
    }

    private function firstCourt(int $tenantId): ?object
    {
        if ($tenantId <= 0) {
            return null;
        }

        return (new CourtModel())->where('tenant_id', $tenantId)->where('deleted_at', null)->orderBy('id', 'ASC')->first();
    }

    private function firstClub(int $tenantId): ?object
    {
        if ($tenantId <= 0) {
            return null;
        }

        return (new ClubModel())->where('tenant_id', $tenantId)->where('deleted_at', null)->orderBy('id', 'ASC')->first();
    }
}
