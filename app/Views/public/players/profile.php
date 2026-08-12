<?php
$locale = service('language')->getLocale();
$stats = $stats ?? [];
$player = $player ?? (object) [];
$primary = $ratings[0] ?? null;
$date = static fn ($value) => $value ? date('d/m/Y', strtotime((string) $value)) : '—';
$playerUrl = '/players/' . esc($player->player_code ?? $player->id);
?>
<!doctype html>
<html lang="<?= esc($locale) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= esc($pageTitle) ?></title>
    <meta name="description" content="<?= esc($metaDescription) ?>">
    <meta property="og:title" content="<?= esc($player->full_name) ?> | Athlete Profile">
    <link rel="stylesheet" href="<?= esc(asset_url('assets/css/public-portal.css')) ?>">
    <link rel="stylesheet" href="<?= esc(asset_url('assets/css/athlete-public.css')) ?>">
</head>
<body class="athlete-body">
<header class="athlete-header"><div class="athlete-container nav-inner">
    <a class="athlete-brand" href="/"><span class="brand-badge">NP</span><span><b>NATIONAL</b><small>PICKLEBALL RANKING</small></span></a>
    <nav><a href="/ranking">BXH</a><a class="active" href="/players">VĐV</a><a href="/tournaments">Giải đấu</a><a href="/live">Live</a></nav>
    <div class="nav-right"><a href="/locale/switch/<?= $locale === 'vi' ? 'en' : 'vi' ?>"><?= $locale === 'vi' ? 'EN' : 'VI' ?></a><a class="nav-cta" href="/login">Khu vực của tôi ↗</a></div>
</div></header>
<main>
    <section class="profile-hero"><div class="athlete-container">
        <a class="back-link" href="/ranking">← Quay lại bảng xếp hạng</a>
        <div class="profile-head">
            <div class="profile-avatar"><?= esc(mb_strtoupper(mb_substr((string) ($player->full_name ?? '?'), 0, 1))) ?><?php if (($player->verification_status ?? '') === 'official'): ?><span>✓</span><?php endif; ?></div>
            <div class="profile-identity">
                <div class="profile-status"><span><i></i><?= esc(($player->verification_status ?? 'unverified') === 'official' ? 'OFFICIAL ATHLETE' : 'PUBLIC ATHLETE PROFILE') ?></span><small>National Player ID · <?= esc($player->national_player_id ?: $player->player_code) ?></small></div>
                <h1><?= esc($player->full_name) ?></h1>
                <p><?= esc($player->region ?: 'Việt Nam') ?> <b>·</b> <?= esc($clubName ?: 'Independent athlete') ?> <b>·</b> <?= esc(ucfirst((string) ($player->gender ?? 'athlete'))) ?></p>
                <div class="profile-actions"><a href="#history">Lịch sử Rating ↓</a><a href="#matches">Lịch sử thi đấu ↓</a><button type="button" data-copy-profile="<?= esc(site_url(ltrim($playerUrl, '/'))) ?>">Chia sẻ hồ sơ ↗</button></div>
            </div>
            <div class="profile-rating"><span>CURRENT RATING</span><strong><?= $stats['rating'] !== null ? number_format((float) $stats['rating'], 3) : '—' ?></strong><small><?= esc($primary->skill_band ?? 'NR') ?> · Reliability <?= number_format((float) ($stats['reliability'] ?? 0), 0) ?>%</small></div>
        </div>
    </div></section>
    <section class="athlete-container profile-grid"><div class="profile-main">
        <div class="metric-grid">
            <div><span>OFFICIAL MATCHES</span><strong><?= number_format((int) ($stats['matches'] ?? 0)) ?></strong><small>Đã ghi nhận</small></div>
            <div><span>WIN RATE</span><strong><?= (int) ($stats['winRate'] ?? 0) ?>%</strong><small><?= (int) ($stats['wins'] ?? 0) ?> thắng · <?= (int) ($stats['losses'] ?? 0) ?> thua</small></div>
            <div><span>CAREER HIGH</span><strong><?= $primary?->highest_rating ? number_format((float) $primary->highest_rating, 3) : '—' ?></strong><small>Canonical best</small></div>
            <div><span>VERIFICATION</span><strong><?= esc(($player->verification_status ?? 'unverified') === 'official' ? 'Official' : 'Verified') ?></strong><small><?= $player->verified_at ? esc($date($player->verified_at)) : 'Public record' ?></small></div>
        </div>
        <section class="profile-section" id="history">
            <div class="section-title">
                <div><span class="eyebrow">01 / RATING JOURNEY</span><h2>Diễn biến năng lực</h2></div>
                <span class="section-count"><?= count($ratingHistory ?? []) ?> records</span>
            </div>
            <?php if ($ratingHistory): ?>
                <div class="history-chart-shell">
                    <div class="history-chart-toolbar">
                        <span id="history-mode">Dạng tuyến tính</span>
                        <span class="history-mode">Bước gần nhất → Cũ nhất</span>
                    </div>
                    <svg class="rating-history-chart" viewBox="0 0 760 240" role="img" aria-label="Biểu đồ lịch sử Rating">
                        <?php
                        $chartItems = array_values(array_slice(array_reverse($ratingHistory), 0, 12));
                        $values = array_map(static fn ($row) => (float) ($row->after_rating ?? 0), $chartItems);
                        $times = array_map(static fn ($row) => esc($date($row->processed_at ?: $row->created_at)), $chartItems);
                        $min = (float) min($values) - 0.01;
                        $max = (float) max($values) + 0.01;
                        $span = max(0.001, $max - $min);
                        $width = 712;
                        $height = 180;
                        $left = 24;
                        $top = 20;
                        $points = [];
                        foreach ($chartItems as $index => $item) {
                            $x = $left + (count($chartItems) === 1 ? $width / 2 : $index * ($width / max(1, count($chartItems) - 1)));
                            $y = $top + $height - (((float) ($item->after_rating ?? 0) - $min) / $span) * $height;
                            $points[] = $x . ',' . round((float) $y, 2);
                        }
                        $pointString = implode(' ', $points);
                        ?>
                        <line x1="<?= $left ?>" y1="<?= $top + $height ?>" x2="<?= $left + $width ?>" y2="<?= $top + $height ?>" class="history-axis"></line>
                        <?php if (! empty($pointString)): ?>
                            <polyline points="<?= esc($pointString) ?>" class="history-line"></polyline>
                            <?php foreach ($points as $index => $point): ?>
                                <?php [$cx, $cy] = array_map('trim', explode(',', $point)); ?>
                                <circle cx="<?= esc($cx) ?>" cy="<?= esc($cy) ?>" r="4" class="history-point">
                                    <title><?= number_format($values[$index], 3) ?> · <?= esc($times[$index] ?? '') ?> · <?= esc($chartItems[$index]->reason ?: ucfirst((string) ($chartItems[$index]->transaction_type ?? 'impact')) ) ?></title>
                                </circle>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <text x="380" y="125" text-anchor="middle" fill="#6c7f83" font-size="13">Chưa có điểm để vẽ biểu đồ.</text>
                        <?php endif; ?>
                    </svg>
                </div>
                <div class="rating-timeline">
                    <?php foreach ($chartItems as $item): ?>
                        <div class="timeline-item"><span class="timeline-dot <?= ((float) $item->rating_delta >= 0) ? 'up' : 'down' ?>"></span><div><strong><?= number_format((float) ($item->after_rating ?? 0), 3) ?></strong><p><?= esc($item->reason ?: ucfirst((string) $item->transaction_type)) ?> · <?= esc($item->discipline ?: 'singles') ?></p></div><b class="delta <?= ((float) $item->rating_delta >= 0) ? 'positive' : 'negative' ?>"><?= ((float) $item->rating_delta >= 0 ? '+' : '') . number_format((float) $item->rating_delta, 3) ?></b><time><?= esc($date($item->created_at)) ?></time></div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">Chưa có lịch sử Rating công khai.</div>
            <?php endif; ?>
        </section>
        <section class="profile-section" id="matches"><div class="section-title"><div><span class="eyebrow">02 / COMPETITIVE RECORD</span><h2>Lịch sử thi đấu</h2></div><span class="section-count"><?= count($matches ?? []) ?> matches</span></div><?php if ($matches): ?><div class="match-list"><?php foreach ($matches as $match): ?><div class="athlete-match"><span class="match-result <?= ($match->result ?? '') === 'won' ? 'win' : (($match->result ?? '') === 'lost' ? 'loss' : 'draw') ?>"><?= strtoupper((string) ($match->result ?? '—')) ?></span><div><strong><?= esc($match->opponent_name ?: 'Đối thủ chưa công bố') ?></strong><small><?= esc($match->tournament_name ?: 'Official match') ?> · <?= esc($date($match->match_date)) ?></small></div><b><?= esc($match->score ?: '—') ?></b><span class="match-arrow">↗</span></div><?php endforeach; ?></div><?php else: ?><div class="empty-state">Chưa có trận đấu official được công khai.</div><?php endif; ?></section>
        <section class="profile-section" id="articles"><div class="section-title"><div><span class="eyebrow">03 / ATHLETE VOICE</span><h2>Bài viết liên quan</h2></div><span class="section-count"><?= count($posts ?? []) ?> posts</span></div><?php if ($posts): ?><div class="article-grid"><?php foreach ($posts as $post): ?><article><span><?= esc(strtoupper((string) $post->type)) ?> · <?= esc($date($post->created_at)) ?></span><h3><?= esc($post->title) ?></h3><p><?= esc(mb_strimwidth(strip_tags((string) $post->body), 0, 150, '…', 'UTF-8')) ?></p><a href="/articles/<?= (int) $post->id ?>">Đọc nội dung ↗</a></article><?php endforeach; ?></div><?php else: ?><div class="empty-state">Vận động viên chưa có bài viết công khai.</div><?php endif; ?></section>
    </div>
    <aside class="profile-aside"><div class="aside-card"><span class="eyebrow">ATHLETE SUMMARY</span><div class="aside-number"><?= $stats['rating'] !== null ? number_format((float) $stats['rating'], 3) : '—' ?><small>RATING</small></div><dl><div><dt>Player ID</dt><dd><?= esc($player->national_player_id ?: $player->player_code) ?></dd></div><div><dt>Province</dt><dd><?= esc($player->region ?: '—') ?></dd></div><div><dt>Club</dt><dd><?= esc($clubName ?: 'Independent') ?></dd></div><div><dt>Member since</dt><dd><?= esc($date($player->created_at)) ?></dd></div></dl><a class="aside-cta" href="/verify?code=<?= urlencode((string) ($player->national_player_id ?: $player->player_code)) ?>">Xác minh hồ sơ ↗</a></div><div class="aside-card aside-method"><span class="eyebrow">TRANSPARENCY</span><h3>Dữ liệu có nguồn.</h3><p>Rating được đọc từ canonical ledger. Kết quả chỉ hiển thị khi được xác nhận theo tiêu chuẩn của hệ thống.</p><a href="/#methodology">Xem methodology ↗</a></div></aside></section>
</main>
<footer class="athlete-footer"><div class="athlete-container"><a class="athlete-brand" href="/"><span class="brand-badge">NP</span><span><b>NATIONAL</b><small>PICKLEBALL RANKING</small></span></a><p>National athlete identity · versioned competition record.</p></div></footer>
<script>document.querySelectorAll('[data-copy-profile]').forEach(function(b){b.addEventListener('click',function(){navigator.clipboard?.writeText(this.dataset.copyProfile);this.textContent='Đã sao chép ✓';});});</script>
</body></html>
