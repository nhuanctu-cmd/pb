<?php
/**
 * Sidebar — menu lọc theo quyền RBAC của user hiện tại.
 * Mỗi item có key 'perm': chỉ hiển thị khi can(perm) = true.
 */
$allMenu = [
    lang('App.menu_group_operations') => [
        ['label' => lang('App.menu_dashboard'),     'icon' => 'bi-speedometer2',    'url' => '/admin/dashboard',     'active' => 'admin/dashboard',     'perm' => 'dashboard.view'],
        ['label' => lang('App.menu_bookings'),      'icon' => 'bi-calendar-check',  'url' => '/admin/bookings',      'active' => 'admin/bookings',      'perm' => 'bookings.view'],
        ['label' => lang('App.menu_pos'),           'icon' => 'bi-shop',            'url' => '/admin/pos',           'active' => 'admin/pos',           'perm' => 'pos.access'],
        ['label' => lang('App.menu_tournaments'),   'icon' => 'bi-trophy',          'url' => '/admin/tournaments',   'active' => 'admin/tournaments',   'perm' => 'tournaments.view'],
        ['label' => lang('App.menu_pricing_rules'), 'icon' => 'bi-cash-coin',       'url' => '/admin/pricing-rules', 'active' => 'admin/pricing-rules', 'perm' => 'pricing-rules.view'],
        ['label' => lang('App.menu_courts'),        'icon' => 'bi-grid-3x3-gap',    'url' => '/admin/courts',        'active' => 'admin/courts',        'perm' => 'courts.view'],
    ],
    lang('App.menu_group_customers') => [
        ['label' => lang('App.menu_players'),       'icon' => 'bi-person-vcard',    'url' => '/admin/players',       'active' => 'admin/players',       'perm' => 'players.view'],
        ['label' => lang('App.menu_memberships'),   'icon' => 'bi-award',           'url' => '/admin/memberships',   'active' => 'admin/memberships',   'perm' => 'memberships.view'],
        ['label' => lang('App.menu_clubs'),         'icon' => 'bi-building-heart',  'url' => '/admin/clubs',         'active' => 'admin/clubs',         'perm' => 'clubs.view'],
        ['label' => lang('App.menu_teams'),         'icon' => 'bi-people-fill',     'url' => '/admin/teams',         'active' => 'admin/teams',         'perm' => 'teams.view'],
        ['label' => lang('App.menu_matches'),       'icon' => 'bi-controller',      'url' => '/admin/matches',       'active' => 'admin/matches',       'perm' => 'matches.view'],
    ],
    lang('App.menu_group_admin') => [
        ['label' => lang('App.menu_facilities'),    'icon' => 'bi-buildings',       'url' => '/admin/facilities',    'active' => 'admin/facilities',    'perm' => 'facilities.view'],
        ['label' => lang('App.menu_branches'),      'icon' => 'bi-diagram-3',       'url' => '/admin/branches',      'active' => 'admin/branches',      'perm' => 'branches.view'],
        ['label' => lang('App.menu_plans'),         'icon' => 'bi-box-seam',        'url' => '/admin/plans',         'active' => 'admin/plans',         'perm' => 'plans.view'],
        ['label' => lang('App.menu_tenants'),       'icon' => 'bi-building-gear',   'url' => '/admin/tenants',       'active' => 'admin/tenants',       'perm' => 'tenants.view'],
        ['label' => lang('App.menu_users'),         'icon' => 'bi-people',          'url' => '/admin/users',         'active' => 'admin/users',         'perm' => 'users.view'],
        ['label' => lang('App.menu_roles'),         'icon' => 'bi-shield-lock',     'url' => '/admin/roles',         'active' => 'admin/roles',         'perm' => 'roles.view'],
        ['label' => lang('App.menu_payments'),      'icon' => 'bi-credit-card',     'url' => '/admin/payments',      'active' => 'admin/payments',      'perm' => 'payments.view'],
        ['label' => lang('App.menu_audit_logs'),    'icon' => 'bi-journal-text',    'url' => '/admin/audit-logs',    'active' => 'admin/audit-logs',    'perm' => 'audit-logs.view'],
        ['label' => lang('App.menu_settings'),      'icon' => 'bi-gear',            'url' => '/admin/settings',      'active' => 'admin/settings',      'perm' => 'settings.view'],
    ],
];

// Lọc item theo quyền + ẩn nhóm rỗng
$menu = [];
foreach ($allMenu as $group => $items) {
    $allowed = array_values(array_filter($items, fn ($item) => empty($item['perm']) || can($item['perm'])));
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
                <?php foreach ($items as $item): ?>
                    <a class="erp-menu-link <?= is_active_route($item['active']) ?>" href="<?= esc($item['url']) ?>">
                        <i class="bi <?= esc($item['icon']) ?>"></i>
                        <span class="erp-menu-label"><?= esc($item['label']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="erp-sidebar-footer">
        <span><?= esc(lang('App.app_name')) ?> v<?= esc(lang('App.version')) ?></span>
    </div>
</aside>
