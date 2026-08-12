<?php

/**
 * Sidebar — menu lọc theo quyền RBAC của user hiện tại.
 * Mỗi item có key 'perm': chỉ hiển thị khi can(perm) = true.
 */

$currentBranchId = (int) ($current_branch_id ?? 0);
$currentTenantId = (int) ($current_tenant_id ?? 0);
$currentUserId   = (int) (session('userId') ?? session('user_id') ?? 0);
$currentUri      = service('uri');
$currentPath = trim($currentUri->getPath(), '/');

$facilityRouteId = 0;
$branchRouteId   = 0;
$tenantRouteId   = $currentTenantId;
$userRouteId     = $currentUserId;
$teamRouteId     = 0;
$paymentInvoiceId = 0;
$customerRouteId = 0;
$playerRouteId = 0;
$clubRouteId = 0;
$membershipRouteId = 0;
$matchRouteId = 0;

if (preg_match('#admin/facilities/(?:dashboard|edit|update|delete|branches|clubs)/(\d+)#', $currentPath, $m)) {
    $facilityRouteId = (int) $m[1];
}
if (preg_match('#admin/branches/(?:edit|update|delete|hours|holidays|save-hours|store-holiday|delete-holiday)/(\d+)#', $currentPath, $m)) {
    $branchRouteId = (int) $m[1];
}
if (preg_match('#admin/tenants/(?:edit|update|delete|set-session)/(\d+)#', $currentPath, $m)) {
    $tenantRouteId = (int) $m[1];
}
if (preg_match('#admin/customers/show/(\d+)#', $currentPath, $m)) {
    $customerRouteId = (int) $m[1];
}
if (preg_match('#admin/players/(?:profile|edit|wallet|booking-history|match-history|delete)/(\d+)#', $currentPath, $m)) {
    $playerRouteId = (int) $m[1];
} elseif (preg_match('#admin/players/create$#', $currentPath)) {
    $playerRouteId = 0;
} else {
    $playerRouteId = 0;
}
if (preg_match('#admin/clubs/(?:edit|delete)/(\d+)#', $currentPath, $m)) {
    $clubRouteId = (int) $m[1];
}
if (preg_match('#admin/memberships/(?:cancel|edit-package|delete-package)/(\d+)#', $currentPath, $m)) {
    $membershipRouteId = (int) $m[1];
} elseif (preg_match('#admin/memberships/create-package#', $currentPath)) {
    $membershipRouteId = 0;
} else {
    $membershipRouteId = 0;
}
if (preg_match('#admin/matches/show/(\d+)#', $currentPath, $m)) {
    $matchRouteId = (int) $m[1];
}
if (preg_match('#admin/users/(?:edit|update|delete)/(\d+)#', $currentPath, $m)) {
    $userRouteId = (int) $m[1];
}
if (preg_match('#admin/teams/show/(\d+)#', $currentPath, $m)) {
    $teamRouteId = (int) $m[1];
}
if (preg_match('#admin/payments/detail/(\d+)#', $currentPath, $m)) {
    $paymentInvoiceId = (int) $m[1];
}

$facilityEditUrl       = $facilityRouteId ? '/admin/facilities/edit/' . $facilityRouteId : '/admin/facilities';
$facilityDashboardUrl   = $facilityRouteId ? '/admin/facilities/dashboard/' . $facilityRouteId : '/admin/facilities';
$facilityBranchesUrl    = $facilityRouteId ? '/admin/facilities/branches/' . $facilityRouteId : '/admin/branches';
$facilityClubsUrl       = $facilityRouteId ? '/admin/facilities/clubs/' . $facilityRouteId : '/admin/clubs';
$facilityDeleteUrl      = $facilityRouteId ? '/admin/facilities/delete/' . $facilityRouteId : '/admin/facilities';

$branchEditUrl          = $branchRouteId ? '/admin/branches/edit/' . $branchRouteId : '/admin/branches';
$activeBranchId         = $branchRouteId ?: $currentBranchId;
$branchCourtsUrl        = $activeBranchId ? '/admin/courts?branch_id=' . $activeBranchId : '/admin/courts';
$branchCourtsMaintenanceUrl = $activeBranchId ? '/admin/courts/maintenance/' . $activeBranchId : '/admin/courts';
$branchCourtsCalendarUrl = '/admin/courts/calendar' . ($activeBranchId ? ('?branch_id=' . $activeBranchId) : '');
$tenantSelectBranch     = $tenantRouteId ? '/admin/branches?tenant_id=' . $tenantRouteId : '/admin/branches';
$branchHoursUrl         = $activeBranchId ? '/admin/branches/hours/' . $activeBranchId : '/admin/branches';
$branchHolidaysUrl      = $activeBranchId ? '/admin/branches/holidays/' . $activeBranchId : '/admin/branches';

$tenantEditUrl          = $tenantRouteId ? '/admin/tenants/edit/' . $tenantRouteId : '/admin/tenants';
$tenantDeleteUrl        = $tenantRouteId ? '/admin/tenants/delete/' . $tenantRouteId : '/admin/tenants';
$tenantSessionUrl       = $tenantRouteId ? '/admin/tenants/set-session/' . $tenantRouteId : '/admin/tenants/select';

$userEditUrl            = $userRouteId ? '/admin/users/edit/' . $userRouteId : '/admin/users';
$userDeleteUrl          = $userRouteId ? '/admin/users/delete/' . $userRouteId : '/admin/users';

$teamDetailUrl          = $teamRouteId ? '/admin/teams/show/' . $teamRouteId : '/admin/teams';
$teamStatusActiveUrl    = '/admin/teams?status=active';
$teamStatusInactiveUrl  = '/admin/teams?status=inactive';
$teamStatusDisbandedUrl = '/admin/teams?status=disbanded';

$userProfileUrl         = '/admin/profile';

$customerDetailUrl      = $customerRouteId ? '/admin/customers/show/' . $customerRouteId : '/admin/customers';
$customerQuickBookingUrl = $customerRouteId ? '/admin/customers/create-booking/' . $customerRouteId : '/admin/customers';
$playerProfileUrl       = $playerRouteId ? '/admin/players/profile/' . $playerRouteId : '/admin/players';
$playerWalletUrl        = $playerRouteId ? '/admin/players/wallet/' . $playerRouteId : '/admin/players';
$playerBookingHistoryUrl = $playerRouteId ? '/admin/players/booking-history/' . $playerRouteId : '/admin/players';
$playerMatchHistoryUrl  = $playerRouteId ? '/admin/players/match-history/' . $playerRouteId : '/admin/players';
$playerEditUrl          = $playerRouteId ? '/admin/players/edit/' . $playerRouteId : '/admin/players';
$playerDeleteUrl        = $playerRouteId ? '/admin/players/delete/' . $playerRouteId : '/admin/players';
$clubEditUrl           = $clubRouteId ? '/admin/clubs/edit/' . $clubRouteId : '/admin/clubs';
$clubDeleteUrl         = $clubRouteId ? '/admin/clubs/delete/' . $clubRouteId : '/admin/clubs';
$membershipCancelUrl    = $membershipRouteId ? '/admin/memberships/cancel/' . $membershipRouteId : '/admin/memberships';
$membershipPackageEditUrl = $membershipRouteId ? '/admin/memberships/edit-package/' . $membershipRouteId : '/admin/memberships/packages';
$membershipPackageDeleteUrl = $membershipRouteId ? '/admin/memberships/delete-package/' . $membershipRouteId : '/admin/memberships/packages';
$matchDetailUrl         = $matchRouteId ? '/admin/matches/show/' . $matchRouteId : '/admin/matches';

$paymentInvoiceUrl      = $paymentInvoiceId ? '/admin/payments/detail/' . $paymentInvoiceId : '/admin/payments';

$allMenu = [
    lang('App.menu_group_operations') => [
        ['label' => lang('App.menu_dashboard'), 'icon' => 'bi-speedometer2', 'url' => '/admin/dashboard', 'active' => 'admin/dashboard', 'perm' => 'dashboard.view'],

        [
            'label' => 'Đặt sân & Booking',
            'icon'  => 'bi-calendar-check',
            'url'   => '/admin/bookings',
            'active'=> 'admin/bookings',
            'perm'  => 'bookings.view',
            'children' => [
                ['label' => 'Danh sách booking', 'icon' => 'bi-list-ul', 'url' => '/admin/bookings', 'active' => 'admin/bookings', 'perm' => 'bookings.view'],
                ['label' => 'Lịch đặt sân', 'icon' => 'bi-calendar3', 'url' => '/admin/bookings/calendar', 'active' => 'admin/bookings/calendar', 'perm' => 'bookings.view'],
                ['label' => 'Tạo booking mới', 'icon' => 'bi-plus-circle', 'url' => '/admin/bookings/create', 'active' => 'admin/bookings/create', 'perm' => 'bookings.view'],
            ],
        ],
        ['label' => lang('App.menu_recurring_bookings'), 'icon' => 'bi-arrow-repeat', 'url' => '/admin/recurring-bookings', 'active' => 'admin/recurring-bookings', 'perm' => 'bookings.view'],
        ['label' => lang('App.menu_waitlist'), 'icon' => 'bi-hourglass-split', 'url' => '/admin/waitlist', 'active' => 'admin/waitlist', 'perm' => 'bookings.view'],
        ['label' => lang('App.menu_walk_ins'), 'icon' => 'bi-person-walking', 'url' => '/admin/walk-ins', 'active' => 'admin/walk-ins', 'perm' => 'bookings.view'],
        ['label' => 'Front Desk · Quầy vận hành', 'icon' => 'bi-person-workspace', 'url' => '/admin/front-desk', 'active' => 'admin/front-desk', 'perm' => 'bookings.view'],
        ['label' => 'Daily Closing · Chốt ca', 'icon' => 'bi-cash-stack', 'url' => '/admin/daily-closing', 'active' => 'admin/daily-closing', 'perm' => 'payments.view'],
        ['label' => lang('App.menu_operations_report'), 'icon' => 'bi-bar-chart-line', 'url' => '/admin/operations-report', 'active' => 'admin/operations-report', 'perm' => 'dashboard.view'],

        [
            'label' => 'Rating & Match Governance',
            'icon'  => 'bi-star',
            'url'   => '/admin/rating',
            'active'=> 'admin/rating',
            'perm'  => 'rating.view',
            'children' => [
                ['label' => 'Rating Engine', 'icon' => 'bi-graph-up-arrow', 'url' => '/admin/rating', 'active' => 'admin/rating', 'perm' => 'rating.view'],
                ['label' => 'Match Governance', 'icon' => 'bi-gavel', 'url' => '/admin/governance', 'active' => 'admin/governance', 'perm' => 'rating.review'],
                ['label' => 'Data Quality', 'icon' => 'bi-shield-check', 'url' => '/admin/data-quality', 'active' => 'admin/data-quality', 'perm' => 'dashboard.view'],
                ['label' => 'Queue Monitoring', 'icon' => 'bi-list-check', 'url' => '/admin/queue', 'active' => 'admin/queue', 'perm' => 'dashboard.view'],
            ],
        ],
        ['label' => 'Nhập điểm', 'icon' => 'bi-keyboard', 'url' => '/admin/scores', 'active' => 'admin/scores', 'perm' => 'scores.view'],
        ['label' => lang('App.menu_open_play'), 'icon' => 'bi-people', 'url' => '/admin/open-play', 'active' => 'admin/open-play', 'perm' => 'bookings.view'],
        ['label' => lang('App.menu_coaching'), 'icon' => 'bi-person-video3', 'url' => '/admin/coaching', 'active' => 'admin/coaching', 'perm' => 'bookings.view'],

        [
            'label' => lang('App.menu_pos'),
            'icon'  => 'bi-shop',
            'url'   => '/admin/pos',
            'active'=> 'admin/pos',
            'perm'  => 'pos.access',
            'children' => [
                ['label' => 'POS Quầy', 'icon' => 'bi-shop', 'url' => '/admin/pos', 'active' => 'admin/pos', 'perm' => 'pos.access'],
                ['label' => 'Khởi tạo đơn mới', 'icon' => 'bi-receipt-cutoff', 'url' => '/admin/pos/counter', 'active' => 'admin/pos/counter', 'perm' => 'pos.access'],
                ['label' => 'Kho sản phẩm', 'icon' => 'bi-box-seam', 'url' => '/admin/pos/inventory', 'active' => 'admin/pos/inventory', 'perm' => 'pos.access'],
                ['label' => 'Nhật ký kho', 'icon' => 'bi-clock-history', 'url' => '/admin/pos/inventory/history', 'active' => 'admin/pos/inventory/history', 'perm' => 'pos.access'],
                ['label' => 'Điều chỉnh tồn kho', 'icon' => 'bi-arrow-repeat', 'url' => '/admin/pos/inventory', 'active' => 'admin/pos/inventory', 'perm' => 'pos.access'],
                ['label' => 'Nhập hàng vào kho', 'icon' => 'bi-box-arrow-in-down', 'url' => '/admin/pos/inventory', 'active' => 'admin/pos/inventory', 'perm' => 'pos.access'],
                ['label' => 'Tra cứu booking', 'icon' => 'bi-search', 'url' => '/admin/pos/searchBookings', 'active' => 'admin/pos/searchBookings', 'perm' => 'pos.access'],
                ['label' => 'Tra cứu player', 'icon' => 'bi-search', 'url' => '/admin/pos/searchPlayers', 'active' => 'admin/pos/searchPlayers', 'perm' => 'pos.access'],
            ],
        ],

        [
            'label' => lang('App.menu_tournaments'),
            'icon'  => 'bi-trophy',
            'url'   => '/admin/tournaments',
            'active'=> 'admin/tournaments',
            'perm'  => 'tournaments.view',
            'children' => [
                ['label' => 'Tổng quan giải', 'icon' => 'bi-grid-1x2', 'url' => '/admin/tournaments', 'active' => 'admin/tournaments', 'perm' => 'tournaments.view'],
                ['label' => 'Đăng ký & Check-in', 'icon' => 'bi-person-check', 'url' => '/admin/tournaments/registrations', 'active' => 'admin/tournaments/registrations', 'perm' => 'tournaments.view'],
                ['label' => 'Tạo giải', 'icon' => 'bi-plus-circle', 'url' => '/admin/tournaments/create', 'active' => 'admin/tournaments/create', 'perm' => 'tournaments.manage'],
                ['label' => 'Xếp lịch thi đấu', 'icon' => 'bi-calendar3', 'url' => '/admin/tournaments/scheduler', 'active' => 'admin/tournaments/scheduler', 'perm' => 'tournaments.manage'],
                ['label' => 'Cây đấu / Bracket', 'icon' => 'bi-diagram-3', 'url' => '/admin/tournaments/bracket', 'active' => 'admin/tournaments/bracket', 'perm' => 'tournaments.view'],
                ['label' => 'Control Room', 'icon' => 'bi-broadcast', 'url' => '/admin/tournaments/control-room', 'active' => 'admin/tournaments/control-room', 'perm' => 'tournaments.view'],
                ['label' => 'Print Center', 'icon' => 'bi-printer', 'url' => '/admin/print-center', 'active' => 'admin/print-center', 'perm' => 'tournaments.view'],
                ['label' => 'TV / LED Mode', 'icon' => 'bi-display', 'url' => '/live-scores/tv', 'active' => 'live-scores', 'perm' => 'tournaments.view', 'external' => true],
                ['label' => 'Tournament Templates', 'icon' => 'bi-copy', 'url' => '/admin/tournament-templates', 'active' => 'admin/tournament-templates', 'perm' => 'tournaments.view'],
            ],
        ],

        [
            'label' => lang('App.menu_competitions'),
            'icon'  => 'bi-bar-chart-steps',
            'url'   => '/admin/competitions',
            'active'=> 'admin/competitions',
            'perm'  => 'tournaments.view',
            'children' => [
                ['label' => 'Danh sách competitions', 'icon' => 'bi-bar-chart-steps', 'url' => '/admin/competitions', 'active' => 'admin/competitions', 'perm' => 'tournaments.view'],
                ['label' => 'Điểm đấu đối kháng', 'icon' => 'bi-trophy', 'url' => '/admin/competitions', 'active' => 'admin/competitions', 'perm' => 'tournaments.manage'],
            ],
        ],
        ['label' => lang('App.menu_ai_scheduling'), 'icon' => 'bi-stars', 'url' => '/admin/ai-scheduling', 'active' => 'admin/ai-scheduling', 'perm' => 'tournaments.manage'],

        [
            'label' => lang('App.menu_livestream'),
            'icon'  => 'bi-broadcast-pin',
            'url'   => '/admin/livestream',
            'active'=> 'admin/livestream',
            'perm'  => 'tournaments.manage',
            'children' => [
                ['label' => 'Kênh phát sóng', 'icon' => 'bi-broadcast-pin', 'url' => '/admin/livestream', 'active' => 'admin/livestream', 'perm' => 'tournaments.manage'],
                ['label' => 'Tương tác kênh', 'icon' => 'bi-sliders2', 'url' => '/admin/livestream', 'active' => 'admin/livestream', 'perm' => 'tournaments.manage'],
            ],
        ],

        ['label' => lang('App.menu_growth'), 'icon' => 'bi-megaphone', 'url' => '/admin/growth', 'active' => 'admin/growth', 'perm' => 'players.view'],
        ['label' => 'CRM Campaign', 'icon' => 'bi-megaphone-fill', 'url' => '/admin/crm-campaigns', 'active' => 'admin/crm-campaigns', 'perm' => 'players.view'],

        [
            'label' => lang('App.menu_pricing_rules'),
            'icon'  => 'bi-cash-coin',
            'url'   => '/admin/pricing-rules',
            'active'=> 'admin/pricing-rules',
            'perm'  => 'pricing-rules.view',
            'children' => [
                ['label' => 'Danh sách quy tắc', 'icon' => 'bi-cash-coin', 'url' => '/admin/pricing-rules', 'active' => 'admin/pricing-rules', 'perm' => 'pricing-rules.view'],
                ['label' => 'Tạo quy tắc', 'icon' => 'bi-plus-circle', 'url' => '/admin/pricing-rules/create', 'active' => 'admin/pricing-rules/create', 'perm' => 'pricing-rules.view'],
                ['label' => 'Kiểm thử', 'icon' => 'bi-speedometer', 'url' => '/admin/pricing-rules/test', 'active' => 'admin/pricing-rules/test', 'perm' => 'pricing-rules.view'],
            ],
        ],

        [
            'label' => lang('App.menu_courts'),
            'icon'  => 'bi-grid-3x3-gap',
            'url'   => '/admin/courts',
            'active'=> 'admin/courts',
            'perm'  => 'courts.view',
            'children' => [
                ['label' => 'Danh sách sân', 'icon' => 'bi-grid-3x3-gap', 'url' => '/admin/courts', 'active' => 'admin/courts', 'perm' => 'courts.view'],
                ['label' => 'Tạo sân', 'icon' => 'bi-plus-circle', 'url' => '/admin/courts/create', 'active' => 'admin/courts/create', 'perm' => 'courts.view'],
                ['label' => 'Lịch sân', 'icon' => 'bi-calendar3', 'url' => '/admin/courts/calendar', 'active' => 'admin/courts/calendar', 'perm' => 'courts.view'],
                ['label' => 'Bảo trì sân', 'icon' => 'bi-tools', 'url' => '/admin/courts', 'active' => 'admin/courts', 'perm' => 'courts.view'],
            ],
        ],
    ],
    lang('App.menu_group_customers') => [
        [
            'label' => lang('App.menu_customers'),
            'icon'  => 'bi-person-lines-fill',
            'url'   => '/admin/customers',
            'active'=> 'admin/customers',
            'perm'  => 'players.view',
            'children' => [
                ['label' => 'Danh sách khách hàng', 'icon' => 'bi-person-lines-fill', 'url' => '/admin/customers', 'active' => 'admin/customers', 'perm' => 'players.view'],
                ['label' => 'Chi tiết khách hàng', 'icon' => 'bi-person-vcard', 'url' => $customerDetailUrl, 'active' => 'admin/customers/show', 'perm' => 'players.view'],
                ['label' => 'Khách hàng hoạt động', 'icon' => 'bi-check2-circle', 'url' => '/admin/customers?status=active', 'active' => 'admin/customers', 'perm' => 'players.view'],
                ['label' => 'Khách hàng tạm khóa', 'icon' => 'bi-lock', 'url' => '/admin/customers?status=inactive', 'active' => 'admin/customers', 'perm' => 'players.view'],
                ['label' => 'Khách hàng đã gắn player', 'icon' => 'bi-link-45deg', 'url' => '/admin/customers?has_player=1', 'active' => 'admin/customers', 'perm' => 'players.view'],
                ['label' => 'Tạo booking nhanh', 'icon' => 'bi-plus-square', 'url' => $customerQuickBookingUrl, 'active' => 'admin/bookings/create', 'perm' => 'players.view'],
            ],
        ],
        [
            'label' => lang('App.menu_players'),
            'icon'  => 'bi-person-vcard',
            'url'   => '/admin/players',
            'active'=> 'admin/players',
            'perm'  => 'players.view',
            'children' => [
                ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'url' => '/admin/players/dashboard', 'active' => 'admin/players/dashboard', 'perm' => 'players.view'],
                ['label' => 'Xếp hạng', 'icon' => 'bi-trophy', 'url' => '/admin/players/ranking', 'active' => 'admin/players/ranking', 'perm' => 'players.view'],
                ['label' => 'Danh sách players', 'icon' => 'bi-person-vcard', 'url' => '/admin/players', 'active' => 'admin/players', 'perm' => 'players.view'],
                ['label' => 'Tạo player mới', 'icon' => 'bi-plus-circle', 'url' => '/admin/players/create', 'active' => 'admin/players/create', 'perm' => 'players.view'],
                ['label' => 'Hồ sơ player đang chọn', 'icon' => 'bi-person-badge', 'url' => $playerProfileUrl, 'active' => 'admin/players/profile', 'perm' => 'players.view'],
                ['label' => 'Ví player đang chọn', 'icon' => 'bi-wallet2', 'url' => $playerWalletUrl, 'active' => 'admin/players/wallet', 'perm' => 'players.view'],
                ['label' => 'Lịch sử booking', 'icon' => 'bi-journal-text', 'url' => $playerBookingHistoryUrl, 'active' => 'admin/players/booking-history', 'perm' => 'players.view'],
                ['label' => 'Lịch sử trận đấu', 'icon' => 'bi-controller', 'url' => $playerMatchHistoryUrl, 'active' => 'admin/players/match-history', 'perm' => 'players.view'],
                ['label' => 'Player hoạt động', 'icon' => 'bi-check2-circle', 'url' => '/admin/players?status=active', 'active' => 'admin/players', 'perm' => 'players.view'],
                ['label' => 'Player chờ duyệt', 'icon' => 'bi-hourglass-split', 'url' => '/admin/players?status=pending', 'active' => 'admin/players', 'perm' => 'players.view'],
                ['label' => 'Sửa player đang chọn', 'icon' => 'bi-pencil-square', 'url' => $playerEditUrl, 'active' => 'admin/players/edit', 'perm' => 'players.view'],
                ['label' => 'Xóa player đang chọn', 'icon' => 'bi-trash', 'url' => $playerDeleteUrl, 'active' => 'admin/players/delete', 'perm' => 'players.view'],
            ],
        ],
        [
            'label' => lang('App.menu_memberships'),
            'icon'  => 'bi-award',
            'url'   => '/admin/memberships',
            'active'=> 'admin/memberships',
            'perm'  => 'memberships.view',
            'children' => [
                ['label' => 'Danh sách hội viên', 'icon' => 'bi-award', 'url' => '/admin/memberships', 'active' => 'admin/memberships', 'perm' => 'memberships.view'],
                ['label' => 'Danh sách cần gia hạn', 'icon' => 'bi-arrow-repeat', 'url' => '/admin/memberships/renewals', 'active' => 'admin/memberships/renewals', 'perm' => 'memberships.view'],
                ['label' => 'Tạo hội viên', 'icon' => 'bi-person-plus', 'url' => '/admin/memberships/create', 'active' => 'admin/memberships/create', 'perm' => 'memberships.view'],
                ['label' => 'Hủy hội viên đang chọn', 'icon' => 'bi-x-lg', 'url' => $membershipCancelUrl, 'active' => 'admin/memberships/cancel', 'perm' => 'memberships.view'],
                ['label' => 'Gói hội viên', 'icon' => 'bi-bag-check', 'url' => '/admin/memberships/packages', 'active' => 'admin/memberships/packages', 'perm' => 'memberships.view'],
                ['label' => 'Tạo gói hội viên', 'icon' => 'bi-plus-circle', 'url' => '/admin/memberships/create-package', 'active' => 'admin/memberships/create-package', 'perm' => 'memberships.view'],
                ['label' => 'Sửa gói hội viên', 'icon' => 'bi-pencil-square', 'url' => $membershipPackageEditUrl, 'active' => 'admin/memberships/edit-package', 'perm' => 'memberships.view'],
                ['label' => 'Xóa gói hội viên', 'icon' => 'bi-trash', 'url' => $membershipPackageDeleteUrl, 'active' => 'admin/memberships/delete-package', 'perm' => 'memberships.view'],
            ],
        ],
        [
            'label' => lang('App.menu_clubs'),
            'icon'  => 'bi-building-heart',
            'url'   => '/admin/clubs',
            'active'=> 'admin/clubs',
            'perm'  => 'clubs.view',
            'children' => [
                ['label' => 'Danh sách Câu lạc bộ', 'icon' => 'bi-building-heart', 'url' => '/admin/clubs', 'active' => 'admin/clubs', 'perm' => 'clubs.view'],
                ['label' => 'Tạo Câu lạc bộ', 'icon' => 'bi-plus-circle', 'url' => '/admin/clubs/create', 'active' => 'admin/clubs/create', 'perm' => 'clubs.view'],
                ['label' => 'Sửa Câu lạc bộ đang chọn', 'icon' => 'bi-pencil-square', 'url' => $clubEditUrl, 'active' => 'admin/clubs/edit', 'perm' => 'clubs.view'],
                ['label' => 'Xóa Câu lạc bộ đang chọn', 'icon' => 'bi-trash', 'url' => $clubDeleteUrl, 'active' => 'admin/clubs/delete', 'perm' => 'clubs.view'],
                ['label' => 'Câu lạc bộ hoạt động', 'icon' => 'bi-check2-circle', 'url' => '/admin/clubs?status=active', 'active' => 'admin/clubs', 'perm' => 'clubs.view'],
                ['label' => 'Câu lạc bộ tạm ngưng', 'icon' => 'bi-pause-circle', 'url' => '/admin/clubs?status=inactive', 'active' => 'admin/clubs', 'perm' => 'clubs.view'],
            ],
        ],
        [
            'label' => lang('App.menu_teams'),
            'icon'  => 'bi-people-fill',
            'url'   => '/admin/teams',
            'active'=> 'admin/teams',
            'perm'  => 'teams.view',
            'children' => [
                ['label' => 'Danh sách đội', 'icon' => 'bi-people-fill', 'url' => '/admin/teams', 'active' => 'admin/teams', 'perm' => 'teams.view'],
                ['label' => 'Chi tiết đội đang chọn', 'icon' => 'bi-people', 'url' => $teamDetailUrl, 'active' => 'admin/teams/show', 'perm' => 'teams.view'],
                ['label' => 'Đội hoạt động', 'icon' => 'bi-check2-circle', 'url' => $teamStatusActiveUrl, 'active' => 'admin/teams', 'perm' => 'teams.view'],
                ['label' => 'Đội tạm ngừng', 'icon' => 'bi-pause-circle', 'url' => $teamStatusInactiveUrl, 'active' => 'admin/teams', 'perm' => 'teams.view'],
                ['label' => 'Đội giải tán', 'icon' => 'bi-dash-circle', 'url' => $teamStatusDisbandedUrl, 'active' => 'admin/teams', 'perm' => 'teams.view'],
                ['label' => 'Lập đội mới', 'icon' => 'bi-person-plus', 'url' => '/admin/teams/create', 'active' => 'admin/teams/create', 'perm' => 'teams.view'],
            ],
        ],
        [
            'label' => lang('App.menu_matches'),
            'icon'  => 'bi-controller',
            'url'   => '/admin/matches',
            'active'=> 'admin/matches',
            'perm'  => 'matches.view',
            'children' => [
                ['label' => 'Danh sách yêu cầu', 'icon' => 'bi-controller', 'url' => '/admin/matches', 'active' => 'admin/matches', 'perm' => 'matches.view'],
                ['label' => 'Kèo đang mở', 'icon' => 'bi-unlock', 'url' => '/admin/matches?status=open', 'active' => 'admin/matches', 'perm' => 'matches.view'],
                ['label' => 'Kèo đã ghép', 'icon' => 'bi-link', 'url' => '/admin/matches?status=matched', 'active' => 'admin/matches', 'perm' => 'matches.view'],
                ['label' => 'Kèo đã hủy', 'icon' => 'bi-x-lg', 'url' => '/admin/matches?status=cancelled', 'active' => 'admin/matches', 'perm' => 'matches.view'],
                ['label' => 'Chi tiết kèo đang chọn', 'icon' => 'bi-search', 'url' => $matchDetailUrl, 'active' => 'admin/matches/show', 'perm' => 'matches.view'],
            ],
        ],
    ],
    lang('App.menu_group_admin') => [
        [
            'label' => lang('App.menu_facilities'),
            'icon'  => 'bi-buildings',
            'url'   => '/admin/facilities',
            'active'=> 'admin/facilities',
            'perm'  => 'facilities.view',
            'children' => [
                ['label' => 'Danh sách cụm sân', 'icon' => 'bi-buildings', 'url' => '/admin/facilities', 'active' => 'admin/facilities', 'perm' => 'facilities.view'],
                ['label' => 'Tạo cụm sân mới', 'icon' => 'bi-plus-circle', 'url' => '/admin/facilities/create', 'active' => 'admin/facilities/create', 'perm' => 'facilities.view'],
                ['label' => 'Xem chi tiết cơ sở', 'icon' => 'bi-grid-3x3-gap', 'url' => $facilityDashboardUrl, 'active' => 'admin/facilities/dashboard', 'perm' => 'facilities.view'],
                ['label' => 'Sửa cơ sở đang chọn', 'icon' => 'bi-pencil-square', 'url' => $facilityEditUrl, 'active' => 'admin/facilities/edit', 'perm' => 'facilities.view'],
                ['label' => 'Xóa cơ sở đang chọn', 'icon' => 'bi-trash', 'url' => $facilityDeleteUrl, 'active' => 'admin/facilities/delete', 'perm' => 'facilities.view'],
                ['label' => 'Chi nhánh của cơ sở', 'icon' => 'bi-diagram-3', 'url' => $facilityBranchesUrl, 'active' => 'admin/facilities/branches', 'perm' => 'facilities.view'],
                ['label' => 'Quản lý CLB theo cơ sở', 'icon' => 'bi-building-heart', 'url' => $facilityClubsUrl, 'active' => 'admin/facilities/clubs', 'perm' => 'facilities.view'],
                ['label' => 'Sân theo cụm', 'icon' => 'bi-grid-3x3-gap', 'url' => '/admin/courts', 'active' => 'admin/courts', 'perm' => 'courts.view'],
                ['label' => 'Cơ sở đang hoạt động', 'icon' => 'bi-check2-circle', 'url' => '/admin/facilities?status=active', 'active' => 'admin/facilities', 'perm' => 'facilities.view'],
                ['label' => 'Cơ sở ngưng hoạt động', 'icon' => 'bi-x-lg', 'url' => '/admin/facilities?status=inactive', 'active' => 'admin/facilities', 'perm' => 'facilities.view'],
                ['label' => 'Cơ sở tạm dừng', 'icon' => 'bi-pause-circle', 'url' => '/admin/facilities?status=suspended', 'active' => 'admin/facilities', 'perm' => 'facilities.view'],
            ],
        ],
        [
            'label' => lang('App.menu_branches'),
            'icon'  => 'bi-diagram-3',
            'url'   => '/admin/branches',
            'active'=> 'admin/branches',
            'perm'  => 'branches.view',
            'children' => [
                ['label' => 'Danh sách chi nhánh', 'icon' => 'bi-diagram-3', 'url' => '/admin/branches', 'active' => 'admin/branches', 'perm' => 'branches.view'],
                ['label' => 'Tạo chi nhánh', 'icon' => 'bi-plus-circle', 'url' => '/admin/branches/create', 'active' => 'admin/branches/create', 'perm' => 'branches.view'],
                ['label' => 'Sửa chi nhánh đang chọn', 'icon' => 'bi-pencil-square', 'url' => $branchEditUrl, 'active' => 'admin/branches/edit', 'perm' => 'branches.view'],
                ['label' => 'Sân của chi nhánh', 'icon' => 'bi-grid-3x3-gap', 'url' => $branchCourtsUrl, 'active' => 'admin/courts', 'perm' => 'courts.view'],
                ['label' => 'Lịch sân chi nhánh', 'icon' => 'bi-calendar3', 'url' => $branchCourtsCalendarUrl, 'active' => 'admin/courts/calendar', 'perm' => 'courts.view'],
                ['label' => 'Bảo trì sân chi nhánh', 'icon' => 'bi-tools', 'url' => $branchCourtsMaintenanceUrl, 'active' => 'admin/courts', 'perm' => 'courts.view'],
                ['label' => 'Giờ mở cửa', 'icon' => 'bi-clock', 'url' => $branchHoursUrl, 'active' => 'admin/branches/hours', 'perm' => 'branches.view'],
                ['label' => 'Ngày lễ đặc biệt', 'icon' => 'bi-calendar2-event', 'url' => $branchHolidaysUrl, 'active' => 'admin/branches/holidays', 'perm' => 'branches.view'],
                ['label' => 'Chi nhánh đang hoạt động', 'icon' => 'bi-check2-circle', 'url' => '/admin/branches?status=active', 'active' => 'admin/branches', 'perm' => 'branches.view'],
                ['label' => 'Chi nhánh bảo trì', 'icon' => 'bi-tools', 'url' => '/admin/branches?status=maintenance', 'active' => 'admin/branches', 'perm' => 'branches.view'],
                ['label' => 'Chi nhánh đang tạm đóng', 'icon' => 'bi-lock', 'url' => '/admin/branches?status=closed', 'active' => 'admin/branches', 'perm' => 'branches.view'],
                ['label' => 'Chi nhánh theo tenant', 'icon' => 'bi-funnel', 'url' => $tenantSelectBranch, 'active' => 'admin/branches', 'perm' => 'branches.view'],
            ],
        ],
        [
            'label' => lang('App.menu_tenants'),
            'icon'  => 'bi-building-gear',
            'url'   => '/admin/tenants',
            'active'=> 'admin/tenants',
            'perm'  => 'tenants.view',
            'children' => [
                ['label' => 'Danh sách tenant', 'icon' => 'bi-building-gear', 'url' => '/admin/tenants', 'active' => 'admin/tenants', 'perm' => 'tenants.view'],
                ['label' => 'Tạo tenant', 'icon' => 'bi-plus-circle', 'url' => '/admin/tenants/create', 'active' => 'admin/tenants/create', 'perm' => 'tenants.view'],
                ['label' => 'Chọn tenant hoạt động', 'icon' => 'bi-arrow-repeat', 'url' => '/admin/tenants/select', 'active' => 'admin/tenants/select', 'perm' => 'tenants.view'],
                ['label' => 'Sửa tenant đang chọn', 'icon' => 'bi-pencil-square', 'url' => $tenantEditUrl, 'active' => 'admin/tenants/edit', 'perm' => 'tenants.view'],
                ['label' => 'Xóa tenant đang chọn', 'icon' => 'bi-trash', 'url' => $tenantDeleteUrl, 'active' => 'admin/tenants/delete', 'perm' => 'tenants.view'],
                ['label' => 'Thiết lập ngữ cảnh tenant', 'icon' => 'bi-display', 'url' => $tenantSessionUrl, 'active' => 'admin/tenants/set-session', 'perm' => 'tenants.view'],
                ['label' => 'Tenant đang hoạt động', 'icon' => 'bi-check2-circle', 'url' => '/admin/tenants?status=active', 'active' => 'admin/tenants', 'perm' => 'tenants.view'],
                ['label' => 'Tenant ngưng hoạt động', 'icon' => 'bi-x-lg', 'url' => '/admin/tenants?status=inactive', 'active' => 'admin/tenants', 'perm' => 'tenants.view'],
                ['label' => 'Tenant tạm ngưng', 'icon' => 'bi-pause-circle', 'url' => '/admin/tenants?status=suspended', 'active' => 'admin/tenants', 'perm' => 'tenants.view'],
            ],
        ],
        [
            'label' => lang('App.menu_users'),
            'icon'  => 'bi-people',
            'url'   => '/admin/users',
            'active'=> 'admin/users',
            'perm'  => 'users.view',
            'children' => [
                ['label' => 'Danh sách người dùng', 'icon' => 'bi-people', 'url' => '/admin/users', 'active' => 'admin/users', 'perm' => 'users.view'],
                ['label' => 'Tạo người dùng', 'icon' => 'bi-person-plus', 'url' => '/admin/users/create', 'active' => 'admin/users/create', 'perm' => 'users.view'],
                ['label' => 'Sửa người dùng đang chọn', 'icon' => 'bi-pencil-square', 'url' => $userEditUrl, 'active' => 'admin/users/edit', 'perm' => 'users.view'],
                ['label' => 'Xóa người dùng đang chọn', 'icon' => 'bi-trash', 'url' => $userDeleteUrl, 'active' => 'admin/users/delete', 'perm' => 'users.view'],
                ['label' => 'Hồ sơ của bạn', 'icon' => 'bi-person-gear', 'url' => $userProfileUrl, 'active' => 'admin/profile', 'perm' => 'users.view'],
                ['label' => 'User đang hoạt động', 'icon' => 'bi-check2-circle', 'url' => '/admin/users?status=active', 'active' => 'admin/users', 'perm' => 'users.view'],
                ['label' => 'User tạm khóa', 'icon' => 'bi-lock', 'url' => '/admin/users?status=inactive', 'active' => 'admin/users', 'perm' => 'users.view'],
            ],
        ],
        [
            'label' => lang('App.menu_roles'),
            'icon'  => 'bi-shield-lock',
            'url'   => '/admin/roles',
            'active'=> 'admin/roles',
            'perm'  => 'roles.view',
            'children' => [
                ['label' => 'Danh sách vai trò', 'icon' => 'bi-shield-lock', 'url' => '/admin/roles', 'active' => 'admin/roles', 'perm' => 'roles.view'],
                ['label' => 'Tạo vai trò', 'icon' => 'bi-plus-circle', 'url' => '/admin/roles/create', 'active' => 'admin/roles/create', 'perm' => 'roles.view'],
                ['label' => 'Phân quyền vai trò', 'icon' => 'bi-key', 'url' => '/admin/roles', 'active' => 'admin/roles', 'perm' => 'roles.view'],
            ],
        ],
        [
            'label' => lang('App.menu_plans'),
            'icon'  => 'bi-layers',
            'url'   => '/admin/plans',
            'active'=> 'admin/plans',
            'perm'  => 'plans.view',
            'children' => [
                ['label' => 'Gói dịch vụ', 'icon' => 'bi-layers', 'url' => '/admin/plans', 'active' => 'admin/plans', 'perm' => 'plans.view'],
                ['label' => 'Danh sách nâng cấp', 'icon' => 'bi-arrow-up-circle', 'url' => '/admin/plans', 'active' => 'admin/plans', 'perm' => 'plans.view'],
            ],
        ],
        [
            'label' => lang('App.menu_payments'),
            'icon'  => 'bi-credit-card',
            'url'   => '/admin/payments',
            'active'=> 'admin/payments',
            'perm'  => 'payments.view',
            'children' => [
                ['label' => 'Danh sách thanh toán', 'icon' => 'bi-credit-card', 'url' => '/admin/payments', 'active' => 'admin/payments', 'perm' => 'payments.view'],
                ['label' => 'Chi tiết hóa đơn đang chọn', 'icon' => 'bi-receipt', 'url' => $paymentInvoiceUrl, 'active' => 'admin/payments/detail', 'perm' => 'payments.view'],
                ['label' => 'Chờ thanh toán', 'icon' => 'bi-hourglass-split', 'url' => '/admin/payments?status=unpaid', 'active' => 'admin/payments', 'perm' => 'payments.view'],
                ['label' => 'Thanh toán một phần', 'icon' => 'bi-wallet2', 'url' => '/admin/payments?status=partial', 'active' => 'admin/payments', 'perm' => 'payments.view'],
                ['label' => 'Đã thanh toán', 'icon' => 'bi-check2-all', 'url' => '/admin/payments?status=paid', 'active' => 'admin/payments', 'perm' => 'payments.view'],
                ['label' => 'Đã hoàn tiền', 'icon' => 'bi-arrow-repeat', 'url' => '/admin/payments?status=refunded', 'active' => 'admin/payments', 'perm' => 'payments.view'],
                ['label' => 'Đã hủy', 'icon' => 'bi-x-lg', 'url' => '/admin/payments?status=cancelled', 'active' => 'admin/payments', 'perm' => 'payments.view'],
                ['label' => 'Cấu hình QR', 'icon' => 'bi-qr-code', 'url' => '/admin/payments/qr-config', 'active' => 'admin/payments/qr-config', 'perm' => 'payments.view'],
            ],
        ],
        [
            'label' => lang('App.menu_media'),
            'icon'  => 'bi-images',
            'url'   => '/admin/media',
            'active'=> 'admin/media',
            'perm'  => 'media.view',
            'children' => [
                ['label' => 'Kho media', 'icon' => 'bi-images', 'url' => '/admin/media', 'active' => 'admin/media', 'perm' => 'media.view'],
            ],
        ],
        [
            'label' => lang('App.menu_notifications'),
            'icon'  => 'bi-bell',
            'url'   => '/admin/notifications',
            'active'=> 'admin/notifications',
            'perm'  => 'notifications.view',
            'children' => [
                ['label' => 'Danh sách thông báo', 'icon' => 'bi-bell', 'url' => '/admin/notifications', 'active' => 'admin/notifications', 'perm' => 'notifications.view'],
                ['label' => 'Thông báo chưa đọc', 'icon' => 'bi-bell-fill', 'url' => '/admin/notifications/unread', 'active' => 'admin/notifications/unread', 'perm' => 'notifications.view'],
            ],
        ],
        [
            'label' => lang('App.menu_audit_logs'),
            'icon'  => 'bi-journal-text',
            'url'   => '/admin/audit-logs',
            'active'=> 'admin/audit-logs',
            'perm'  => 'audit-logs.view',
            'children' => [
                ['label' => 'Lịch sử thao tác', 'icon' => 'bi-journal-text', 'url' => '/admin/audit-logs', 'active' => 'admin/audit-logs', 'perm' => 'audit-logs.view'],
            ],
        ],
        [
            'label' => lang('App.menu_settings'),
            'icon'  => 'bi-gear',
            'url'   => '/admin/settings',
            'active'=> 'admin/settings',
            'perm'  => 'settings.view',
            'children' => [
                ['label' => 'Thiết lập chung', 'icon' => 'bi-gear', 'url' => '/admin/settings', 'active' => 'admin/settings', 'perm' => 'settings.view'],
                ['label' => 'Webhooks', 'icon' => 'bi-link-45deg', 'url' => '/admin/webhooks', 'active' => 'admin/webhooks', 'perm' => 'settings.view'],
                ['label' => 'API đối tác', 'icon' => 'bi-key', 'url' => '/admin/integrations', 'active' => 'admin/integrations', 'perm' => 'settings.view'],
            ],
        ],
    ],
];

$menu = [];
foreach ($allMenu as $group => $items) {
    $allowed = array_values(array_filter($items, fn($item) => empty($item['perm']) || can($item['perm'])));
    if (! empty($allowed)) {
        $menu[$group] = $allowed;
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
        <?php foreach ($menu as $group => $items): ?>
            <div class="erp-menu-group">
                <div class="erp-menu-title"><?= esc($group) ?></div>
                <?php foreach ($items as $item):
                    $children = array_values(array_filter($item['children'] ?? [], fn($child) => empty($child['perm']) || can($child['perm'])));
                    $isParentActive = is_active_route($item['active']) || (bool) array_filter($children, fn ($child) => is_active_route($child['active']));
                ?>
                    <div class="erp-menu-item">
                    <a
                        class="erp-menu-link <?= $isParentActive ? 'active' : '' ?> <?= $children ? 'erp-menu-parent' : '' ?>"
                        href="<?= esc($item['url']) ?>"
                        <?= ! empty($item['external']) ? 'target="_blank" rel="noopener"' : '' ?>
                    >
                        <i class="bi <?= esc($item['icon']) ?>"></i>
                        <span class="erp-menu-label"><?= esc($item['label']) ?></span>
                        <?php if ($children): ?>
                            <i class="bi bi-chevron-down erp-menu-chevron"></i>
                        <?php endif; ?>
                    </a>
                    <?php if ($children): ?>
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
    </div>
    <div class="erp-sidebar-footer">
        <span><?= esc(lang('App.app_name')) ?> v<?= esc(lang('App.version')) ?></span>
    </div>
</aside>
