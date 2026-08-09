<!DOCTYPE html>
<html lang="<?= session('lang') ?? 'vi' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#1a73e8">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title><?= $this->renderSection('title') ?> - Pickleball</title>
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/assets/player/icons/icon-192.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1a73e8;
            --success: #0f9d58;
            --warning: #f4b400;
            --danger: #db4437;
            --bg: #f8f9fa;
            --card: #ffffff;
            --bottom-nav-height: 56px;
            --header-height: 48px;
            --safe-bottom: env(safe-area-inset-bottom, 0px);
        }
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        html, body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg);
            margin: 0;
            padding: 0;
            overscroll-behavior: none;
            -webkit-font-smoothing: antialiased;
        }
        /* Mobile header */
        .mobile-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: var(--header-height);
            background: var(--primary);
            color: #fff;
            display: flex;
            align-items: center;
            padding: 0 16px;
            z-index: 1030;
            gap: 8px;
        }
        .mobile-header .back-btn {
            background: none;
            border: none;
            color: #fff;
            font-size: 22px;
            padding: 4px;
            cursor: pointer;
        }
        .mobile-header h1 {
            font-size: 17px;
            font-weight: 600;
            margin: 0;
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .mobile-header .header-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        .mobile-header .header-actions a {
            color: #fff;
            text-decoration: none;
            font-size: 20px;
            position: relative;
        }
        .badge-dot {
            position: absolute;
            top: -2px;
            right: -4px;
            width: 8px;
            height: 8px;
            background: var(--danger);
            border-radius: 50%;
        }
        /* Main content */
        .mobile-content {
            padding: calc(var(--header-height) + 8px) 12px calc(var(--bottom-nav-height) + var(--safe-bottom) + 12px);
            min-height: 100vh;
        }
        /* Bottom navigation */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: calc(var(--bottom-nav-height) + var(--safe-bottom));
            padding-bottom: var(--safe-bottom);
            background: var(--card);
            border-top: 1px solid #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: space-around;
            z-index: 1030;
        }
        .bottom-nav a {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2px;
            text-decoration: none;
            color: #666;
            font-size: 10px;
            padding: 4px 8px;
            min-width: 48px;
            -webkit-tap-highlight-color: transparent;
        }
        .bottom-nav a i { font-size: 22px; }
        .bottom-nav a.active { color: var(--primary); font-weight: 600; }
        /* Cards */
        .card-mobile {
            background: var(--card);
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            margin-bottom: 12px;
            overflow: hidden;
        }
        .card-mobile .card-body { padding: 16px; }
        .card-mobile .card-header {
            padding: 14px 16px;
            font-weight: 600;
            font-size: 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        /* Buttons */
        .btn-mobile {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 20px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            border: none;
            width: 100%;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .btn-mobile:active { opacity: 0.8; }
        .btn-mobile-primary { background: var(--primary); color: #fff; }
        .btn-mobile-success { background: var(--success); color: #fff; }
        .btn-mobile-danger { background: var(--danger); color: #fff; }
        .btn-mobile-outline {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }
        /* FAB */
        .fab {
            position: fixed;
            bottom: calc(var(--bottom-nav-height) + var(--safe-bottom) + 16px);
            right: 16px;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: var(--primary);
            color: #fff;
            border: none;
            font-size: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(26,115,232,0.4);
            cursor: pointer;
            z-index: 1020;
            transition: transform 0.2s;
        }
        .fab:active { transform: scale(0.9); }
        /* Status chips */
        .status-chip {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-confirmed { background: #e8f5e9; color: #2e7d32; }
        .status-pending { background: #fff3e0; color: #e65100; }
        .status-cancelled { background: #ffebee; color: #c62828; }
        .status-completed { background: #e3f2fd; color: #1565c0; }
        /* Calendar mobile */
        .cal-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 4px;
            text-align: center;
        }
        .cal-grid .cal-header {
            font-size: 12px;
            color: #666;
            padding: 8px 0;
            font-weight: 600;
        }
        .cal-grid .cal-day {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .cal-grid .cal-day.selected { background: var(--primary); color: #fff; }
        .cal-grid .cal-day.has-booking { border: 2px solid var(--primary); font-weight: 700; }
        .cal-grid .cal-day.today { font-weight: 700; }
        /* QR card */
        .qr-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 24px;
        }
        .qr-container canvas, .qr-container img {
            width: 200px;
            height: 200px;
            margin-bottom: 16px;
        }
        /* Form mobile */
        .form-group-mobile {
            margin-bottom: 16px;
        }
        .form-group-mobile label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #555;
            margin-bottom: 6px;
        }
        .form-group-mobile input,
        .form-group-mobile select,
        .form-group-mobile textarea {
            width: 100%;
            padding: 14px 16px;
            border: 1.5px solid #ddd;
            border-radius: 10px;
            font-size: 16px;
            background: var(--card);
            transition: border-color 0.2s;
        }
        .form-group-mobile input:focus,
        .form-group-mobile select:focus {
            outline: none;
            border-color: var(--primary);
        }
        /* Stepper */
        .stepper {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
        }
        .stepper .step {
            flex: 1;
            text-align: center;
            font-size: 12px;
            color: #999;
            position: relative;
        }
        .stepper .step .step-num {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #eee;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 13px;
            margin-bottom: 4px;
        }
        .stepper .step.active .step-num { background: var(--primary); color: #fff; }
        .stepper .step.done .step-num { background: var(--success); color: #fff; }
        .stepper .step.active { color: var(--primary); font-weight: 600; }
        .stepper .step.done { color: var(--success); }
        /* Language switcher */
        .lang-switch {
            display: flex;
            gap: 4px;
            background: rgba(255,255,255,0.2);
            border-radius: 16px;
            padding: 2px;
        }
        .lang-switch button {
            background: none;
            border: none;
            color: #fff;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            opacity: 0.7;
        }
        .lang-switch button.active { background: rgba(255,255,255,0.3); opacity: 1; }
        /* Step content */
        .step-content { display: none; }
        .step-content.active { display: block; }
        /* Toast */
        .mobile-toast {
            position: fixed;
            bottom: calc(var(--bottom-nav-height) + var(--safe-bottom) + 20px);
            left: 50%;
            transform: translateX(-50%);
            background: #333;
            color: #fff;
            padding: 12px 24px;
            border-radius: 24px;
            font-size: 14px;
            font-weight: 500;
            z-index: 9999;
            opacity: 0;
            transition: opacity 0.3s;
            pointer-events: none;
        }
        .mobile-toast.show { opacity: 1; }
        /* Empty state */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 48px 24px;
            color: #999;
        }
        .empty-state i { font-size: 48px; margin-bottom: 12px; }
        .empty-state p { font-size: 14px; text-align: center; }
        @media (min-width: 768px) {
            .mobile-content { max-width: 480px; margin: 0 auto; }
        }
    </style>
    <?= $this->renderSection('styles') ?>
</head>
<body>
    <!-- Mobile Header -->
    <header class="mobile-header">
        <?php if (isset($showBack) && $showBack): ?>
        <button class="back-btn" onclick="history.back()"><i class="bi bi-chevron-left"></i></button>
        <?php endif; ?>
        <h1><?= $this->renderSection('title') ?></h1>
        <div class="header-actions">
            <div class="lang-switch">
                <button class="<?= (session('lang') ?? 'vi') === 'vi' ? 'active' : '' ?>" onclick="switchLang('vi')">VI</button>
                <button class="<?= (session('lang') ?? 'vi') === 'en' ? 'active' : '' ?>" onclick="switchLang('en')">EN</button>
            </div>
            <a href="/player/notifications">
                <i class="bi bi-bell"></i>
                <span class="badge-dot" id="notifDot" style="display:none"></span>
            </a>
        </div>
    </header>

    <!-- Content -->
    <main class="mobile-content">
        <?= $this->renderSection('content') ?>
    </main>

    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="/player" class="<?= current_url() === site_url('player') ? 'active' : '' ?>">
            <i class="bi bi-house-door"></i>
            <span>Trang chủ</span>
        </a>
        <a href="/player/bookings" class="<?= strpos(current_url(), 'player/bookings') !== false ? 'active' : '' ?>">
            <i class="bi bi-calendar-check"></i>
            <span>Lịch</span>
        </a>
        <a href="/player/tournaments" class="<?= strpos(current_url(), 'player/tournaments') !== false ? 'active' : '' ?>">
            <i class="bi bi-trophy"></i>
            <span>Giải</span>
        </a>
        <a href="/player/wallet" class="<?= strpos(current_url(), 'player/wallet') !== false ? 'active' : '' ?>">
            <i class="bi bi-wallet2"></i>
            <span>Ví</span>
        </a>
        <a href="/player/profile" class="<?= strpos(current_url(), 'player/profile') !== false ? 'active' : '' ?>">
            <i class="bi bi-person"></i>
            <span>Cá nhân</span>
        </a>
    </nav>

    <!-- FAB -->
    <button class="fab" onclick="location.href='/player/booking/create'" id="fabBtn">
        <i class="bi bi-plus-lg"></i>
    </button>

    <!-- Toast -->
    <div class="mobile-toast" id="toast"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function switchLang(lang) {
        fetch('/player/lang/' + lang).then(() => location.reload());
    }
    function showToast(msg, type) {
        const t = document.getElementById('toast');
        t.textContent = msg;
        t.style.background = type === 'error' ? '#db4437' : '#333';
        t.classList.add('show');
        setTimeout(() => t.classList.remove('show'), 3000);
    }
    <?php if (session()->getFlashdata('message')): ?>
    showToast('<?= session()->getFlashdata('message') ?>');
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
    showToast('<?= session()->getFlashdata('error') ?>', 'error');
    <?php endif; ?>
    </script>
    <?= $this->renderSection('scripts') ?>
</body>
</html>
