<?php
$stats = $portal['stats'] ?? [];
$venue = $portal['venue_overview'] ?? [];
$rankings = $portal['top_rankings'] ?? [];
$movers = $portal['top_movers'] ?? [];
$liveEvents = $portal['live_events'] ?? [];
$upcoming = $portal['upcoming_events'] ?? [];
$provinces = $portal['province_ranking'] ?? [];
$clubs = $portal['top_clubs'] ?? [];
$results = $portal['latest_results'] ?? [];
$isLoggedIn = (bool) session()->get('isLoggedIn');
$currentLocale = service('language')->getLocale();
$otherLocale = $currentLocale === 'vi' ? 'en' : 'vi';

$displayDate = static function ($value): string {
    if (! $value) return '—';
    $timestamp = strtotime((string) $value);
    return $timestamp ? date('d/m/Y', $timestamp) : (string) $value;
};
$displayDateTime = static function ($value): string {
    if (! $value) return '—';
    $timestamp = strtotime((string) $value);
    return $timestamp ? date('d/m · H:i', $timestamp) : (string) $value;
};
$verificationLabel = static function ($value): string {
    return match ((string) $value) {
        'official', 'national' => 'OFFICIAL',
        'verified' => 'VERIFIED',
        'sanctioned' => 'SANCTIONED',
        default => 'COMMUNITY',
    };
};
$statIcons = ['players' => '◎', 'clubs' => '⌘', 'tournaments' => '▱', 'matches' => '↗', 'provinces' => '⌖'];
?>
<!doctype html>
<html lang="<?= esc($currentLocale) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($pageTitle) ?></title>
    <meta name="description" content="<?= esc($metaDescription) ?>">
    <meta property="og:title" content="<?= esc($pageTitle) ?>">
    <meta property="og:description" content="<?= esc($metaDescription) ?>">
    <link rel="canonical" href="<?= esc(site_url('/')) ?>">
    <link rel="stylesheet" href="<?= esc(asset_url('assets/css/public-portal.css')) ?>">
</head>
<body class="portal-body">
<a class="skip-link" href="#main-content">Bỏ qua đến nội dung</a>

<header class="portal-header" data-portal-header>
    <div class="portal-container portal-nav">
        <a class="portal-brand" href="/" aria-label="National Pickleball Ranking — Trang chủ">
            <span class="brand-mark"><span>N</span><i>P</i></span>
            <span><strong>NATIONAL</strong><small>PICKLEBALL RANKING</small></span>
        </a>
        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="portal-menu" data-nav-toggle><span></span><span></span><span></span><b class="sr-only">Mở menu</b></button>
        <nav id="portal-menu" class="portal-menu" aria-label="Điều hướng chính">
            <a class="is-active" href="/ranking">BXH</a><a href="/players">VĐV</a><a href="/tournaments">Giải đấu</a><a href="/calendar">Lịch thi đấu</a><a href="/clubs">CLB</a><a href="/matches">Kết quả</a><a class="nav-live" href="/live"><i></i> Live</a>
        </nav>
        <div class="nav-actions"><a class="nav-search-link" href="#player-search" aria-label="Tìm kiếm">⌕</a><a class="nav-locale" href="<?= esc(site_url('locale/switch/' . $otherLocale)) ?>"><?= $currentLocale === 'vi' ? 'EN' : 'VI' ?></a><a class="nav-account" href="<?= $isLoggedIn ? '/admin/dashboard' : '/login' ?>"><?= $isLoggedIn ? 'Khu vực của tôi' : 'Đăng nhập' ?></a></div>
    </div>
</header>

<main id="main-content">
    <section class="hero-section">
        <div class="hero-noise"></div><div class="hero-grid"></div><div class="hero-glow hero-glow-one"></div><div class="hero-glow hero-glow-two"></div>
        <div class="portal-container hero-layout">
            <div class="hero-content">
                <div class="hero-kicker"><span></span> CỔNG DỮ LIỆU PICKLEBALL QUỐC GIA <b>LIVE DATA</b></div>
                <h1>Thi đấu.<br><em>Được ghi nhận.</em></h1>
                <p class="hero-lead">Nơi hội tụ bảng xếp hạng, Rating, giải đấu, CLB và kết quả chính thức của cộng đồng Pickleball Việt Nam.</p>
                <form class="player-search" id="player-search" action="#player-search" data-player-search autocomplete="off">
                    <label for="portal-search">Tìm vận động viên · tra cứu dữ liệu công khai</label>
                    <div class="search-control"><span class="search-icon">⌕</span><input id="portal-search" name="q" type="search" placeholder="Tên VĐV, National Player ID, CLB…" aria-describedby="search-help" data-search-input><button type="submit">Tra cứu <span>→</span></button></div>
                    <small id="search-help">Tìm hồ sơ, lịch thi đấu, giải đấu và kết quả đã xác minh.</small><div class="search-results" data-search-results hidden></div>
                </form>
                <div class="hero-actions"><a class="button button-primary" href="#national-ranking">Xem BXH quốc gia <span>↗</span></a><a class="button button-quiet" href="/tournaments">Khám phá giải đấu <span>↗</span></a></div>
            </div>
            <aside class="hero-snapshot" aria-label="Tóm tắt dữ liệu quốc gia">
                <div class="snapshot-top"><span class="eyebrow-light">NATIONAL SNAPSHOT</span><span class="live-status"><i></i> ONLINE</span></div>
                <div class="snapshot-rating"><span>RATING LEADER</span><strong><?= esc($rankings[0]['name'] ?? 'Đang cập nhật') ?></strong><small><?= esc($rankings[0]['province'] ?? 'Toàn quốc') ?> · <?= esc($rankings[0]['skill_band'] ?? '—') ?></small><b><?= isset($rankings[0]['rating']) ? number_format((float) $rankings[0]['rating'], 3) : '—' ?><em> RATING</em></b></div>
                <div class="snapshot-grid"><div><span>BXH cập nhật</span><b><?= esc($displayDate($portal['last_updated'] ?? null)) ?></b></div><div><span>Trận chính thức</span><b><?= number_format((int) ($stats[3]['value'] ?? 0)) ?></b></div><div><span>VĐV có Rating</span><b><?= number_format((int) ($stats[0]['value'] ?? 0)) ?></b></div><div><span>Độ tin cậy</span><b><?= number_format((float) ($rankings[0]['reliability'] ?? 0), 0) ?>%</b></div></div>
                <a class="snapshot-link" href="/ranking">Mở national leaderboard <span>↗</span></a>
            </aside>
        </div>
        <div class="hero-bottom portal-container"><span>Official data network</span><span>Rating engine v1</span><span>Versioned results</span><span>Privacy-safe public portal</span></div>
    </section>

    <?php if ($stats): ?><section class="stats-section portal-container" aria-label="Số liệu hệ thống"><div class="stats-strip"><?php foreach ($stats as $stat): ?><div class="stat-item"><span class="stat-icon"><?= esc($statIcons[$stat['key'] ?? ''] ?? '•') ?></span><div><strong><?= number_format((int) ($stat['value'] ?? 0)) ?></strong><span><?= esc($stat['label'] ?? '') ?></span></div></div><?php endforeach; ?><div class="stats-updated"><span>STATUS</span><strong>Hệ thống đang hoạt động</strong><small>Snapshot: <?= esc($displayDateTime($portal['last_updated'] ?? null)) ?></small></div></div></section><?php endif; ?>

    <section class="portal-section portal-container" id="national-ranking">
        <div class="section-heading"><div><span class="section-eyebrow">01 / NATIONAL LEADERBOARD</span><h2>Ai đang dẫn đầu <em>Việt Nam?</em></h2><p>Bảng xếp hạng đọc từ hồ sơ Rating canonical, có Reliability và số trận để kiểm chứng.</p></div><a class="text-link" href="/ranking">Mở đầy đủ BXH <span>↗</span></a></div>
        <div class="ranking-layout">
            <div class="ranking-shell" data-ranking-shell data-discipline="<?= esc($portal['ranking_discipline'] ?? 'singles') ?>">
                <div class="ranking-toolbar"><div class="discipline-tabs" role="tablist" aria-label="Nội dung xếp hạng"><button class="is-active" type="button" role="tab" aria-selected="true" data-discipline="singles">Đơn</button><button type="button" role="tab" aria-selected="false" data-discipline="doubles">Đôi</button><button type="button" role="tab" aria-selected="false" data-discipline="mixed_doubles">Đôi nam nữ</button></div><div class="filter-row"><span>SEASON 2026</span><span class="verified-label"><i></i> VERIFIED DATA</span></div></div>
            <?php if ($rankings): ?><div class="top-three"><?php foreach (array_slice($rankings, 0, 3) as $position => $player): ?><a class="podium-card podium-<?= $position + 1 ?>" href="/players/<?= esc($player['player_code'] ?: $player['player_id']) ?>"><span class="podium-rank">0<?= $position + 1 ?></span><span class="avatar avatar-<?= $position + 1 ?>"><?= esc(mb_strtoupper(mb_substr($player['name'], 0, 1))) ?></span><strong><?= esc($player['name']) ?></strong><small><?= esc($player['province']) ?> · <?= esc($player['skill_band'] ?? 'NR') ?></small><b><?= number_format((float) $player['rating'], 3) ?><em> RATING</em></b></a><?php endforeach; ?></div><div class="ranking-table-wrap"><table class="ranking-table"><thead><tr><th>Hạng</th><th>Vận động viên</th><th>Tỉnh / thành</th><th>CLB</th><th>Rating</th><th>Skill</th><th>Reliability</th><th>Trận</th><th>Điểm</th></tr></thead><tbody><?php foreach ($rankings as $player): ?><tr><td><b class="rank-number">#<?= (int) $player['rank'] ?></b></td><td><a class="player-cell" href="/players/<?= esc($player['player_code'] ?: $player['player_id']) ?>"><span class="mini-avatar"><?= esc(mb_strtoupper(mb_substr($player['name'], 0, 1))) ?></span><span><strong><?= esc($player['name']) ?></strong><small><?= esc($player['national_player_id'] ?: ($player['player_code'] ?: 'National Player')) ?></small></span></a></td><td><?= esc($player['province']) ?></td><td><?= esc($player['club']) ?></td><td class="mono strong"><?= number_format((float) $player['rating'], 3) ?></td><td><span class="skill-chip"><?= esc($player['skill_band'] ?? 'NR') ?></span></td><td class="mono"><?= number_format((float) ($player['reliability'] ?? 0), 0) ?>%</td><td class="mono"><?= number_format((int) ($player['match_count'] ?? 0)) ?></td><td class="mono strong"><?= (float) $player['points'] > 0 ? number_format((float) $player['points'], 0) : '—' ?></td></tr><?php endforeach; ?></tbody></table></div><div class="ranking-foot"><span><i class="status-dot"></i> Canonical profile · cập nhật <?= esc($displayDate($portal['last_updated'] ?? null)) ?></span><a href="/ranking#methodology">Xem methodology <span>↗</span></a></div><?php else: ?><div class="module-empty"><span class="empty-icon">◎</span><strong>BXH đang được cập nhật</strong><p>Dữ liệu xếp hạng chính thức sẽ hiển thị khi có snapshot hợp lệ.</p></div><?php endif; ?>
            </div>
            <aside class="ranking-aside"><div class="aside-label">HOW TO READ</div><h3>Không chỉ là một con số.</h3><p>Rating phản ánh trình độ hiện tại. Ranking phản ánh vị trí cạnh tranh trong mùa giải.</p><div class="metric-card"><span>RATING</span><strong>5.999</strong><small>Performance level</small></div><div class="metric-card metric-dark"><span>RELIABILITY</span><strong>69%</strong><small>Mức độ tin cậy của dữ liệu</small></div><a class="aside-link" href="/ranking#methodology">Đọc tiêu chuẩn xếp hạng <span>↗</span></a></aside>
        </div>
    </section>

    <section class="portal-container rating-history-panel" id="rating-history" data-rating-history hidden><div class="card-heading"><div><span class="section-eyebrow">RATING JOURNEY</span><h3>Lịch sử <em>Rating</em></h3></div><span class="history-discipline" data-history-discipline>Singles</span></div><p class="history-caption" data-history-caption>Chọn một vận động viên trong bảng để xem diễn biến Rating.</p><div class="history-chart-wrap"><svg class="history-chart" viewBox="0 0 720 240" role="img" aria-label="Biểu đồ lịch sử rating" data-history-chart></svg></div></section>

    <section class="portal-container portal-section operations-section"><div class="section-heading"><div><span class="section-eyebrow">02 / THE NETWORK</span><h2>Một hệ sinh thái,<br><em>nhiều cách tham gia.</em></h2></div><a class="text-link" href="/solutions">Dành cho tổ chức <span>↗</span></a></div><div class="network-grid"><a class="network-card network-card-teal" href="/players"><span class="network-number">01</span><span class="network-icon">◎</span><strong>Vận động viên</strong><p>Hồ sơ, National Player ID, Rating và lịch sử thi đấu.</p><span class="network-arrow">↗</span></a><a class="network-card network-card-orange" href="/tournaments"><span class="network-number">02</span><span class="network-icon">▱</span><strong>Giải đấu</strong><p>Đăng ký, lịch thi đấu, live score và kết quả chính thức.</p><span class="network-arrow">↗</span></a><a class="network-card network-card-cream" href="/clubs"><span class="network-number">03</span><span class="network-icon">⌘</span><strong>CLB & sân</strong><p>Khám phá cộng đồng, CLB xác minh và mạng lưới sân.</p><span class="network-arrow">↗</span></a></div></section>

    <section class="portal-container insight-grid"><article class="data-card movers-card"><div class="card-heading"><div><span class="section-eyebrow">03 / MOMENTUM</span><h3>Những bước tiến <em>nổi bật</em></h3></div><span class="round-icon">↗</span></div><?php if ($movers): foreach ($movers as $mover): ?><div class="mover-row"><span class="mover-avatar"><?= esc(mb_strtoupper(mb_substr((string) ($mover['full_name'] ?? '?'), 0, 1))) ?></span><span class="mover-name"><strong><?= esc($mover['full_name'] ?? 'VĐV') ?></strong><small>National Ranking</small></span><span class="mover-ranks">#<?= (int) ($mover['previous_rank'] ?? 0) ?> <i>→</i> <b>#<?= (int) ($mover['current_rank'] ?? 0) ?></b></span><span class="move-value">+<?= (int) ($mover['movement'] ?? 0) ?></span></div><?php endforeach; else: ?><div class="card-empty"><strong>Snapshot đang được xây dựng</strong><span>Khi có hai kỳ BXH, hệ thống sẽ hiển thị các VĐV tăng hạng mạnh nhất.</span></div><?php endif; ?><a class="card-link" href="/ranking">Xem toàn bộ biến động <span>↗</span></a></article><article class="data-card live-card" id="live-now"><div class="card-heading"><div><span class="section-eyebrow live-eyebrow"><i></i> 04 / LIVE & SCHEDULE</span><h3>Sân đấu <em>hôm nay</em></h3></div><span class="live-pulse">LIVE BOARD</span></div><?php if ($liveEvents): foreach ($liveEvents as $match): ?><div class="live-match"><div class="court-label"><span><?= esc($match->court_name ?? ('COURT ' . ($match->court_id ?? '—'))) ?></span><b><?= esc($match->scheduled_date ?? '') ?> · <?= esc(substr((string) ($match->start_time ?? ''), 0, 5)) ?></b></div><div class="match-teams"><strong><?= esc($match->team_a_name ?? 'Đội A') ?><br><span>vs</span> <?= esc($match->team_b_name ?? 'Đội B') ?></strong><b><?= esc($match->score_text ?? 'Chưa bắt đầu') ?></b></div></div><?php endforeach; else: ?><div class="card-empty"><strong>Chưa có trận live</strong><span>Lịch đấu và trạng thái sân sẽ cập nhật tại live board.</span></div><?php endif; ?><a class="card-link" href="/live-scores">Mở live match board <span>↗</span></a></article></section>

    <section class="portal-section portal-container" id="tournaments"><div class="section-heading"><div><span class="section-eyebrow">05 / UPCOMING EVENTS</span><h2>Giải đấu <em>sắp diễn ra</em></h2><p>Chọn giải, xem thể thức và đăng ký trực tiếp.</p></div><a class="text-link" href="/tournaments">Xem lịch giải đấu <span>↗</span></a></div><?php if ($upcoming): ?><div class="tournament-grid"><?php foreach ($upcoming as $event): ?><a class="tournament-card" href="/tournaments/<?= esc($event->slug ?? $event->id) ?>"><div class="date-tile"><b><?= $event->start_date ? date('d', strtotime($event->start_date)) : '—' ?></b><span><?= $event->start_date ? date('M', strtotime($event->start_date)) : '—' ?></span></div><div class="tournament-info"><div><span class="status-badge status-<?= esc($event->verification_level ?? 'community') ?>"><i></i><?= esc($verificationLabel($event->verification_level ?? 'community')) ?></span><span class="tier-label">TOURNAMENT</span></div><h3><?= esc($event->name) ?></h3><p><?= esc($event->status ?? 'open') ?> <span>·</span> <?= esc($event->start_date ?? '—') ?> — <?= esc($event->end_date ?? '—') ?></p></div><span class="arrow-circle">↗</span></a><?php endforeach; ?></div><?php else: ?><div class="wide-empty"><span>▱</span><div><strong>Chưa có giải đấu sắp diễn ra</strong><p>Lịch thi đấu chính thức sẽ được cập nhật tại đây.</p></div><a href="/tournaments">Xem tất cả ↗</a></div><?php endif; ?></section>

    <section class="portal-container discovery-grid"><article class="data-card province-card"><div class="card-heading"><div><span class="section-eyebrow">06 / COVERAGE</span><h3>Độ phủ <em>toàn quốc</em></h3></div><a class="round-icon" href="/players">↗</a></div><div class="coverage-summary"><div><strong><?= number_format((int) ($venue['facilities'] ?? 0)) ?></strong><span>Cụm sân</span></div><div><strong><?= number_format((int) ($venue['branches'] ?? 0)) ?></strong><span>Chi nhánh</span></div><div><strong><?= number_format((int) ($venue['courts'] ?? 0)) ?></strong><span>Sân đấu</span></div></div><?php if ($provinces): foreach ($provinces as $index => $province): ?><div class="province-row"><span><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span><strong><?= esc($province->province) ?></strong><div class="bar"><i style="width:<?= min(100, ((int) $province->player_count / max(1, (int) ($provinces[0]->player_count ?? 1))) * 100) ?>%"></i></div><b><?= number_format((int) $province->player_count) ?></b></div><?php endforeach; else: ?><div class="card-empty">Chưa có dữ liệu tỉnh / thành.</div><?php endif; ?></article><article class="data-card club-card" id="club-ranking"><div class="card-heading"><div><span class="section-eyebrow">07 / VERIFIED COMMUNITY</span><h3>Top <em>CLB</em></h3></div><a class="round-icon" href="/clubs">↗</a></div><?php if ($clubs): foreach ($clubs as $index => $club): ?><div class="club-row"><span class="club-rank">#<?= $index + 1 ?></span><span class="club-logo"><?= esc(mb_strtoupper(mb_substr((string) ($club->name ?? 'C'), 0, 1))) ?></span><span><strong><?= esc($club->name) ?></strong><small><?= esc($club->province ?? 'Việt Nam') ?></small></span><span class="verified-dot">✓</span></div><?php endforeach; else: ?><div class="card-empty">Chưa có CLB được xác minh.</div><?php endif; ?><a class="card-link" href="/clubs">Khám phá danh bạ CLB <span>↗</span></a></article></section>

    <section class="portal-section portal-container results-section"><div class="section-heading"><div><span class="section-eyebrow">08 / OFFICIAL RESULTS</span><h2>Kết quả <em>có thể kiểm chứng</em></h2></div><a class="text-link" href="/matches">Mở result center <span>↗</span></a></div><?php if ($results): ?><div class="result-list"><?php foreach ($results as $result): ?><a class="result-row" href="/tournaments/<?= esc($result->slug ?? $result->id) ?>"><span class="result-date"><?= esc($displayDate($result->end_date ?? null)) ?></span><span class="result-name"><strong><?= esc($result->name) ?></strong><small>Versioned result · <?= esc($verificationLabel($result->verification_level ?? 'community')) ?></small></span><span class="status-badge status-<?= esc($result->verification_level ?? 'community') ?>"><i></i><?= esc($verificationLabel($result->verification_level ?? 'community')) ?></span><span class="arrow-circle">↗</span></a><?php endforeach; ?></div><?php else: ?><div class="wide-empty"><span>✓</span><div><strong>Chưa có kết quả chính thức</strong><p>Kết quả đã xác minh sẽ xuất hiện sau khi giải kết thúc.</p></div></div><?php endif; ?></section>

    <section class="verify-section" id="verify"><div class="portal-container verify-inner"><div><span class="section-eyebrow">09 / VERIFY</span><h2>Kiểm chứng<br><em>trước khi tin.</em></h2><p>Nhập Match ID, Tournament Result ID hoặc National Player ID để xem dữ liệu công khai và nguồn xác minh.</p></div><div class="verify-form"><label for="verify-code">Mã xác minh</label><div><input id="verify-code" placeholder="VD: VN-M-0000128"><a href="/verify">Kiểm tra <span>→</span></a></div><small>Thông tin trả về chỉ bao gồm dữ liệu công khai.</small></div></div></section>

    <section class="methodology-section"><div class="portal-container methodology-inner"><div><span class="section-eyebrow">TRANSPARENCY BY DESIGN</span><h2>Mọi thứ đều có<br><em>nguồn và lịch sử.</em></h2></div><div class="methodology-points"><div><b>01</b><span>Source<br><strong>Official · Verified</strong></span></div><div><b>02</b><span>Rating<br><strong>Canonical profile</strong></span></div><div><b>03</b><span>Result<br><strong>Versioned & auditable</strong></span></div></div><a class="text-link" href="/ranking#methodology">Đọc tiêu chuẩn <span>↗</span></a></div></section>
</main>

<footer class="portal-footer"><div class="portal-container footer-main"><a class="portal-brand" href="/"><span class="brand-mark"><span>N</span><i>P</i></span><span><strong>NATIONAL</strong><small>PICKLEBALL RANKING</small></span></a><div class="footer-nav"><div><b>Tra cứu</b><a href="/ranking">Bảng xếp hạng</a><a href="/players">Vận động viên</a><a href="/tournaments">Giải đấu</a><a href="/clubs">Câu lạc bộ</a></div><div><b>Vận hành</b><a href="/calendar">Lịch thi đấu</a><a href="/live">Live scores</a><a href="/verify">Xác minh kết quả</a><a href="/solutions">Giải pháp nền tảng</a></div></div></div><div class="portal-container footer-bottom"><span>© <?= date('Y') ?> National Pickleball Ranking</span><span>Dữ liệu công khai · Chính sách quyền riêng tư · Điều khoản</span></div></footer>
<script src="<?= esc(asset_url('assets/js/public-portal.js')) ?>"></script>
</body>
</html>
