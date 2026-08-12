<?php
$stats = $tournamentStats ?? [];
$status = (string) ($tournament->status ?? 'draft');
$categoryCount = count($categories ?? []);
$registrationCount = (int) ($stats['registrations'] ?? 0);
$capacity = (int) ($stats['capacity'] ?? 0);
$registrationProgress = $capacity > 0 ? min(100, (int) round($registrationCount / $capacity * 100)) : 0;
$matchCount = (int) ($stats['matches'] ?? 0);
$completedMatchCount = (int) ($stats['completed_matches'] ?? 0);
$matchProgress = $matchCount > 0 ? min(100, (int) round($completedMatchCount / $matchCount * 100)) : 0;
$firstCategoryId = (int) ($categories[0]->id ?? 0);
$description = trim((string) ($tournament->description_vi ?? ''));
$statusSteps = [
    ['key' => 'setup', 'label' => 'Thiết lập', 'icon' => 'bi-sliders2', 'done' => true],
    ['key' => 'registration', 'label' => 'Mở đăng ký', 'icon' => 'bi-person-plus', 'done' => in_array($status, ['open', 'closed', 'running', 'completed'], true)],
    ['key' => 'review', 'label' => 'Duyệt hồ sơ', 'icon' => 'bi-person-check', 'done' => (int) ($stats['approved'] ?? 0) > 0],
    ['key' => 'checkin', 'label' => 'Check-in', 'icon' => 'bi-qr-code-scan', 'done' => (int) ($stats['checked_in'] ?? 0) > 0],
    ['key' => 'schedule', 'label' => 'Xếp lịch', 'icon' => 'bi-calendar3', 'done' => $matchCount > 0 && (int) ($stats['unassigned_matches'] ?? 0) === 0],
    ['key' => 'live', 'label' => 'Thi đấu & điểm', 'icon' => 'bi-broadcast', 'done' => (int) ($stats['live_matches'] ?? 0) > 0 || $completedMatchCount > 0],
    ['key' => 'close', 'label' => 'Tổng kết', 'icon' => 'bi-flag', 'done' => $status === 'completed'],
];
?>

<?= $this->extend('layouts/admin_master') ?>

<?= $this->section('content') ?>
<style>
    .tournament-page { --tour-ink:#172033; --tour-muted:#6b7280; --tour-line:#e7ebf1; --tour-primary:#4056d8; --tour-soft:#f6f8fc; }
    .tournament-page .tour-hero { position:relative; overflow:hidden; border:0; border-radius:20px; color:#fff; background:linear-gradient(120deg,#172554 0%,#293b91 58%,#4f46a5 100%); box-shadow:0 18px 45px rgba(30,41,99,.18); }
    .tour-hero:after { content:""; position:absolute; width:330px; height:330px; right:-100px; top:-160px; border-radius:50%; background:rgba(255,255,255,.08); }
    .tour-hero-body { position:relative; z-index:1; padding:28px; }
    .tour-kicker { letter-spacing:.12em; text-transform:uppercase; font-size:.72rem; opacity:.72; font-weight:700; }
    .tour-hero h1 { max-width:760px; margin:.35rem 0 .65rem; font-size:clamp(1.5rem,3vw,2.35rem); font-weight:800; }
    .tour-hero .tour-description { max-width:760px; color:rgba(255,255,255,.78); }
    .tour-meta { display:flex; flex-wrap:wrap; gap:18px; margin-top:22px; }
    .tour-meta-item { display:flex; align-items:center; gap:9px; color:rgba(255,255,255,.88); font-size:.88rem; }
    .tour-meta-item i { color:#b9c7ff; font-size:1.05rem; }
    .tour-hero-actions { position:relative; z-index:2; display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end; padding:18px 28px; background:rgba(9,18,58,.25); }
    .tour-hero-actions .btn { border-color:rgba(255,255,255,.35); color:#fff; }
    .tour-hero-actions .btn:hover { background:#fff; color:var(--tour-ink); }
    .tour-status { border-radius:999px; padding:.38rem .72rem; font-size:.76rem; font-weight:700; background:#fff; color:#23346e; }
    .tour-tabs { display:flex; gap:4px; overflow:auto; padding:7px; border:1px solid var(--tour-line); border-radius:14px; background:#fff; }
    .tour-tabs a { white-space:nowrap; padding:9px 13px; border-radius:9px; color:#5f6878; font-size:.86rem; text-decoration:none; }
    .tour-tabs a:hover, .tour-tabs a.active { color:var(--tour-primary); background:#eef1ff; font-weight:700; }
    .tour-kpi { height:100%; border:1px solid var(--tour-line); border-radius:15px; background:#fff; padding:17px; }
    .tour-kpi .kpi-icon { width:36px; height:36px; display:grid; place-items:center; border-radius:11px; color:var(--tour-primary); background:#eef1ff; }
    .tour-kpi .kpi-label { margin-top:15px; color:var(--tour-muted); font-size:.8rem; }
    .tour-kpi .kpi-value { margin-top:3px; color:var(--tour-ink); font-size:1.45rem; font-weight:800; }
    .tour-kpi .kpi-note { color:var(--tour-muted); font-size:.74rem; }
    .tour-card { height:100%; border:1px solid var(--tour-line); border-radius:16px; background:#fff; box-shadow:0 4px 18px rgba(31,41,55,.035); }
    .tour-card-header { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:17px 19px; border-bottom:1px solid var(--tour-line); }
    .tour-card-header h2, .tour-card-header h3 { margin:0; color:var(--tour-ink); font-size:1rem; font-weight:800; }
    .tour-card-body { padding:19px; }
    .tour-progress { height:7px; overflow:hidden; border-radius:9px; background:#edf0f5; }
    .tour-progress > span { display:block; height:100%; border-radius:inherit; background:linear-gradient(90deg,#536dff,#8b5cf6); }
    .tour-stepper { display:grid; grid-template-columns:repeat(7,1fr); gap:8px; }
    .tour-step { position:relative; min-width:0; text-align:center; color:#a1a8b4; font-size:.73rem; }
    .tour-step:not(:last-child):after { content:""; position:absolute; top:17px; left:62%; width:76%; height:2px; background:#e7ebf1; }
    .tour-step.done { color:#3448bf; }
    .tour-step.done:after { background:#aebaff; }
    .tour-step-icon { position:relative; z-index:1; display:grid; place-items:center; width:35px; height:35px; margin:0 auto 7px; border:2px solid #e7ebf1; border-radius:50%; background:#fff; }
    .tour-step.done .tour-step-icon { border-color:#6576e8; color:#fff; background:#5366d9; }
    .tour-category { height:100%; padding:17px; border:1px solid var(--tour-line); border-radius:14px; background:linear-gradient(145deg,#fff,#fafbff); }
    .tour-category h3 { margin:0; font-size:1rem; color:var(--tour-ink); }
    .tour-category .category-type { color:var(--tour-muted); font-size:.76rem; }
    .category-metrics { display:grid; grid-template-columns:repeat(3,1fr); gap:8px; margin:17px 0; }
    .category-metrics > div { padding:9px; border-radius:10px; background:var(--tour-soft); }
    .category-metrics strong { display:block; color:var(--tour-ink); }
    .category-metrics small { color:var(--tour-muted); font-size:.7rem; }
    .tour-checklist { display:grid; gap:11px; }
    .tour-check { display:flex; gap:10px; align-items:flex-start; padding:10px 11px; border-radius:10px; background:var(--tour-soft); font-size:.82rem; }
    .tour-check i { margin-top:2px; color:#d97706; }
    .tour-check.ok i { color:#059669; }
    .tour-table td, .tour-table th { vertical-align:middle; white-space:nowrap; }
    .tour-table th { color:#687286; font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; }
    .tour-table td { color:#263044; font-size:.82rem; }
    .tour-empty { padding:28px 14px; text-align:center; color:var(--tour-muted); }
    .tour-info-list { display:grid; gap:0; }
    .tour-info-row { display:flex; justify-content:space-between; gap:15px; padding:10px 0; border-bottom:1px solid var(--tour-line); font-size:.83rem; }
    .tour-info-row:last-child { border-bottom:0; }
    .tour-info-row span { color:var(--tour-muted); }
    .tour-info-row strong { color:var(--tour-ink); text-align:right; }
    .tour-sponsor { display:flex; align-items:center; gap:10px; padding:10px 0; border-bottom:1px solid var(--tour-line); }
    .tour-sponsor:last-child { border-bottom:0; }
    .tour-sponsor img { width:38px; height:38px; object-fit:contain; border-radius:8px; background:#f7f8fb; }
    @media (max-width: 991px) { .tour-stepper { grid-template-columns:repeat(4,1fr); row-gap:18px; } .tour-step:nth-child(4):after { display:none; } }
    @media (max-width: 575px) { .tour-hero-body, .tour-hero-actions { padding:18px; } .tour-stepper { grid-template-columns:repeat(2,1fr); } .tour-step:after { display:none; } }
</style>

<div class="tournament-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <a class="text-decoration-none small" href="/admin/tournaments"><i class="bi bi-arrow-left me-1"></i>Danh sách giải đấu</a>
            <div class="small text-muted mt-1">Trung tâm điều hành giải đấu · #<?= (int) $tournament->id ?></div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <?= renderStatusBadge($status, 'tournament') ?>
            <span class="small text-muted">Cập nhật <?= esc(format_datetime($tournament->updated_at ?? $tournament->created_at ?? null)) ?></span>
        </div>
    </div>

    <section class="tour-hero mb-3">
        <div class="tour-hero-body">
            <div class="tour-kicker">Pickleball tournament operations</div>
            <h1><?= esc($tournament->name_vi ?? 'Giải đấu chưa đặt tên') ?></h1>
            <p class="tour-description mb-0"><?= esc($description !== '' ? $description : 'Chưa có mô tả. Hãy bổ sung thông tin để vận động viên và đội vận hành có cùng một nguồn dữ liệu.') ?></p>
            <div class="tour-meta">
                <span class="tour-meta-item"><i class="bi bi-calendar-event"></i><?= esc(format_date($tournament->start_date ?? null)) ?> – <?= esc(format_date($tournament->end_date ?? null)) ?></span>
                <span class="tour-meta-item"><i class="bi bi-person-badge"></i><?= $registrationCount ?> / <?= $capacity > 0 ? $capacity : '∞' ?> đăng ký</span>
                <span class="tour-meta-item"><i class="bi bi-cash-coin"></i><?= format_money($tournament->registration_fee ?? 0) ?></span>
                <span class="tour-meta-item"><i class="bi bi-diagram-3"></i><?= $categoryCount ?> hạng mục · <?= $matchCount ?> trận</span>
            </div>
        </div>
        <div class="tour-hero-actions">
            <a href="/admin/tournaments/edit/<?= (int) $tournament->id ?>" class="btn btn-sm"><i class="bi bi-pencil me-1"></i>Chỉnh sửa</a>
            <a href="/admin/tournaments/registrations/<?= (int) $tournament->id ?>" class="btn btn-sm"><i class="bi bi-people me-1"></i>Đăng ký</a>
            <a href="/admin/tournaments/registrations/<?= (int) $tournament->id ?>/export" class="btn btn-sm"><i class="bi bi-filetype-csv me-1"></i>Xuất CSV</a>
            <a href="/admin/tournaments/control-room?tournament_id=<?= (int) $tournament->id ?>" class="btn btn-sm"><i class="bi bi-broadcast me-1"></i>Control Room</a>
            <a href="/admin/tournaments/bracket?tournament_id=<?= (int) $tournament->id ?>" class="btn btn-sm"><i class="bi bi-diagram-3 me-1"></i>Cây đấu</a>
            <?php if ($firstCategoryId): ?>
                <a href="/admin/tournaments/scheduler?category_id=<?= $firstCategoryId ?>" class="btn btn-sm"><i class="bi bi-calendar3 me-1"></i>Xếp lịch</a>
                <a href="/admin/scores?category_id=<?= $firstCategoryId ?>" class="btn btn-sm"><i class="bi bi-pencil-square me-1"></i>Nhập điểm</a>
            <?php endif; ?>
            <a href="/admin/print-center?tournament_id=<?= (int) $tournament->id ?>" class="btn btn-sm"><i class="bi bi-printer me-1"></i>In ấn</a>
            <?php if (! empty($tournament->slug_vi)): ?><a href="/tournaments/<?= esc($tournament->slug_vi) ?>" target="_blank" class="btn btn-sm"><i class="bi bi-box-arrow-up-right me-1"></i>Công khai</a><?php endif; ?>
        </div>
    </section>

    <nav class="tour-tabs mb-3" aria-label="Điều hướng giải đấu">
        <a class="active" href="#overview"><i class="bi bi-grid-1x2 me-1"></i>Tổng quan</a>
        <a href="#categories"><i class="bi bi-collection me-1"></i>Hạng mục</a>
        <a href="#registrations"><i class="bi bi-person-lines-fill me-1"></i>Đăng ký</a>
        <a href="#operations"><i class="bi bi-check2-square me-1"></i>Vận hành</a>
        <a href="/admin/tournaments/bracket?tournament_id=<?= (int) $tournament->id ?>"><i class="bi bi-diagram-3 me-1"></i>Cây đấu</a>
        <a href="<?= $firstCategoryId ? '/admin/scores?category_id=' . $firstCategoryId : '/admin/scores' ?>"><i class="bi bi-trophy me-1"></i>Kết quả</a>
        <a href="/admin/tournaments/control-room?tournament_id=<?= (int) $tournament->id ?>"><i class="bi bi-broadcast me-1"></i>Điều phối live</a>
        <a href="/admin/print-center?tournament_id=<?= (int) $tournament->id ?>"><i class="bi bi-printer me-1"></i>Print Center</a>
    </nav>

    <?php if (can('tournaments.manage') && in_array($status, ['draft', 'open', 'closed', 'running'], true)): ?>
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <span class="small text-muted me-1">Chuyển trạng thái:</span>
            <?php if ($status === 'draft'): ?>
                <form method="post" action="/admin/tournaments/open/<?= (int) $tournament->id ?>" class="d-inline"><?= csrf_field() ?><button class="btn btn-sm btn-success"><i class="bi bi-unlock me-1"></i>Mở đăng ký</button></form>
            <?php elseif ($status === 'open'): ?>
                <form method="post" action="/admin/tournaments/close/<?= (int) $tournament->id ?>" class="d-inline"><?= csrf_field() ?><button class="btn btn-sm btn-warning"><i class="bi bi-lock me-1"></i>Đóng đăng ký</button></form>
            <?php endif; ?>
            <?php if (in_array($status, ['open', 'closed'], true)): ?><form method="post" action="/admin/tournaments/start/<?= (int) $tournament->id ?>" class="d-inline"><?= csrf_field() ?><button class="btn btn-sm btn-primary"><i class="bi bi-play-circle me-1"></i>Bắt đầu giải</button></form><?php endif; ?>
            <?php if ($status === 'running'): ?><form method="post" action="/admin/tournaments/complete/<?= (int) $tournament->id ?>" class="d-inline" onsubmit="return confirm('Xác nhận hoàn tất giải đấu?')"><?= csrf_field() ?><button class="btn btn-sm btn-success"><i class="bi bi-flag me-1"></i>Hoàn tất giải</button></form><?php endif; ?>
            <?php if ($status !== 'running'): ?><form method="post" action="/admin/tournaments/cancel/<?= (int) $tournament->id ?>" class="d-inline" onsubmit="return confirm('Xác nhận hủy giải đấu này?')"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle me-1"></i>Hủy giải</button></form><?php endif; ?>
        </div>
    <?php endif; ?>

    <section id="overview" class="row g-3 mb-3">
        <?php $kpis = [
            ['Đăng ký', $registrationCount, $capacity > 0 ? $registrationProgress . '% công suất' : 'Không giới hạn', 'bi-people', 'primary'],
            ['Đã duyệt', (int) ($stats['approved'] ?? 0), (int) ($stats['pending'] ?? 0) . ' chờ xử lý', 'bi-person-check', 'success'],
            ['Check-in', (int) ($stats['checked_in'] ?? 0), (int) ($stats['paid'] ?? 0) . ' đã thanh toán', 'bi-qr-code-scan', 'warning'],
            ['Lịch thi đấu', $matchCount, $completedMatchCount . ' trận hoàn tất', 'bi-calendar3', 'info'],
            ['Doanh thu dự kiến', format_money($stats['expected_revenue'] ?? 0), (int) ($stats['rejected'] ?? 0) . ' hồ sơ từ chối', 'bi-cash-stack', 'success'],
        ]; ?>
        <?php foreach ($kpis as $kpi): ?>
            <div class="col-sm-6 col-xl"><div class="tour-kpi"><div class="kpi-icon"><i class="bi <?= $kpi[3] ?>"></i></div><div class="kpi-label"><?= esc($kpi[0]) ?></div><div class="kpi-value"><?= is_string($kpi[1]) ? esc($kpi[1]) : number_format($kpi[1]) ?></div><div class="kpi-note"><?= esc($kpi[2]) ?></div></div></div>
        <?php endforeach; ?>
    </section>

    <section class="tour-card mb-3">
        <div class="tour-card-header"><h2><i class="bi bi-signpost-2 me-2 text-primary"></i>Quy trình vận hành</h2><span class="small text-muted">Theo dõi mức sẵn sàng của giải</span></div>
        <div class="tour-card-body"><div class="tour-stepper">
            <?php foreach ($statusSteps as $step): ?><div class="tour-step <?= $step['done'] ? 'done' : '' ?>"><div class="tour-step-icon"><i class="bi <?= $step['icon'] ?>"></i></div><span><?= esc($step['label']) ?></span></div><?php endforeach; ?>
        </div></div>
    </section>

    <div class="row g-3 mb-3">
        <div class="col-xl-8" id="categories">
            <section class="tour-card">
                <div class="tour-card-header"><h2><i class="bi bi-collection me-2 text-primary"></i>Hạng mục thi đấu</h2><a href="/admin/tournaments/edit/<?= (int) $tournament->id ?>" class="small text-decoration-none">Quản lý hạng mục <i class="bi bi-arrow-right"></i></a></div>
                <div class="tour-card-body">
                    <?php if (empty($categories)): ?><div class="tour-empty"><i class="bi bi-inbox d-block fs-2 mb-2"></i>Chưa có hạng mục. Hãy thêm ít nhất một hạng mục trước khi mở đăng ký.</div>
                    <?php else: ?><div class="row g-3">
                        <?php foreach ($categories as $category): $categoryId = (int) $category->id; $categoryStat = $categoryStats[$categoryId] ?? ['total' => 0, 'approved' => 0, 'checked_in' => 0, 'matches' => 0]; $categoryLimit = (int) ($category->max_teams ?? $category->max_players ?? 0); $categoryProgress = $categoryLimit > 0 ? min(100, (int) round($categoryStat['approved'] / $categoryLimit * 100)) : 0; ?>
                            <div class="col-md-6"><article class="tour-category"><div class="d-flex justify-content-between gap-2"><div><h3><?= esc($category->name_vi ?? 'Hạng mục') ?></h3><div class="category-type"><?= esc($category->category_type ?? $category->format ?? 'Thi đấu') ?></div></div><?= renderStatusBadge($category->status ?? 'active') ?></div><div class="category-metrics"><div><strong><?= (int) $categoryStat['total'] ?></strong><small>Đăng ký</small></div><div><strong><?= (int) $categoryStat['checked_in'] ?></strong><small>Check-in</small></div><div><strong><?= (int) $categoryStat['matches'] ?></strong><small>Trận</small></div></div><div class="d-flex justify-content-between small mb-1"><span class="text-muted">Đã duyệt</span><strong><?= (int) $categoryStat['approved'] ?><?= $categoryLimit ? ' / ' . $categoryLimit : '' ?></strong></div><div class="tour-progress mb-3"><span style="width:<?= $categoryProgress ?>%"></span></div><div class="d-flex flex-wrap gap-2"><a class="btn btn-sm btn-outline-primary" href="/admin/tournaments/scheduler?category_id=<?= $categoryId ?>"><i class="bi bi-calendar3 me-1"></i>Lịch</a><a class="btn btn-sm btn-outline-primary" href="/admin/tournaments/bracket?category_id=<?= $categoryId ?>"><i class="bi bi-diagram-3 me-1"></i>Cây đấu</a><a class="btn btn-sm btn-outline-secondary" href="/admin/scores?category_id=<?= $categoryId ?>"><i class="bi bi-pencil-square me-1"></i>Điểm</a></div></article></div>
                        <?php endforeach; ?>
                    </div><?php endif; ?>
                </div>
            </section>
        </div>
        <div class="col-xl-4" id="operations">
            <section class="tour-card">
                <div class="tour-card-header"><h2><i class="bi bi-shield-check me-2 text-primary"></i>Kiểm tra sẵn sàng</h2><span class="small text-muted">Trước giờ thi đấu</span></div>
                <div class="tour-card-body"><div class="tour-checklist">
                    <?php $checks = [
                        [$categoryCount > 0, 'Hạng mục thi đấu', $categoryCount > 0 ? 'Đã cấu hình ' . $categoryCount . ' hạng mục' : 'Cần thêm hạng mục', $categoryCount > 0 ? '#categories' : '/admin/tournaments/edit/' . (int) $tournament->id],
                        [! empty($rule?->rule_content_vi), 'Điều lệ giải', ! empty($rule?->rule_content_vi) ? 'Đã có nội dung điều lệ' : 'Chưa nhập điều lệ', '/admin/tournaments/edit/' . (int) $tournament->id],
                        [(int) ($stats['pending'] ?? 0) === 0, 'Duyệt hồ sơ', (int) ($stats['pending'] ?? 0) . ' hồ sơ đang chờ', '/admin/tournaments/registrations/' . (int) $tournament->id],
                        [(int) ($stats['unassigned_matches'] ?? 0) === 0, 'Phân sân & giờ', (int) ($stats['unassigned_matches'] ?? 0) . ' trận chưa hoàn thiện lịch', $firstCategoryId ? '/admin/tournaments/scheduler?category_id=' . $firstCategoryId : '#categories'],
                        [(int) ($stats['paid'] ?? 0) >= (int) ($stats['approved'] ?? 0), 'Thanh toán', (int) ($stats['approved'] ?? 0) - (int) ($stats['paid'] ?? 0) . ' hồ sơ cần đối soát', '/admin/tournaments/registrations/' . (int) $tournament->id],
                    ]; ?>
                    <?php foreach ($checks as $check): ?><a href="<?= esc($check[3]) ?>" class="tour-check <?= $check[0] ? 'ok' : '' ?> text-decoration-none"><i class="bi <?= $check[0] ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill' ?>"></i><span><strong class="d-block text-dark"><?= esc($check[1]) ?></strong><span class="text-muted"><?= esc($check[2]) ?></span></span><i class="bi bi-chevron-right ms-auto"></i></a><?php endforeach; ?>
                </div></div>
            </section>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-7" id="registrations">
            <section class="tour-card">
                <div class="tour-card-header"><h2><i class="bi bi-person-lines-fill me-2 text-primary"></i>Đăng ký gần đây</h2><a href="/admin/tournaments/registrations/<?= (int) $tournament->id ?>" class="small text-decoration-none">Xem toàn bộ <i class="bi bi-arrow-right"></i></a></div>
                <div class="table-responsive"><table class="table table-hover mb-0 tour-table"><thead><tr><th>VĐV / đội</th><th>Hạng mục</th><th>Duyệt</th><th>Thanh toán</th><th>Check-in</th></tr></thead><tbody>
                    <?php foreach (array_slice($registrations ?? [], 0, 8) as $registration): $checkinValue = $registration->checkin_status ?? (! empty($registration->checked_in_at) ? 'checked_in' : 'pending'); ?>
                        <tr><td><strong><?= esc($registration->contact_name ?? 'Chưa có tên') ?></strong><div class="small text-muted"><?= esc(format_datetime($registration->created_at ?? null)) ?></div></td><td><?= esc($registration->category_name ?? '—') ?></td><td><?= renderStatusBadge($registration->approval_status ?? 'pending') ?></td><td><?= renderStatusBadge($registration->payment_status ?? 'unpaid', 'payment') ?></td><td><?= renderStatusBadge($checkinValue, 'booking') ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (empty($registrations)): ?><tr><td colspan="5"><div class="tour-empty">Chưa có hồ sơ đăng ký.</div></td></tr><?php endif; ?>
                </tbody></table></div>
            </section>
        </div>
        <div class="col-xl-5">
            <section class="tour-card">
                <div class="tour-card-header"><h2><i class="bi bi-calendar2-week me-2 text-primary"></i>Trận sắp diễn ra</h2><?php if ($firstCategoryId): ?><a href="/admin/tournaments/scheduler?category_id=<?= $firstCategoryId ?>" class="small text-decoration-none">Mở lịch <i class="bi bi-arrow-right"></i></a><?php endif; ?></div>
                <div class="table-responsive"><table class="table table-hover mb-0 tour-table"><thead><tr><th>Trận</th><th>Thời gian / sân</th><th>Trạng thái</th></tr></thead><tbody>
                    <?php foreach (($nextMatches ?? []) as $match): ?><tr><td><strong>M<?= (int) ($match->match_no ?? 0) ?></strong><div class="small text-muted"><?= esc($match->round_name ?? $match->round ?? 'Vòng đấu') ?></div></td><td><?= esc(format_date($match->scheduled_date ?? null)) ?> <strong><?= esc(substr((string) ($match->start_time ?? ''), 0, 5)) ?></strong><div class="small text-muted"><i class="bi bi-geo-alt me-1"></i><?= esc($match->court_name ?? $match->court_code ?? 'Chưa phân sân') ?></div></td><td><?= renderStatusBadge($match->status ?? 'scheduled', 'general') ?></td></tr><?php endforeach; ?>
                    <?php if (empty($nextMatches)): ?><tr><td colspan="3"><div class="tour-empty"><i class="bi bi-calendar-x d-block fs-3 mb-2"></i>Chưa có trận sắp diễn ra.</div></td></tr><?php endif; ?>
                </tbody></table></div>
            </section>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <section class="tour-card"><div class="tour-card-header"><h2><i class="bi bi-file-earmark-text me-2 text-primary"></i>Điều lệ & thông tin tài chính</h2><a href="/admin/tournaments/edit/<?= (int) $tournament->id ?>" class="small text-decoration-none">Chỉnh sửa</a></div><div class="tour-card-body"><div class="tour-info-list"><div class="tour-info-row"><span>Thời gian tổ chức</span><strong><?= esc(format_date($tournament->start_date ?? null)) ?> – <?= esc(format_date($tournament->end_date ?? null)) ?></strong></div><div class="tour-info-row"><span>Thời gian nhận đăng ký</span><strong><?= esc(format_datetime($tournament->registration_start ?? null)) ?> – <?= esc(format_datetime($tournament->registration_end ?? null)) ?></strong></div><div class="tour-info-row"><span>Phí mặc định</span><strong><?= format_money($tournament->registration_fee ?? 0) ?></strong></div><div class="tour-info-row"><span>Doanh thu dự kiến</span><strong><?= format_money($stats['expected_revenue'] ?? 0) ?></strong></div></div><div class="mt-3 p-3 rounded-3 bg-light" style="white-space:pre-wrap;max-height:240px;overflow:auto;"><?= esc($rule->rule_content_vi ?? 'Chưa nhập nội dung điều lệ.') ?></div></div></section>
        </div>
        <div class="col-lg-5">
            <section class="tour-card"><div class="tour-card-header"><h2><i class="bi bi-award me-2 text-primary"></i>Nhà tài trợ</h2><span class="small text-muted"><?= count($sponsors ?? []) ?> đối tác</span></div><div class="tour-card-body"><?php foreach (($sponsors ?? []) as $sponsor): ?><div class="tour-sponsor"><?php if (! empty($sponsor->logo)): ?><img src="<?= esc($sponsor->logo) ?>" alt=""><?php else: ?><span class="kpi-icon"><i class="bi bi-building"></i></span><?php endif; ?><div><strong><?= esc($sponsor->sponsor_name ?? 'Nhà tài trợ') ?></strong><?php if (! empty($sponsor->sponsor_level)): ?><div class="small text-muted"><?= esc($sponsor->sponsor_level) ?></div><?php endif; ?></div></div><?php endforeach; ?><?php if (empty($sponsors)): ?><div class="tour-empty"><i class="bi bi-building d-block fs-3 mb-2"></i>Chưa có nhà tài trợ.</div><?php endif; ?></div></section>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
