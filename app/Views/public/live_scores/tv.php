<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($pageTitle ?? 'TV Live Scores') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background:#071014; color:#f8fafc; min-height:100vh; }
        .tv-wrap { max-width:1400px; margin:0 auto; padding:32px; }
        .score-board { display:grid; grid-template-columns:repeat(auto-fit,minmax(320px,1fr)); gap:20px; }
        .match { border:1px solid rgba(255,255,255,.18); border-radius:8px; padding:24px; background:#102027; }
        .score { font-size:clamp(32px,6vw,84px); font-weight:800; letter-spacing:0; }
        .banner { background:#f8fafc; color:#111827; border-radius:8px; padding:18px 24px; }
    </style>
</head>
<body>
<main class="tv-wrap">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="display-5 fw-bold mb-0"><?= esc($data['config']->display_name ?? 'Live Score TV') ?></h1>
            <div class="text-white-50">Refresh <?= (int) ($data['refresh_seconds'] ?? 5) ?>s</div>
        </div>
        <div class="text-end">
            <div class="fw-bold">QR Bracket</div>
            <img alt="QR" width="96" height="96" src="https://api.qrserver.com/v1/create-qr-code/?size=96x96&data=<?= urlencode(site_url('/live-scores/bracket')) ?>">
        </div>
    </div>

    <?php if (! empty($data['show_sponsor'])): ?>
        <div class="banner mb-4 text-center fw-semibold">Sponsor banner</div>
    <?php endif; ?>

    <section class="score-board mb-4">
        <?php foreach (($data['live_matches'] ?? []) as $match): ?>
            <article class="match">
                <div class="d-flex justify-content-between text-white-50"><span>Trận #<?= (int) $match->id ?></span><span><?= esc($match->round_name ?? $match->round ?? '') ?></span></div>
                <div class="mt-3 fs-4"><?= esc($match->team_a_name ?? ('Team #' . ($match->team_a_id ?? '-'))) ?></div>
                <div class="score"><?= esc($match->score_text ?? '-') ?></div>
                <div class="fs-4"><?= esc($match->team_b_name ?? ('Team #' . ($match->team_b_id ?? '-'))) ?></div>
            </article>
        <?php endforeach; ?>
    </section>

    <?php if (! empty($data['show_next_matches'])): ?>
        <h2 class="h4">Trận tiếp theo</h2>
        <div class="row g-3">
            <?php foreach (array_slice($data['next_matches'] ?? [], 0, 4) as $match): ?>
                <div class="col-md-3"><div class="match h-100">#<?= (int) $match->id ?><br><?= esc($match->team_a_name ?? ('Team #' . ($match->team_a_id ?? '-'))) ?> vs <?= esc($match->team_b_name ?? ('Team #' . ($match->team_b_id ?? '-'))) ?></div></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
<script>
setTimeout(() => location.reload(), <?= max(1, (int) ($data['refresh_seconds'] ?? 5)) ?> * 1000);
</script>
</body>
</html>
