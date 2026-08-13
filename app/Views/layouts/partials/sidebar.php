<?php

/**
 * Sidebar vận hành theo đúng flow:
 * 1) Tình hình ca làm việc
 * 2) Đặt sân / check-in
 * 3) Giải đấu
 * 4) Membership & CRM
 * 5) Settings & tích hợp
 */

$today = date('Y-m-d');
$tenantId = (int) (current_tenant_id() ?: 1);
$currentBranchId = (int) ($current_branch_id ?? 0);
$isSuperAdmin = is_superadmin();

$canView = static function (?string $permission = null) use ($isSuperAdmin): bool {
    if ($isSuperAdmin) {
        return true;
    }
    if (! $permission) {
        return true;
    }
    return can($permission);
};

$menuGroups = [
    'Điều hành sân theo ca' => [
        [
            'label' => 'Tổng quan vận hành',
            'icon'  => 'bi-speedometer2',
            'url'   => '/admin/dashboard',
            'active'=> 'admin/dashboard',
            'perm'  => 'dashboard.view',
            'children' => [
                ['label' => 'Runbook 1-nút', 'icon' => 'bi-journal-check', 'url' => '/admin/runbook', 'active' => 'admin/runbook', 'perm' => 'dashboard.view'],
                ['label' => 'Kế hoạch ngày', 'icon' => 'bi-calendar-event', 'url' => '/admin/venue-operations?date=' . $today, 'active' => 'admin/venue-operations', 'perm' => 'facilities.view'],
                ['label' => 'Owner Dashboard', 'icon' => 'bi-columns-gap', 'url' => '/admin/owner-dashboard?date=' . $today, 'active' => 'admin/owner-dashboard', 'perm' => 'dashboard.view'],
                ['label' => 'Báo cáo vận hành', 'icon' => 'bi-bar-chart-line', 'url' => '/admin/operations-report', 'active' => 'admin/operations-report', 'perm' => 'dashboard.view'],
            ],
        ],
        ['label' => 'Venue Control Room', 'icon' => 'bi-building-check', 'url' => '/admin/venue-operations', 'active' => 'admin/venue-operations', 'perm' => 'facilities.view'],
        ['label' => 'Front Desk', 'icon' => 'bi-person-workspace', 'url' => '/admin/front-desk', 'active' => 'admin/front-desk', 'perm' => 'bookings.view'],
        ['label' => 'Daily Closing (chốt ca)', 'icon' => 'bi-cash-stack', 'url' => '/admin/daily-closing?date=' . $today, 'active' => 'admin/daily-closing', 'perm' => 'payments.view'],
    ],

    'Đặt sân & Khách hàng' => [
        ['label' => 'Bookings', 'icon' => 'bi-calendar2-check', 'url' => '/admin/bookings', 'active' => 'admin/bookings', 'perm' => 'bookings.view'],
        ['label' => 'Lịch đặt sân', 'icon' => 'bi-calendar3', 'url' => '/admin/bookings/calendar', 'active' => 'admin/bookings/calendar', 'perm' => 'bookings.view'],
        ['label' => 'Tạo booking nhanh', 'icon' => 'bi-plus-circle', 'url' => '/admin/bookings/create', 'active' => 'admin/bookings/create', 'perm' => 'bookings.view'],
        ['label' => 'Lịch lặp', 'icon' => 'bi-arrow-repeat', 'url' => '/admin/recurring-bookings', 'active' => 'admin/recurring-bookings', 'perm' => 'bookings.view'],
        ['label' => 'Waitlist', 'icon' => 'bi-hourglass-split', 'url' => '/admin/waitlist', 'active' => 'admin/waitlist', 'perm' => 'bookings.view'],
        ['label' => 'Walk-in', 'icon' => 'bi-person-walking', 'url' => '/admin/walk-ins', 'active' => 'admin/walk-ins', 'perm' => 'bookings.view'],
        [
            'label' => 'Khách hàng',
            'icon'  => 'bi-people',
            'url'   => '/admin/customers',
            'active'=> 'admin/customers',
            'perm'  => 'players.view',
            'children' => [
                ['label' => 'Danh sách khách', 'icon' => 'bi-people', 'url' => '/admin/customers', 'active' => 'admin/customers', 'perm' => 'players.view'],
                ['label' => 'Tìm booking theo khách', 'icon' => 'bi-search', 'url' => '/admin/customers', 'active' => 'admin/customers', 'perm' => 'players.view'],
                ['label' => 'Đánh dấu ưu tiên', 'icon' => 'bi-flag', 'url' => '/admin/customers?tag=vip', 'active' => 'admin/customers', 'perm' => 'players.view'],
            ],
        ],
    ],

    'Giải đấu' => [
        ['label' => 'Scheduler', 'icon' => 'bi-calendar-check', 'url' => '/admin/tournaments/scheduler', 'active' => 'admin/tournaments/scheduler', 'perm' => 'tournaments.manage'],
        ['label' => 'Control Room', 'icon' => 'bi-broadcast', 'url' => '/admin/tournaments/control-room', 'active' => 'admin/tournaments/control-room', 'perm' => 'tournaments.view'],
        ['label' => 'Bracket', 'icon' => 'bi-diagram-3', 'url' => '/admin/tournaments/bracket', 'active' => 'admin/tournaments/bracket', 'perm' => 'tournaments.view'],
        ['label' => 'Print Center', 'icon' => 'bi-printer', 'url' => '/admin/print-center', 'active' => 'admin/print-center', 'perm' => 'tournaments.view'],
        ['label' => 'Tournament Template', 'icon' => 'bi-copy', 'url' => '/admin/tournament-templates', 'active' => 'admin/tournament-templates', 'perm' => 'tournaments.view'],
        ['label' => 'TV / LED Board', 'icon' => 'bi-display', 'url' => '/live-scores/tv', 'active' => 'live-scores', 'perm' => 'tournaments.view', 'external' => true],
        ['label' => 'Hạng mục giải đấu', 'icon' => 'bi-bar-chart-steps', 'url' => '/admin/competitions', 'active' => 'admin/competitions', 'perm' => 'tournaments.view'],
    ],

    'Membership + CRM thương mại' => [
        ['label' => 'Gia hạn hội viên', 'icon' => 'bi-arrow-repeat', 'url' => '/admin/memberships/renewals?days=30&status=active', 'active' => 'admin/memberships/renewals', 'perm' => 'memberships.view'],
        ['label' => 'Hội viên', 'icon' => 'bi-card-checklist', 'url' => '/admin/memberships', 'active' => 'admin/memberships', 'perm' => 'memberships.view'],
        ['label' => 'Membership Packages', 'icon' => 'bi-box-seam', 'url' => '/admin/memberships/packages', 'active' => 'admin/memberships/packages', 'perm' => 'memberships.view'],
        ['label' => 'CRM Campaign', 'icon' => 'bi-megaphone-fill', 'url' => '/admin/crm-campaigns', 'active' => 'admin/crm-campaigns', 'perm' => 'players.view'],
        ['label' => 'Thanh toán', 'icon' => 'bi-credit-card', 'url' => '/admin/payments', 'active' => 'admin/payments', 'perm' => 'payments.view'],
        ['label' => 'POS', 'icon' => 'bi-shop', 'url' => '/admin/pos', 'active' => 'admin/pos', 'perm' => 'pos.access'],
        ['label' => 'Điểm thưởng/referral', 'icon' => 'bi-gift', 'url' => '/admin/growth', 'active' => 'admin/growth', 'perm' => 'players.view'],
    ],

    'Cơ sở dữ liệu & Thiết lập' => [
        [
            'label' => 'Cơ sở & Chi nhánh',
            'icon'  => 'bi-diagram-3',
            'url'   => '/admin/facilities',
            'active'=> 'admin/facilities',
            'perm'  => 'facilities.view',
            'children' => [
                ['label' => 'Facilities', 'icon' => 'bi-buildings', 'url' => '/admin/facilities', 'active' => 'admin/facilities', 'perm' => 'facilities.view'],
                ['label' => 'Branches', 'icon' => 'bi-geo-alt', 'url' => '/admin/branches', 'active' => 'admin/branches', 'perm' => 'branches.view'],
                ['label' => 'Courts', 'icon' => 'bi-grid-3x3-gap', 'url' => '/admin/courts', 'active' => 'admin/courts', 'perm' => 'courts.view'],
                ['label' => 'Lịch sân', 'icon' => 'bi-calendar4-week', 'url' => '/admin/courts/calendar', 'active' => 'admin/courts/calendar', 'perm' => 'courts.view'],
                ['label' => 'Giờ mở cửa chi nhánh', 'icon' => 'bi-clock', 'url' => '/admin/branches/hours/' . (int) $currentBranchId, 'active' => 'admin/branches/hours', 'perm' => 'branches.view'],
            ],
        ],
        [
            'label' => 'Người dùng & phân quyền',
            'icon'  => 'bi-shield-lock',
            'url'   => '/admin/users',
            'active'=> 'admin/users',
            'perm'  => 'users.view',
            'children' => [
                ['label' => 'Users', 'icon' => 'bi-people', 'url' => '/admin/users', 'active' => 'admin/users', 'perm' => 'users.view'],
                ['label' => 'Roles', 'icon' => 'bi-key', 'url' => '/admin/roles', 'active' => 'admin/roles', 'perm' => 'roles.view'],
                ['label' => 'Audit logs', 'icon' => 'bi-journal-text', 'url' => '/admin/audit-logs', 'active' => 'admin/audit-logs', 'perm' => 'audit-logs.view'],
            ],
        ],
        [
            'label' => 'Bảo mật & hệ thống',
            'icon'  => 'bi-shield-check',
            'url'   => '/admin/settings',
            'active'=> 'admin/settings',
            'perm'  => 'settings.view',
            'children' => [
                ['label' => 'Cài đặt chung', 'icon' => 'bi-gear', 'url' => '/admin/settings', 'active' => 'admin/settings', 'perm' => 'settings.view'],
                ['label' => 'Webhooks', 'icon' => 'bi-link-45deg', 'url' => '/admin/webhooks', 'active' => 'admin/webhooks', 'perm' => 'settings.view'],
                ['label' => 'Integrations', 'icon' => 'bi-plug', 'url' => '/admin/integrations', 'active' => 'admin/integrations', 'perm' => 'settings.view'],
                ['label' => 'Tenant', 'icon' => 'bi-building', 'url' => '/admin/tenants', 'active' => 'admin/tenants', 'perm' => 'tenants.view'],
                ['label' => 'Thông báo hệ thống', 'icon' => 'bi-bell', 'url' => '/admin/notifications', 'active' => 'admin/notifications', 'perm' => 'notifications.view'],
            ],
        ],
        [
            'label' => 'API chuẩn',
            'icon'  => 'bi-terminal',
            'url'   => '/api/v1/facilities?tenant_id=' . $tenantId,
            'active'=> 'api/v1/facilities',
            'perm'  => 'settings.view',
            'children' => [
                ['label' => 'Facility API', 'icon' => 'bi-code-slash', 'url' => '/api/v1/facilities?tenant_id=' . $tenantId, 'active' => 'api/v1/facilities', 'perm' => 'settings.view', 'external' => true],
                ['label' => 'Branch API', 'icon' => 'bi-code-slash', 'url' => '/api/v1/branches?tenant_id=' . $tenantId, 'active' => 'api/v1/branches', 'perm' => 'settings.view', 'external' => true],
                ['label' => 'Court API', 'icon' => 'bi-code-slash', 'url' => '/api/v1/courts?tenant_id=' . $tenantId, 'active' => 'api/v1/courts', 'perm' => 'settings.view', 'external' => true],
                ['label' => 'Club API', 'icon' => 'bi-code-slash', 'url' => '/api/v1/clubs?tenant_id=' . $tenantId, 'active' => 'api/v1/clubs', 'perm' => 'settings.view', 'external' => true],
                ['label' => 'Public API', 'icon' => 'bi-globe2', 'url' => '/api/public/v1/home', 'active' => 'api/public/v1/home', 'perm' => 'settings.view', 'external' => true],
            ],
        ],
        ['label' => 'Pricing Rules', 'icon' => 'bi-sliders2', 'url' => '/admin/pricing-rules', 'active' => 'admin/pricing-rules', 'perm' => 'pricing-rules.view'],
        ['label' => 'Xem public Venue', 'icon' => 'bi-layout-text-sidebar-reverse', 'url' => '/venues', 'active' => 'venues', 'perm' => null, 'external' => true],
        ['label' => 'Xem public Club', 'icon' => 'bi-people', 'url' => '/clubs', 'active' => 'clubs', 'perm' => null, 'external' => true],
    ],
];

$filteredMenu = [];
foreach ($menuGroups as $group => $items) {
    $groupItems = [];
    foreach ($items as $item) {
        $children = $item['children'] ?? [];
        $visibleChildren = [];
        foreach ($children as $child) {
            if ($canView($child['perm'] ?? null)) {
                $visibleChildren[] = $child;
            }
        }

        $isAllowed = $canView($item['perm'] ?? null) || ! empty($visibleChildren);
        if (! $isAllowed) {
            continue;
        }
        if (! empty($children)) {
            $item['children'] = $visibleChildren;
        }
        $groupItems[] = $item;
    }
    if (! empty($groupItems)) {
        $filteredMenu[$group] = $groupItems;
    }
}
?>

<aside class="erp-sidebar">
    <div class="erp-sidebar-brand">
        <div class="erp-brand-mark">PB</div>
        <div class="erp-brand-text">
            <div class="erp-brand-name"><?= esc(lang('App.app_name')) ?></div>
            <div class="erp-brand-tenant"><?= esc(session('tenant_name') ?? 'Pickleball Commerce') ?></div>
        </div>
    </div>
    <div class="erp-sidebar-scroll">
        <?php foreach ($filteredMenu as $group => $items): ?>
            <div class="erp-menu-group">
                <div class="erp-menu-title"><?= esc($group) ?></div>
                <?php foreach ($items as $item): ?>
                    <?php
                    $children = $item['children'] ?? [];
                    $isParentActive = is_active_route($item['active'] ?? '')
                        || (bool) array_filter($children, fn($child) => is_active_route($child['active'] ?? ''));
                    ?>
                    <div class="erp-menu-item">
                        <a
                            class="erp-menu-link <?= $isParentActive ? 'active' : '' ?> <?= $children ? 'erp-menu-parent' : '' ?>"
                            href="<?= esc($item['url']) ?>"
                            <?= ! empty($item['external']) ? 'target="_blank" rel="noopener"' : '' ?>
                        >
                            <i class="bi <?= esc($item['icon']) ?>"></i>
                            <span class="erp-menu-label"><?= esc($item['label']) ?></span>
                            <?php if (! empty($children)): ?>
                                <i class="bi bi-chevron-down erp-menu-chevron"></i>
                            <?php endif; ?>
                        </a>
                        <?php if (! empty($children)): ?>
                            <div class="erp-submenu <?= $isParentActive ? 'is-open' : '' ?>">
                                <?php foreach ($children as $child): ?>
                                    <a
                                        class="erp-menu-link <?= is_active_route($child['active']) ?>"
                                        href="<?= esc($child['url']) ?>"
                                        <?= ! empty($child['external']) ? 'target="_blank" rel="noopener"' : '' ?>
                                    >
                                        <i class="bi <?= esc($child['icon']) ?>"></i>
                                        <span class="erp-menu-label"><?= esc($child['label']) ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>

        <div class="erp-menu-group">
            <div class="erp-menu-title">Context</div>
            <div class="erp-menu-item">
                <a class="erp-menu-link" href="/admin/tenants/select">
                    <i class="bi bi-arrow-left-right"></i>
                    <span class="erp-menu-label">
                        <?= $tenantId ? ('Tenant #' . $tenantId) : 'Chọn Tenant' ?>
                        <?= $currentBranchId ? (' · Chi nhánh #' . $currentBranchId) : '' ?>
                    </span>
                </a>
            </div>
        </div>
    </div>
    <div class="erp-sidebar-footer">
        <span><?= esc(lang('App.app_name')) ?> v<?= esc(lang('App.version')) ?></span>
    </div>
</aside>

