<?php
$menu = $menu ?? [
    lang('App.menu_group_operations') => [
        ['label' => lang('App.menu_dashboard'),     'icon' => 'bi-speedometer2',    'url' => '/admin/dashboard',     'active' => 'admin/dashboard'],
        ['label' => lang('App.menu_bookings'),      'icon' => 'bi-calendar-check',  'url' => '/admin/bookings',      'active' => 'admin/bookings'],
        ['label' => lang('App.menu_pos'),           'icon' => 'bi-shop',            'url' => '/admin/pos',           'active' => 'admin/pos'],
        ['label' => lang('App.menu_tournaments'),   'icon' => 'bi-trophy',          'url' => '/admin/tournaments',   'active' => 'admin/tournaments'],
        ['label' => lang('App.menu_pricing_rules'), 'icon' => 'bi-cash-coin',       'url' => '/admin/pricing-rules', 'active' => 'admin/pricing-rules'],
        ['label' => lang('App.menu_courts'),        'icon' => 'bi-grid-3x3-gap',    'url' => '/admin/courts',        'active' => 'admin/courts'],
    ],
    lang('App.menu_group_customers') => [
        ['label' => lang('App.menu_players'),       'icon' => 'bi-person-vcard',    'url' => '/admin/players',       'active' => 'admin/players'],
        ['label' => lang('App.menu_memberships'),   'icon' => 'bi-award',           'url' => '/admin/memberships',   'active' => 'admin/memberships'],
        ['label' => lang('App.menu_clubs'),         'icon' => 'bi-building-heart',  'url' => '/admin/clubs',         'active' => 'admin/clubs'],
        ['label' => lang('App.menu_teams'),         'icon' => 'bi-people-fill',     'url' => '/admin/teams',         'active' => 'admin/teams'],
        ['label' => lang('App.menu_matches'),       'icon' => 'bi-controller',      'url' => '/admin/matches',       'active' => 'admin/matches'],
    ],
    lang('App.menu_group_admin') => [
        ['label' => lang('App.menu_facilities'),    'icon' => 'bi-buildings',       'url' => '/admin/facilities',    'active' => 'admin/facilities'],
        ['label' => lang('App.menu_branches'),      'icon' => 'bi-diagram-3',       'url' => '/admin/branches',      'active' => 'admin/branches'],
        ['label' => lang('App.menu_tenants'),       'icon' => 'bi-building-gear',   'url' => '/admin/tenants',       'active' => 'admin/tenants'],
        ['label' => lang('App.menu_users'),         'icon' => 'bi-people',          'url' => '/admin/users',         'active' => 'admin/users'],
        ['label' => lang('App.menu_roles'),         'icon' => 'bi-shield-lock',     'url' => '/admin/roles',         'active' => 'admin/roles'],
        ['label' => lang('App.menu_payments'),      'icon' => 'bi-credit-card',     'url' => '/admin/payments',      'active' => 'admin/payments'],
        ['label' => lang('App.menu_audit_logs'),    'icon' => 'bi-journal-text',    'url' => '/admin/audit-logs',    'active' => 'admin/audit-logs'],
        ['label' => lang('App.menu_settings'),      'icon' => 'bi-gear',            'url' => '/admin/settings',      'active' => 'admin/settings'],
    ],
];
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
                        <?php if (! empty($item['badge'])): ?><span class="badge text-bg-primary"><?= esc($item['badge']) ?></span><?php endif; ?>
                    </a>
                    <?php if (! empty($item['children'])): ?>
                        <div class="erp-submenu">
                            <?php foreach ($item['children'] as $child): ?>
                                <a class="erp-menu-link <?= is_active_route($child['active']) ?>" href="<?= esc($child['url']) ?>">
                                    <span class="erp-menu-label"><?= esc($child['label']) ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="erp-sidebar-footer">
        <span><?= esc(lang('App.app_name')) ?> v<?= esc(lang('App.version')) ?></span>
    </div>
</aside>
