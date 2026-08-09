<?php
$menu = $menu ?? [
    'Vận hành' => [
        ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'url' => '/admin/dashboard', 'active' => 'admin/dashboard'],
        ['label' => 'Đặt sân', 'icon' => 'bi-calendar-check', 'url' => '/admin/bookings', 'active' => 'admin/bookings', 'badge' => '12'],
        ['label' => 'Giải đấu', 'icon' => 'bi-trophy', 'url' => '/admin/tournaments', 'active' => 'admin/tournaments'],
        ['label' => 'Dynamic Pricing', 'icon' => 'bi-cash-coin', 'url' => '/admin/pricing-rules', 'active' => 'admin/pricing-rules'],
        ['label' => 'Sân & lịch', 'icon' => 'bi-grid-3x3-gap', 'url' => '/admin/courts', 'active' => 'admin/courts'],
    ],
    'Khách hàng' => [
        ['label' => 'Người chơi', 'icon' => 'bi-person-vcard', 'url' => '/admin/players', 'active' => 'admin/players'],
        ['label' => 'Hội viên', 'icon' => 'bi-award', 'url' => '/admin/memberships', 'active' => 'admin/memberships'],
        ['label' => 'Club', 'icon' => 'bi-building-heart', 'url' => '/admin/clubs', 'active' => 'admin/clubs'],
        ['label' => 'Team', 'icon' => 'bi-people-fill', 'url' => '/admin/teams', 'active' => 'admin/teams'],
        ['label' => 'Kèo mở', 'icon' => 'bi-controller', 'url' => '/admin/matches', 'active' => 'admin/matches'],
    ],
    'Hệ thống' => [
        ['label' => 'UI Foundation', 'icon' => 'bi-palette', 'url' => '/admin/ui-demo/dashboard', 'active' => 'admin/ui-demo'],
        ['label' => 'Cụm sân', 'icon' => 'bi-buildings', 'url' => '/admin/facilities', 'active' => 'admin/facilities'],
        ['label' => 'Người dùng', 'icon' => 'bi-people', 'url' => '/admin/users', 'active' => 'admin/users'],
        ['label' => 'Vai trò', 'icon' => 'bi-shield-lock', 'url' => '/admin/roles', 'active' => 'admin/roles'],
        ['label' => 'Thiết lập', 'icon' => 'bi-gear', 'url' => '/admin/settings', 'active' => 'admin/settings'],
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
        <span>ERP UI Foundation v1.0</span>
    </div>
</aside>
