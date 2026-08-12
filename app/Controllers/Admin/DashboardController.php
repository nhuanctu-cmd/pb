<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\TenantModel;
use App\Models\BranchModel;
use App\Models\UserModel;
use App\Models\AuditLogModel;

class DashboardController extends BaseController
{
    public function index()
    {
        $tenantModel = new TenantModel();
        $branchModel = new BranchModel();
        $userModel = new UserModel();
        $auditLogModel = new AuditLogModel();
        $operationsDashboard = service('operationsDashboardService');
        $db = \Config\Database::connect();

        $tenantId = current_tenant_id();
        $tenantId = $tenantId ? (int) $tenantId : 0;
        $branchId = current_branch_id();
        $branchId = $branchId ? (int) $branchId : 0;
        $isGlobalView = is_superadmin();
        $scopeBranchId = $isGlobalView ? null : ($branchId > 0 ? $branchId : null);
        $currentUser = user();
        $primaryRole = (string) (session()->get('primary_role') ?? ($isGlobalView ? 'super-admin' : 'staff'));
        $roleLabels = [
            'super-admin' => 'Quản trị hệ thống',
            'owner' => 'Chủ câu lạc bộ',
            'branch-manager' => 'Quản lý chi nhánh',
            'staff' => 'Nhân viên vận hành',
            'referee' => 'Trọng tài',
            'player' => 'Người chơi',
        ];
        $hour = (int) date('G');
        $greeting = $hour < 12 ? 'Chào buổi sáng' : ($hour < 18 ? 'Chào buổi chiều' : 'Chào buổi tối');
        $branch = $branchId > 0 ? $branchModel->find($branchId) : null;

        $this->viewData['pageTitle'] = 'Dashboard';
        $this->viewData['dashboardContext'] = [
            'greeting' => $greeting,
            'name' => trim((string) ($currentUser?->first_name ?? session()->get('fullName') ?? 'bạn')),
            'role' => $roleLabels[$primaryRole] ?? ucfirst(str_replace('-', ' ', $primaryRole)),
            'role_slug' => $primaryRole,
            'tenant_name' => session()->get('tenant_name') ?? 'Pickleball',
            'branch_name' => $isGlobalView ? 'Toàn tenant' : ($branch->name ?? 'Chi nhánh phụ trách'),
            'is_global' => $isGlobalView,
        ];
        // The admin UI is tenant-contextual. Never fall back to a global
        // aggregate when the current tenant is missing.
        $this->viewData['totalTenants'] = is_superadmin()
            ? $tenantModel->where('status', 'active')->where('deleted_at', null)->countAllResults()
            : ($tenantId > 0 ? 1 : 0);
        $this->viewData['totalBranches'] = $tenantId > 0
            ? ($scopeBranchId
                ? $branchModel->where('tenant_id', $tenantId)->where('id', $scopeBranchId)->where('deleted_at', null)->countAllResults()
                : $branchModel->where('tenant_id', $tenantId)->where('deleted_at', null)->countAllResults())
            : 0;
        $this->viewData['totalUsers'] = $tenantId > 0
            ? ($scopeBranchId
                ? $userModel->where('tenant_id', $tenantId)->where('branch_id', $scopeBranchId)->where('deleted_at', null)->countAllResults()
                : $userModel->where('tenant_id', $tenantId)->where('deleted_at', null)->countAllResults())
            : 0;
        $this->viewData['recentActivities'] = $tenantId > 0
            ? $auditLogModel->where('tenant_id', $tenantId)->orderBy('created_at', 'DESC')->limit(10)->findAll()
            : [];
        $this->viewData['operations'] = $tenantId > 0 ? $operationsDashboard->get($tenantId, $this->request->getGet('date'), $scopeBranchId) : [];
        $this->viewData['platformStats'] = $tenantId > 0 ? $this->platformStats($db, $tenantId, $scopeBranchId) : [];
        $this->viewData['commercialLinks'] = [
            ['label' => 'Front Desk', 'description' => 'Booking, check-in và sân đang sử dụng', 'icon' => 'bi-person-workspace', 'url' => '/admin/front-desk', 'color' => 'primary'],
            ['label' => 'Daily Closing', 'description' => 'Đối soát tiền mặt, QR, POS cuối ca', 'icon' => 'bi-cash-stack', 'url' => '/admin/daily-closing', 'color' => 'success'],
            ['label' => 'Gia hạn hội viên', 'description' => 'Danh sách khách sắp hết hạn', 'icon' => 'bi-arrow-repeat', 'url' => '/admin/memberships/renewals', 'color' => 'warning'],
            ['label' => 'CRM Campaign', 'description' => 'Chăm sóc theo phân khúc khách hàng', 'icon' => 'bi-megaphone-fill', 'url' => '/admin/crm-campaigns', 'color' => 'info'],
        ];
        $this->viewData['currentTenantName'] = session('tenant_name') ?: 'Chưa chọn tenant';

        return $this->render('admin/dashboard', $this->viewData);
    }

    private function platformStats($db, int $tenantId, ?int $branchId = null): array
    {
        $today = date('Y-m-d');
        $stats = [
            'courts' => ['label' => 'Sân hoạt động', 'icon' => 'bi-grid-3x3-gap', 'value' => 0, 'link' => '/admin/courts'],
            'players' => ['label' => 'Người chơi', 'icon' => 'bi-people', 'value' => 0, 'link' => '/admin/players'],
            'bookings' => ['label' => 'Tổng booking', 'icon' => 'bi-calendar-check', 'value' => 0, 'link' => '/admin/bookings'],
            'tournaments' => ['label' => 'Giải đấu', 'icon' => 'bi-trophy', 'value' => 0, 'link' => '/admin/tournaments/scheduler'],
            'memberships' => ['label' => 'Hội viên active', 'icon' => 'bi-award', 'value' => 0, 'link' => '/admin/memberships'],
            'products' => ['label' => 'Sản phẩm active', 'icon' => 'bi-box-seam', 'value' => 0, 'link' => '/admin/pos/products'],
            'matches' => ['label' => 'Trận chính thức', 'icon' => 'bi-controller', 'value' => 0, 'link' => '/admin/matches'],
            'disputes' => ['label' => 'Khiếu nại mở', 'icon' => 'bi-shield-exclamation', 'value' => 0, 'link' => '/admin/matches'],
        ];

        $count = static function (string $table, array $where = []) use ($db, $tenantId, $branchId): int {
            if (! $db->tableExists($table)) {
                return 0;
            }
            $builder = $db->table($table)->where('tenant_id', $tenantId);
            if ($branchId && $db->fieldExists('branch_id', $table)) {
                $builder->where('branch_id', $branchId);
            } elseif ($branchId && $table === 'players' && $db->fieldExists('home_branch_id', $table)) {
                $builder->where('home_branch_id', $branchId);
            }
            foreach ($where as $column => $value) {
                is_array($value) ? $builder->whereIn($column, $value) : $builder->where($column, $value);
            }
            return (int) $builder->countAllResults();
        };

        if ($db->tableExists('courts')) {
            $stats['courts']['value'] = $count('courts', ['status' => ['available', 'occupied']]);
        }
        if ($db->tableExists('players')) {
            $stats['players']['value'] = $count('players', ['status' => 'active']);
        }
        if ($db->tableExists('bookings')) {
            $stats['bookings']['value'] = $count('bookings', ['deleted_at' => null]);
        }
        if ($db->tableExists('tournaments')) {
            $stats['tournaments']['value'] = $count('tournaments', ['status' => ['open', 'running', 'completed']]);
        }
        if ($db->tableExists('memberships')) {
            $stats['memberships']['value'] = $count('memberships', ['status' => 'active']);
        }
        if ($db->tableExists('products')) {
            $stats['products']['value'] = $count('products', ['status' => 'active']);
        }
        if ($db->tableExists('matches')) {
            $stats['matches']['value'] = $count('matches', ['status' => 'official']);
        }
        if ($db->tableExists('match_disputes')) {
            $stats['disputes']['value'] = $count('match_disputes', ['status' => ['open', 'reviewing']]);
        }

        $stats['today'] = [
            'label' => 'Booking hôm nay',
            'icon' => 'bi-calendar2-day',
            'value' => $db->tableExists('bookings') ? $count('bookings', ['booking_date' => $today, 'deleted_at' => null]) : 0,
            'link' => '/admin/bookings?date=' . $today,
        ];

        return $stats;
    }
}
