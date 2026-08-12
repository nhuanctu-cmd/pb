<nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
    <div class="position-sticky pt-3">
        <div class="px-3 mb-3">
            <a href="/admin/dashboard" class="text-white text-decoration-none fs-5 fw-bold">
                🏓 <?= lang('App.app_name') ?>
            </a>
        </div>

        <hr class="text-secondary">

        <ul class="nav flex-column">
            <?php if ($is_superadmin ?? false): ?>
            <li class="nav-item">
                <a class="nav-link <?= is_active_route('admin/tenants') ?>" href="/admin/tenants">
                    <i class="bi bi-building"></i> <?= lang('Tenant.tenants') ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= is_active_route('admin/branches') ?>" href="/admin/branches">
                    <i class="bi bi-diagram-3"></i> <?= lang('Tenant.branches') ?>
                </a>
            </li>
            <?php endif; ?>

            <li class="nav-item">
                <a class="nav-link <?= is_active_route('admin/dashboard') ?>" href="/admin/dashboard">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= is_active_route('admin/front-desk') ?>" href="/admin/front-desk">
                    <i class="bi bi-person-workspace"></i> Front Desk
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= is_active_route('admin/daily-closing') ?>" href="/admin/daily-closing">
                    <i class="bi bi-cash-stack"></i> Daily Closing
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= is_active_route('admin/owner-dashboard') ?>" href="/admin/owner-dashboard">
                    <i class="bi bi-briefcase"></i> Owner Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= is_active_route('admin/memberships/renewals') ?>" href="/admin/memberships/renewals">
                    <i class="bi bi-arrow-repeat"></i> Gia hạn hội viên
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= is_active_route('admin/crm-campaigns') ?>" href="/admin/crm-campaigns">
                    <i class="bi bi-megaphone-fill"></i> CRM Campaign
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= is_active_route('admin/runbook') ?>" href="/admin/runbook">
                    <i class="bi bi-journal-check"></i> Runbook vận hành
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= is_active_route('admin/facilities') ?>" href="/admin/facilities">
                    <i class="bi bi-buildings"></i> Facilities
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= is_active_route('admin/bookings') ?>" href="/admin/bookings">
                    <i class="bi bi-calendar-check"></i> <?= lang('App.bookings') ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= is_active_route('admin/pricing-rules') ?>" href="/admin/pricing-rules">
                    <i class="bi bi-cash-coin"></i> Dynamic Pricing
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= is_active_route('admin/pos') ?>" href="/admin/pos">
                    <i class="bi bi-cart"></i> POS Bán hàng
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= is_active_route('admin/payments') ?>" href="/admin/payments">
                    <i class="bi bi-credit-card"></i> Thanh toán
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= is_active_route('admin/inventory') ?>" href="/admin/pos/inventory">
                    <i class="bi bi-box-seam"></i> Tồn kho
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= is_active_route('admin/courts') ?>" href="/admin/courts">
                    <i class="bi bi-grid-3x3-gap-fill"></i> <?= lang('Court.courts') ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= is_active_route('admin/players') ?>" href="/admin/players">
                    <i class="bi bi-person-vcard"></i> Players
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= is_active_route('admin/tournaments') ?>" href="/admin/tournaments/scheduler">
                    <i class="bi bi-diagram-3"></i> Tournament Scheduler
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link small <?= service('uri')->getSegment(2) === 'players' && service('uri')->getSegment(3) === 'dashboard' ? 'active' : '' ?>" href="/admin/players/dashboard">
                    <i class="bi bi-speedometer2"></i> Player Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link small <?= service('uri')->getSegment(2) === 'players' && service('uri')->getSegment(3) === 'ranking' ? 'active' : '' ?>" href="/admin/players/ranking">
                    <i class="bi bi-trophy"></i> Ranking
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= is_active_route('admin/memberships') ?>" href="/admin/memberships">
                    <i class="bi bi-award"></i> Memberships
                </a>
            </li>

            <li class="nav-item mt-3">
                <span class="nav-link text-secondary text-uppercase small">System</span>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= is_active_route('admin/users') ?>" href="/admin/users">
                    <i class="bi bi-people"></i> Users
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= is_active_route('admin/roles') ?>" href="/admin/roles">
                    <i class="bi bi-shield-lock"></i> Roles
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= is_active_route('admin/settings') ?>" href="/admin/settings">
                    <i class="bi bi-gear"></i> Settings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= is_active_route('admin/audit-logs') ?>" href="/admin/audit-logs">
                    <i class="bi bi-journal-text"></i> Audit Logs
                </a>
            </li>
        </ul>

        <hr class="text-secondary">

        <h6 class="px-3 text-secondary text-uppercase small"><?= lang('Tenant.tenants') ?></h6>
        <ul class="nav flex-column mb-2">
            <?php if ($current_tenant_id ?? false): ?>
            <li class="nav-item px-3">
                <span class="text-light small"><?= session('tenant_name') ?? '' ?></span>
            </li>
            <li class="nav-item">
                <a class="nav-link small" href="/admin/tenants/select">
                    <i class="bi bi-arrow-left-right"></i> Switch Tenant
                </a>
            </li>
            <?php else: ?>
            <li class="nav-item">
                <a class="nav-link small" href="/admin/tenants/select">
                    <i class="bi bi-building-add"></i> Select Tenant
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
