<nav class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
    <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar" aria-controls="sidebar" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="w-100"></div>

    <div class="d-flex align-items-center px-3">
        <!-- Language Switcher -->
        <div class="dropdown me-3">
            <button class="btn btn-dark btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <?= $current_locale === 'vi' ? '🇻🇳 VI' : '🇺🇸 EN' ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item <?= $current_locale === 'en' ? 'active' : '' ?>" href="/locale/switch/en">🇺🇸 English</a></li>
                <li><a class="dropdown-item <?= $current_locale === 'vi' ? 'active' : '' ?>" href="/locale/switch/vi">🇻🇳 Tiếng Việt</a></li>
            </ul>
        </div>

        <!-- User Dropdown -->
        <div class="dropdown">
            <button class="btn btn-dark btn-sm dropdown-toggle d-flex align-items-center" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-person-circle me-1"></i>
                <?= session('fullName') ?? session('username') ?? 'User' ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="/admin/profile"><i class="bi bi-person"></i> <?= esc(lang('App.account_profile')) ?></a></li>
                <li><a class="dropdown-item" href="/admin/settings"><i class="bi bi-gear"></i> <?= esc(lang('App.account_settings')) ?></a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="/logout"><i class="bi bi-box-arrow-right"></i> <?= esc(lang('App.logout')) ?></a></li>
            </ul>
        </div>
    </div>
</nav>
