<header class="erp-topbar">
    <div class="erp-topbar-left">
        <button class="erp-btn erp-btn-icon d-lg-none" data-mobile-sidebar aria-label="Mở menu"><i class="bi bi-list"></i></button>
        <button class="erp-btn erp-btn-icon d-none d-lg-inline-flex" data-sidebar-toggle aria-label="Thu gọn menu"><i class="bi bi-layout-sidebar"></i></button>
        <?= view('layouts/partials/breadcrumb', ['breadcrumbs' => $breadcrumbs ?? []]) ?>
    </div>
    <div class="erp-topbar-right">
        <div class="erp-search">
            <i class="bi bi-search"></i>
            <input type="search" placeholder="Tìm booking, người chơi, sân...">
        </div>
        <?php if (is_superadmin()): ?>
            <a class="erp-btn" href="/admin/tenants/select"><i class="bi bi-building"></i> Tenant</a>
        <?php endif; ?>
        <a class="erp-btn" href="/locale/switch/<?= (session('locale') ?? 'en') === 'vi' ? 'en' : 'vi' ?>">
            <i class="bi bi-translate"></i> <?= strtoupper(session('locale') ?? 'EN') ?>
        </a>
        <button class="erp-btn erp-btn-icon" type="button" aria-label="Thông báo"><i class="bi bi-bell"></i></button>
        <a class="erp-btn erp-btn-primary" href="/admin/bookings/create"><i class="bi bi-plus-lg"></i> Đặt sân</a>
        <div class="dropdown">
            <button class="erp-btn dropdown-toggle" data-bs-toggle="dropdown">
                <span class="erp-avatar"><?= esc(substr(session('fullName') ?? 'AD', 0, 1)) ?></span>
            </button>
            <div class="dropdown-menu dropdown-menu-end">
                <a class="dropdown-item" href="/admin/profile">Hồ sơ</a>
                <a class="dropdown-item" href="/logout">Đăng xuất</a>
            </div>
        </div>
    </div>
</header>
