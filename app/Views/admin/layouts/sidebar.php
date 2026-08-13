<nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
    <div class="position-sticky pt-3">
        <div class="px-3 mb-3">
            <a href="/admin/dashboard" class="text-white text-decoration-none fs-5 fw-bold">
                🏓 <?= lang('App.app_name') ?>
            </a>
        </div>

        <hr class="text-secondary">

        <ul class="nav flex-column">
            <?php
                $segment2 = service('uri')->getSegment(2);
                $segment3 = service('uri')->getSegment(3);
                $isPlayersDashboard = $segment2 === 'players' && $segment3 === 'dashboard';
                $isPlayersRanking = $segment2 === 'players' && $segment3 === 'ranking';
            ?>

            <li class="nav-item">
                <a class="nav-link <?= is_active_route('admin/dashboard') ?>" href="/admin/dashboard">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link small text-white-50" href="#"><i class="bi bi-bag-check-fill"></i> Commerce Flow</a>
            </li>
            <li class="nav-item">
                <a class="nav-link ps-3 <?= is_active_route('admin/venue-operations') ?>" href="/admin/venue-operations">Quản lý vận hành sân</a>
            </li>
            <li class="nav-item">
                <a class="nav-link ps-3 <?= is_active_route('admin/front-desk') ?>" href="/admin/front-desk">Front Desk</a>
            </li>
            <li class="nav-item">
                <a class="nav-link ps-3 <?= is_active_route('admin/owner-dashboard') ?>" href="/admin/owner-dashboard">Owner Dashboard</a>
            </li>
            <li class="nav-item">
                <a class="nav-link ps-3 <?= is_active_route('admin/daily-closing') ?>" href="/admin/daily-closing">Daily Closing</a>
            </li>
            <li class="nav-item">
                <a class="nav-link ps-3 <?= is_active_route('admin/memberships/renewals') ?>" href="/admin/memberships/renewals">Membership Renewal</a>
            </li>
            <li class="nav-item">
                <a class="nav-link ps-3 <?= is_active_route('admin/crm-campaigns') ?>" href="/admin/crm-campaigns">CRM Campaign</a>
            </li>
            <li class="nav-item">
                <a class="nav-link ps-3 <?= is_active_route('admin/runbook') ?>" href="/admin/runbook">Runbook vận hành</a>
            </li>
            <li class="nav-item">
                <a class="nav-link ps-3 <?= is_active_route('admin/runbook') ?>" href="/admin/runbook?focus=15">Runbook 15-module</a>
            </li>
            <li class="nav-item">
                <a class="nav-link ps-3" href="/admin/venue-operations">Venue Control Room</a>
            </li>
            <li class="nav-item">
                <a class="nav-link ps-3" href="/admin/tournaments/control-room">TV / LED Board</a>
            </li>
            <li class="nav-item">
                <a class="nav-link ps-3" href="/admin/print-center">Print Center</a>
            </li>
            <li class="nav-item">
                <a class="nav-link ps-3" href="/admin/tournament-templates">Tournament Template</a>
            </li>

            <li class="nav-item mt-2">
                <a class="nav-link small text-white-50" href="#"><i class="bi bi-building-gear"></i> Venue & Court</a>
            </li>
            <li class="nav-item">
                <a class="nav-link ps-3 <?= is_active_route('admin/facilities') ?>" href="/admin/facilities">Facilities</a>
            </li>
            <li class="nav-item">
                <a class="nav-link ps-3 <?= is_active_route('admin/branches') ?>" href="/admin/branches">Branches</a>
            </li>
            <li class="nav-item">
                <a class="nav-link ps-3 <?= is_active_route('admin/courts') ?>" href="/admin/courts">Courts</a>
            </li>
            <li class="nav-item">
                <a class="nav-link ps-3" href="/admin/courts/calendar">Court Calendar</a>
            </li>

            <li class="nav-item mt-2">
                <a class="nav-link small text-white-50" href="#"><i class="bi bi-calendar2-check"></i> Booking & Match</a>
            </li>
            <li class="nav-item">
                <a class="nav-link ps-3 <?= is_active_route('admin/bookings') ?>" href="/admin/bookings">Bookings</a>
            </li>
            <li class="nav-item">
                <a class="nav-link ps-3 <?= is_active_route('admin/waitlist') ?>" href="/admin/waitlist">Waitlist</a>
            </li>
            <li class="nav-item">
                <a class="nav-link ps-3 <?= is_active_route('admin/walk-ins') ?>" href="/admin/walk-ins">Walk-in</a>
            </li>

            <li class="nav-item mt-2">
                <a class="nav-link small text-white-50" href="#"><i class="bi bi-calendar-check"></i> Tournament</a>
            </li>
            <li class="nav-item">
                <a class="nav-link ps-3 <?= is_active_route('admin/tournaments') ?>" href="/admin/tournaments/scheduler">Tournament Scheduler</a>
            </li>
            <li class="nav-item">
                <a class="nav-link ps-3" href="/admin/tournament-templates">Tournament Templates</a>
            </li>

            <li class="nav-item mt-2">
                <a class="nav-link small text-white-50" href="#"><i class="bi bi-people-fill"></i> Membership & Users</a>
            </li>
            <li class="nav-item">
                <a class="nav-link ps-3 <?= is_active_route('admin/memberships') ?>" href="/admin/memberships">Memberships</a>
            </li>
            <li class="nav-item">
                <a class="nav-link ps-3 <?= is_active_route('admin/players') ?>" href="/admin/players">Players</a>
            </li>
            <li class="nav-item">
                <a class="nav-link ps-3 <?= $isPlayersDashboard ? 'active' : '' ?>" href="/admin/players/dashboard">Player Dashboard</a>
            </li>
            <li class="nav-item">
                <a class="nav-link ps-3 <?= $isPlayersRanking ? 'active' : '' ?>" href="/admin/players/ranking">Player Ranking</a>
            </li>
            <li class="nav-item">
                <a class="nav-link ps-3 <?= is_active_route('admin/clubs') ?>" href="/admin/clubs">Clubs</a>
            </li>

            <li class="nav-item mt-2">
                <a class="nav-link small text-white-50" href="#"><i class="bi bi-shop-window"></i> Revenue & POS</a>
            </li>
            <li class="nav-item">
                <a class="nav-link ps-3 <?= is_active_route('admin/payments') ?>" href="/admin/payments">Thanh toán</a>
            </li>
            <li class="nav-item">
                <a class="nav-link ps-3 <?= is_active_route('admin/pos') ?>" href="/admin/pos">POS Bán hàng</a>
            </li>
            <li class="nav-item">
                <a class="nav-link ps-3 <?= is_active_route('admin/inventory') ?>" href="/admin/pos/inventory">Tồn kho</a>
            </li>
            <li class="nav-item">
                <a class="nav-link ps-3 <?= is_active_route('admin/pricing-rules') ?>" href="/admin/pricing-rules">Dynamic Pricing</a>
            </li>

            <?php if ($is_superadmin ?? false): ?>
            <li class="nav-item mt-3">
                <a class="nav-link small text-white-50" href="#"><i class="bi bi-briefcase-fill"></i> Super Admin</a>
            </li>
            <li class="nav-item">
                <a class="nav-link ps-3 <?= is_active_route('admin/tenants') ?>" href="/admin/tenants">
                    <i class="bi bi-building"></i> <?= lang('Tenant.tenants') ?>
                </a>
            </li>
            <?php endif; ?>

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
            <li class="nav-item">
                <a class="nav-link" href="/venues" target="_blank">
                    <i class="bi bi-globe2"></i> Xem Public Venue
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/clubs" target="_blank">
                    <i class="bi bi-people-fill"></i> Xem Public Club
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/api/v1/facilities?tenant_id=<?= (int) (current_tenant_id() ?: 1) ?>" target="_blank">
                    <i class="bi bi-code-slash"></i> Test API Venue
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/admin/facilities" target="_blank">
                    <i class="bi bi-diagram-3"></i> Facility-Club Partnership
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/api/v1/branches?tenant_id=<?= (int) (current_tenant_id() ?: 1) ?>" target="_blank">
                    <i class="bi bi-terminal-dash"></i> API chuẩn
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/admin/audit-logs" target="_blank">
                    <i class="bi bi-shield-lock"></i> Security Hardening
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/admin/runbook" target="_blank">
                    <i class="bi bi-play-btn"></i> One-Click Runbook
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
