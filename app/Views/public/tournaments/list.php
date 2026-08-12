<?php
$locale = $current_locale ?? 'vi';
$nameOf = static fn (object $row): string => (string) (($locale === 'en' && ! empty($row->name_en)) ? $row->name_en : ($row->name_vi ?? 'Tournament'));
$statusLabel = static fn (string $status): string => match ($status) { 'open' => 'Đang mở đăng ký', 'running' => 'Đang diễn ra', 'closed' => 'Đã đóng đăng ký', 'completed' => 'Đã kết thúc', 'cancelled' => 'Đã hủy', default => ucfirst($status) };
$statusClass = static fn (string $status): string => match ($status) { 'open' => 'is-open', 'running' => 'is-live', 'completed' => 'is-complete', 'cancelled' => 'is-cancelled', default => 'is-closed' };
$featuredName = $featured ? $nameOf($featured) : 'National Pickleball Events';
?>
<!doctype html>
<html lang="<?= esc($locale) ?>">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($pageTitle) ?></title>
    <meta name="description" content="Lịch giải đấu Pickleball, thể thức, sân đấu, đăng ký và kết quả chính thức.">
    <link rel="stylesheet" href="<?= esc(asset_url('assets/css/tournament-public.css')) ?>">
</head>
<body class="tournament-public-body">
<header class="tp-header"><div class="tp-container tp-nav"><a class="tp-brand" href="/"><span class="tp-mark">NP</span><span><strong>NATIONAL</strong><small>PICKLEBALL RANKING</small></span></a><nav><a href="/ranking">BXH</a><a href="/players">VĐV</a><a class="is-active" href="/tournaments">Giải đấu</a><a href="/clubs">CLB</a><a href="/live">Live</a></nav><div class="tp-actions"><a href="/locale/switch/<?= $locale === 'vi' ? 'en' : 'vi' ?>"><?= $locale === 'vi' ? 'EN' : 'VI' ?></a><a class="tp-login" href="<?= session()->get('isLoggedIn') ? '/admin/dashboard' : '/login' ?>"><?= session()->get('isLoggedIn') ? 'Khu vực của tôi' : 'Đăng nhập' ?></a></div></div></header>
<main>
    <section class="tp-hero"><div class="tp-orb tp-orb-one"></div><div class="tp-orb tp-orb-two"></div><div class="tp-container tp-hero-inner"><div><span class="tp-kicker"><i></i> OFFICIAL COMPETITION CALENDAR</span><h1>Giải đấu<br><em>đang chuyển động.</em></h1><p>Khám phá lịch thi đấu, hạng mục, sân đấu và đăng ký vào những giải Pickleball được ghi nhận trên hệ thống quốc gia.</p><div class="tp-hero-links"><a class="tp-button tp-button-orange" href="#events">Xem lịch giải <span>↓</span></a><a class="tp-ghost-link" href="/calendar">Mở calendar <span>↗</span></a></div></div><div class="tp-hero-note"><span>EVENTS INDEX / 2026</span><strong><?= number_format((int) ($counts['all'] ?? 0)) ?></strong><small>giải đấu trong hệ thống</small><div class="tp-note-line"><b></b><span>Verified competition data</span></div></div></div></section>
    <section class="tp-container tp-feature-wrap"><div class="tp-feature-label"><span>FEATURED EVENT</span><i></i></div>
        <?php if ($featured): ?>
            <a class="tp-feature" href="/tournaments/<?= esc($featured->slug_vi) ?>">
                <div class="tp-feature-date"><b><?= $featured->start_date ? date('d', strtotime($featured->start_date)) : '—' ?></b><span><?= $featured->start_date ? date('M Y', strtotime($featured->start_date)) : 'DATE TBA' ?></span></div>
                <div class="tp-feature-content"><div class="tp-status <?= esc($statusClass((string) $featured->status)) ?>"><i></i><?= esc($statusLabel((string) $featured->status)) ?></div><h2><?= esc($featuredName) ?></h2><p><?= esc($featured->branch_name ?? 'Địa điểm đang cập nhật') ?> <span>·</span> <?= esc($featured->start_date ?? 'Lịch đang cập nhật') ?></p><div class="tp-feature-metrics"><span><b><?= number_format((int) ($featured->registration_count ?? 0)) ?></b> đăng ký</span><span><b><?= number_format((int) ($featured->category_count ?? 0)) ?></b> hạng mục</span><span><b><?= number_format((int) ($featured->match_count ?? 0)) ?></b> trận</span></div></div><span class="tp-feature-arrow">↗</span>
            </a>
        <?php else: ?><div class="tp-empty">Chưa có giải đấu nổi bật.</div><?php endif; ?>
    </section>
    <section class="tp-container tp-content" id="events">
        <div class="tp-section-heading"><div><span class="tp-eyebrow">ALL EVENTS</span><h2>Lịch thi đấu <em>chính thức</em></h2><p>Lọc theo trạng thái hoặc tìm nhanh theo tên giải đấu.</p></div><div class="tp-counts"><span><b><?= number_format((int) ($counts['open'] ?? 0)) ?></b> mở đăng ký</span><span><b><?= number_format((int) ($counts['running'] ?? 0)) ?></b> đang diễn ra</span></div></div>
        <form class="tp-filters" method="get" action="/tournaments"><div class="tp-search"><span>⌕</span><input name="q" value="<?= esc($search ?? '') ?>" placeholder="Tìm tên giải đấu…"></div><div class="tp-status-filter"><button type="button" class="tp-filter-toggle">Trạng thái <span>⌄</span></button><div class="tp-filter-options"><a class="<?= ($active_status ?? 'all') === 'all' ? 'is-active' : '' ?>" href="/tournaments<?= $search ? '?q=' . urlencode($search) : '' ?>">Tất cả <b><?= (int) ($counts['all'] ?? 0) ?></b></a><?php foreach (['open' => 'Mở đăng ký', 'running' => 'Đang diễn ra', 'closed' => 'Đã đóng', 'completed' => 'Đã kết thúc'] as $key => $label): ?><a class="<?= ($active_status ?? '') === $key ? 'is-active' : '' ?>" href="/tournaments?status=<?= $key ?><?= $search ? '&q=' . urlencode($search) : '' ?>"><?= $label ?> <b><?= (int) ($counts[$key] ?? 0) ?></b></a><?php endforeach; ?></div></div><button class="tp-button tp-button-dark" type="submit">Tìm kiếm <span>→</span></button></form>
        <?php if ($tournaments): ?>
            <div class="tp-grid">
            <?php foreach ($tournaments as $tournament):
                $cardName = $nameOf($tournament);
                $cardStatus = (string) $tournament->status;
                $cardDate = $tournament->start_date && $tournament->end_date ? date('d/m/Y', strtotime($tournament->start_date)) . ' — ' . date('d/m/Y', strtotime($tournament->end_date)) : 'Lịch đang cập nhật';
                $cardImage = $tournament->banner ?: asset_url('assets/img/tournament-fallback.png');
            ?>
                <article class="tp-card">
                    <a class="tp-card-image" href="/tournaments/<?= esc($tournament->slug_vi) ?>"><img src="<?= esc($cardImage) ?>" alt="<?= esc($cardName) ?>"><span class="tp-card-status <?= esc($statusClass($cardStatus)) ?>"><i></i><?= esc($statusLabel($cardStatus)) ?></span><span class="tp-card-arrow">↗</span></a>
                    <div class="tp-card-body"><div class="tp-card-meta"><span><?= esc($tournament->verification_level ? strtoupper((string) $tournament->verification_level) : 'COMMUNITY') ?></span><span><?= esc($tournament->tier_code ?? 'OPEN EVENT') ?></span></div><h3><a href="/tournaments/<?= esc($tournament->slug_vi) ?>"><?= esc($cardName) ?></a></h3><p class="tp-card-place">⌖ <?= esc($tournament->branch_name ?? 'Địa điểm đang cập nhật') ?></p><div class="tp-card-date"><b><?= $tournament->start_date ? date('d/m', strtotime($tournament->start_date)) : '—' ?></b><span><?= esc($cardDate) ?></span></div><div class="tp-card-footer"><span><b><?= number_format((int) ($tournament->registration_count ?? 0)) ?></b> đăng ký</span><span><b><?= number_format((int) ($tournament->category_count ?? 0)) ?></b> hạng mục</span><span><b><?= number_format((int) ($tournament->match_count ?? 0)) ?></b> trận</span></div><a class="tp-card-cta" href="/tournaments/<?= esc($tournament->slug_vi) ?>"><?= $cardStatus === 'open' ? 'Xem chi tiết & đăng ký' : 'Xem thông tin giải' ?> <span>↗</span></a></div>
                </article>
            <?php endforeach; ?>
            </div>
        <?php else: ?><div class="tp-empty"><strong>Không tìm thấy giải đấu phù hợp.</strong><span>Thử bỏ bộ lọc hoặc tìm với từ khóa khác.</span><a href="/tournaments">Xem tất cả giải đấu ↗</a></div><?php endif; ?>
    </section>
    <section class="tp-container tp-trust"><div><span class="tp-eyebrow">PUBLIC DATA CONTRACT</span><h2>Thông tin đủ để<br><em>ra quyết định.</em></h2></div><div class="tp-trust-grid"><div><b>01</b><strong>Thể thức</strong><span>Hạng mục, độ tuổi, giới tính, Rating range</span></div><div><b>02</b><strong>Vận hành</strong><span>Lịch sân, vòng đấu, live score, trạng thái</span></div><div><b>03</b><strong>Minh bạch</strong><span>Verification, result version và lịch sử</span></div></div></section>
</main>
<footer class="tp-footer"><div class="tp-container"><div class="tp-footer-main"><a class="tp-brand tp-brand-white" href="/"><span class="tp-mark">NP</span><span><strong>NATIONAL</strong><small>PICKLEBALL RANKING</small></span></a><div><a href="/ranking">BXH</a><a href="/players">VĐV</a><a href="/clubs">CLB</a><a href="/verify">Xác minh</a></div></div><div class="tp-footer-bottom"><span>© <?= date('Y') ?> National Pickleball Ranking</span><span>Dữ liệu công khai · Official competition portal</span></div></div></footer>
</body>
</html>
