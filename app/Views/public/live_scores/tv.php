<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($pageTitle ?? 'TV Live Scores') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background:radial-gradient(circle at 85% -10%,#174b72 0%,#071014 38%,#04080a 100%); color:#f8fafc; min-height:100vh; font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif; }
        .tv-wrap { max-width:1600px; margin:0 auto; padding:clamp(20px,4vw,56px); }
        .score-board { display:grid; grid-template-columns:repeat(auto-fit,minmax(320px,1fr)); gap:20px; }
        .match { border:1px solid rgba(148,163,184,.24); border-radius:18px; padding:clamp(20px,3vw,32px); background:linear-gradient(145deg,rgba(23,43,49,.95),rgba(10,28,34,.95)); box-shadow:0 16px 36px rgba(0,0,0,.2); }
        .score { font-size:clamp(42px,7vw,104px); font-weight:900; letter-spacing:-.05em; color:#fbbf24; line-height:1.05; }
        .banner { background:linear-gradient(90deg,#f8fafc,#dbeafe); color:#102a43; border-radius:14px; padding:18px 24px; box-shadow:0 10px 24px rgba(0,0,0,.16); }
        .tv-slide { display:none; min-height:420px; align-content:center; }
        .tv-slide.active { display:grid; }
        .slide-label { color:#fbbf24; font-size:clamp(18px,2vw,30px); letter-spacing:.12em; font-weight:800; }
        .call-match { text-align:center; border:2px solid #fbbf24; border-radius:22px; padding:clamp(42px,8vw,92px) 24px; background:linear-gradient(145deg,#1c3b43,#10262d); box-shadow:0 20px 50px rgba(0,0,0,.28); }
        .call-match h2 { font-size:clamp(38px,7vw,104px); font-weight:900; letter-spacing:-.05em; }
        .tv-slide { animation:tv-fade .45s ease; }
        @keyframes tv-fade { from { opacity:.35; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }
        @media (max-width:700px) { .tv-wrap { padding:18px; } .tv-wrap header img { width:64px; height:64px; } .score-board { grid-template-columns:1fr; } }
    </style>
</head>
<body>
<main class="tv-wrap">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="display-5 fw-bold mb-0"><?= esc($data['tournament']->name_vi ?? $data['config']->display_name ?? 'Live Score TV') ?></h1>
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

    <section id="tv-slides">
        <div class="tv-slide active" data-slide="live">
            <div class="slide-label mb-3">LIVE NOW</div>
            <div class="score-board">
                <?php foreach (($data['live_matches'] ?? []) as $match): ?>
                    <article class="match">
                        <div class="d-flex justify-content-between text-white-50">
                            <span>M<?= (int) $match->match_no ?></span>
                            <span><?= esc($match->court_name ?? 'Court') ?></span>
                        </div>
                        <div class="mt-3 fs-4"><?= esc($match->team_a_label ?? ($match->team_a_name ?? ('Team #' . ($match->team_a_id ?? '-')))) ?></div>
                        <div class="score"><?= esc($match->score_text ?? '-') ?></div>
                        <div class="fs-4"><?= esc($match->team_b_label ?? ($match->team_b_name ?? ('Team #' . ($match->team_b_id ?? '-')))) ?></div>
                    </article>
                <?php endforeach; ?>
                <?php if (empty($data['live_matches'])): ?>
                    <article class="match text-center text-white-50 fs-3">
                        <p class="mb-0">Chưa có trận đang thi đấu</p>
                    </article>
                <?php endif; ?>
            </div>
        </div>

        <div class="tv-slide" data-slide="call">
            <div class="call-match">
                <div class="slide-label">CALL PLAYER</div>
                <?php $match = ($data['called_matches'] ?? [])[0] ?? null; ?>
                <?php if ($match): ?>
                    <h2 class="mt-4">SÂN <?= esc($match->court_name ?? '-') ?></h2>
                    <div class="fs-2">M<?= (int) $match->match_no ?> · <?= esc($match->team_a_label ?? '') ?> <span class="text-white-50">VS</span> <?= esc($match->team_b_label ?? '') ?></div>
                <?php else: ?>
                    <h2 class="mt-4">Các VĐV chuẩn bị vào sân</h2>
                    <p class="text-white-50 mb-0">Màn hình tự chuyển sang lịch kế tiếp sau một nhịp.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="tv-slide" data-slide="next">
            <div class="slide-label mb-3">NEXT MATCHES</div>
            <div class="score-board">
                <?php foreach (array_slice($data['next_matches'] ?? [], 0, 6) as $match): ?>
                    <article class="match">
                        <div class="d-flex justify-content-between text-white-50">
                            <span>M<?= (int) $match->match_no ?></span>
                            <span><?= esc($match->court_name ?? 'Chưa phân sân') ?> · <?= esc(substr((string) ($match->start_time ?? ''), 0, 5)) ?></span>
                        </div>
                        <div class="mt-3 fs-4"><?= esc($match->team_a_label ?? '') ?></div>
                        <div class="text-white-50 my-2">VS</div>
                        <div class="fs-4"><?= esc($match->team_b_label ?? '') ?></div>
                    </article>
                <?php endforeach; ?>
                <?php if (empty($data['next_matches'])): ?>
                    <article class="match text-center text-white-50 fs-3">
                        <p class="mb-0">Chưa có trận kế tiếp.</p>
                    </article>
                <?php endif; ?>
            </div>
        </div>

        <div class="tv-slide" data-slide="results">
            <div class="slide-label mb-3">KẾT QUẢ MỚI NHẤT</div>
            <div class="score-board">
                <?php foreach (($data['result_matches'] ?? []) as $match): ?>
                    <article class="match">
                        <div class="d-flex justify-content-between text-white-50">
                            <span>M<?= (int) $match->match_no ?></span>
                            <span><?= esc($match->category_name ?? '') ?></span>
                        </div>
                        <div class="mt-3 fs-4"><?= esc($match->team_a_label ?? '') ?></div>
                        <div class="score"><?= esc($match->score_text ?? '-') ?></div>
                        <div class="fs-4"><?= esc($match->team_b_label ?? '') ?></div>
                    </article>
                <?php endforeach; ?>
                <?php if (empty($data['result_matches'])): ?>
                    <article class="match text-center text-white-50 fs-3">
                        <p class="mb-0">Chưa có kết quả mới.</p>
                    </article>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>
<script>
const slides = [...document.querySelectorAll('.tv-slide')];
const sequence = <?= json_encode($data['slides'] ?? ['live', 'next', 'call', 'results'], JSON_UNESCAPED_UNICODE) ?>;
const refreshSeconds = Math.max(5, <?= max(1, (int) ($data['refresh_seconds'] ?? 5)) ?>);
const slideHoldSeconds = Math.max(5, Math.round(refreshSeconds * 1.2));
let slideIndex = 0;

function showSlide() {
    const type = sequence[slideIndex % Math.max(1, sequence.length)];
    slides.forEach((slide) => slide.classList.toggle('active', slide.dataset.slide === type));
    slideIndex++;
}

if (sequence.length > 1) {
    setInterval(showSlide, slideHoldSeconds * 1000);
}
setTimeout(() => location.reload(), refreshSeconds * 1000);
</script>
</body>
</html>
