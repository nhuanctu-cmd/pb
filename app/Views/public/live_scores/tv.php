<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($pageTitle ?? 'TV Live Scores') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --bg: #030712;
            --card: #0f172a;
            --card-2: #111827;
            --line: rgba(148, 163, 184, 0.28);
            --amber: #fbbf24;
            --mint: #22d3ee;
            --text: #f8fafc;
        }
        html, body {
            width: 100%;
            min-height: 100%;
            background: radial-gradient(circle at 20% 0%, #163e5e 0%, #081421 35%, #030712 90%);
            color: var(--text);
            font-family: Inter, system-ui, -apple-system, "Segoe UI", sans-serif;
        }
        .tv-wrap {
            width: min(1920px, 96vw);
            margin: 0 auto;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding: clamp(20px, 3vw, 46px);
        }
        .tv-header {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 22px;
            align-items: center;
        }
        .tv-hero {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .tv-title {
            font-size: clamp(30px, 4vw, 56px);
            font-weight: 900;
            letter-spacing: .01em;
            margin: 0;
            color: #fff;
            text-transform: uppercase;
        }
        .tv-meta {
            color: rgba(248, 250, 252, 0.72);
            font-weight: 600;
            letter-spacing: .03em;
        }
        .badge-board {
            display: inline-flex;
            gap: 8px;
            align-items: center;
            border: 1px solid var(--line);
            border-radius: 999px;
            padding: 7px 14px;
            background: rgba(15, 23, 42, 0.8);
            font-weight: 700;
            font-size: 0.85rem;
            text-transform: uppercase;
        }
        .tv-board {
            border-radius: 22px;
            border: 1px solid var(--line);
            background: linear-gradient(150deg, rgba(15, 23, 42, 0.9), rgba(17, 24, 39, 0.9));
            box-shadow: 0 24px 60px rgba(0, 0, 0, .28);
            padding: 24px;
            flex: 1;
            display: grid;
            align-content: stretch;
            position: relative;
            overflow: hidden;
        }
        .tv-stage {
            display: none;
            animation: slideIn .42s ease;
            min-height: 100%;
            width: 100%;
        }
        .tv-stage.active {
            display: block;
        }
        @keyframes slideIn {
            from {opacity: .35; transform: translateY(10px);}
            to {opacity: 1; transform: translateY(0);}
        }
        .stage-label {
            display: inline-flex;
            gap: 8px;
            align-items: center;
            color: var(--amber);
            font-size: clamp(20px, 2vw, 34px);
            font-weight: 900;
            letter-spacing: .08em;
            margin-bottom: 18px;
            text-transform: uppercase;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
            gap: 16px;
        }
        .match-card {
            border: 1px solid var(--line);
            border-radius: 18px;
            background: linear-gradient(150deg, rgba(17, 24, 39, 0.95), rgba(8, 14, 28, 0.95));
            min-height: 190px;
            padding: 18px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 10px;
            position: relative;
            overflow: hidden;
        }
        .match-card::before {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: 18px;
            background: linear-gradient(120deg, transparent, rgba(34, 211, 238, 0.05), transparent);
            transform: translateX(-100%);
            transition: transform .8s;
        }
        .match-card:hover::before {
            transform: translateX(100%);
        }
        .match-top {
            display: flex;
            justify-content: space-between;
            color: rgba(226, 232, 240, .76);
            font-weight: 700;
            font-size: .94rem;
        }
        .match-team {
            font-size: clamp(20px, 1.9vw, 34px);
            font-weight: 900;
            line-height: 1.12;
            color: #fff;
        }
        .score {
            color: var(--amber);
            font-size: clamp(40px, 5.5vw, 88px);
            font-weight: 900;
            letter-spacing: -.06rem;
            margin: 8px 0;
        }
        .empty {
            border: 2px dashed var(--line);
            border-radius: 16px;
            color: rgba(241, 245, 249, .72);
            padding: 34px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: clamp(20px, 2vw, 34px);
            font-weight: 700;
            text-align: center;
            min-height: 190px;
        }
        .call-card {
            border: 2px solid var(--amber);
            background: linear-gradient(155deg, rgba(23, 42, 69, 0.96), rgba(17, 24, 39, 0.95));
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 64vh;
            text-align: center;
            border-radius: 22px;
        }
        .call-title {
            font-size: clamp(38px, 6vw, 88px);
            letter-spacing: -.05em;
            font-weight: 900;
            text-transform: uppercase;
        }
        .call-sub {
            margin-top: 14px;
            font-size: clamp(26px, 2.7vw, 46px);
            color: var(--mint);
            font-weight: 700;
        }
        .sponsor {
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 8px 16px;
            max-width: fit-content;
            margin-bottom: 12px;
            background: rgba(248, 250, 252, .08);
            font-weight: 700;
        }
        .ticker {
            margin-top: 10px;
            color: rgba(226, 232, 240, .68);
            font-size: .9rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }
        @media (max-width: 1200px) {
            .tv-wrap {
                width: 100%;
            }
            .grid {
                grid-template-columns: 1fr;
            }
            .tv-title {
                font-size: clamp(24px, 6.5vw, 44px);
            }
        }
    </style>
</head>
<body>
<main class="tv-wrap">
    <div class="tv-header">
        <div class="tv-hero">
            <div class="badge-board" id="sequence-tag">
                <?= strtoupper(implode(' → ', $data['slides'] ?? ['live', 'next', 'call', 'results'])) ?>
            </div>
            <h1 class="tv-title"><?= esc($data['tournament']->name_vi ?? $data['config']->display_name ?? 'Live Score TV') ?></h1>
            <div class="tv-meta">
                <span id="status-text">Đang load dữ liệu...</span>
                <span class="mx-2">•</span>
                <span>Auto-refresh: <strong><?= (int) ($data['refresh_seconds'] ?? 10) ?> giây</strong></span>
            </div>
        </div>
        <div class="text-end">
            <div class="sponsor">QR Bracket</div>
            <img alt="QR" width="112" height="112" src="https://api.qrserver.com/v1/create-qr-code/?size=112x112&data=<?= urlencode(site_url('/live-scores/bracket')) ?>">
        </div>
    </div>

    <div class="tv-board">
        <section id="tv-live" class="tv-stage active">
            <div class="stage-label">LIVE NOW</div>
            <div id="live-items" class="grid">
                <?php if (! empty($data['live_matches'])): ?>
                    <?php foreach ($data['live_matches'] as $match): ?>
                        <article class="match-card">
                            <div class="match-top">
                                <span>M<?= (int) ($match->match_no ?? 0) ?> · <?= esc($match->court_name ?? 'Court') ?></span>
                                <span><?= esc(substr((string) ($match->start_time ?? ''), 0, 5)) ?></span>
                            </div>
                            <div class="match-team"><?= esc($match->team_a_label ?? '-') ?></div>
                            <div class="score"><?= esc($match->score_text ?? '-') ?></div>
                            <div class="match-team"><?= esc($match->team_b_label ?? '-') ?></div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty">Chưa có trận đang thi đấu</div>
                <?php endif; ?>
            </div>
        </section>

        <section id="tv-next" class="tv-stage">
            <div class="stage-label">NEXT MATCHES</div>
            <div id="next-items" class="grid">
                <?php if (! empty($data['next_matches'])): ?>
                    <?php foreach (array_slice($data['next_matches'], 0, 6) as $match): ?>
                        <article class="match-card">
                            <div class="match-top">
                                <span>M<?= (int) ($match->match_no ?? 0) ?> · <?= esc($match->court_name ?? 'Chưa phân sân') ?></span>
                                <span><?= esc(substr((string) ($match->start_time ?? ''), 0, 5)) ?></span>
                            </div>
                            <div class="match-team"><?= esc($match->team_a_label ?? '-') ?></div>
                            <div class="match-team">vs</div>
                            <div class="match-team"><?= esc($match->team_b_label ?? '-') ?></div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty">Chưa có trận kế tiếp</div>
                <?php endif; ?>
            </div>
        </section>

        <section id="tv-call" class="tv-stage">
            <div class="call-card">
                <div class="stage-label">CALL PLAYER</div>
                <?php $match = ($data['called_matches'] ?? [])[0] ?? null; ?>
                <?php if ($match): ?>
                    <div class="call-title">Sân <?= esc($match->court_name ?? '—') ?></div>
                    <div class="call-sub">M<?= (int) ($match->match_no ?? 0) ?> · <?= esc($match->team_a_label ?? '-') ?> vs <?= esc($match->team_b_label ?? '-') ?></div>
                <?php else: ?>
                    <div class="call-title">Các vận động viên chuẩn bị</div>
                    <div class="call-sub">Hệ thống tự chuyển sang kỳ mới kế tiếp</div>
                <?php endif; ?>
                <p class="text-white-50 mt-3 mb-0">Hãy giữ luồng: đã gọi → lên sân → cập nhật điểm</p>
            </div>
        </section>

        <section id="tv-results" class="tv-stage">
            <div class="stage-label">KẾT QUẢ MỚI NHẤT</div>
            <div id="results-items" class="grid">
                <?php if (! empty($data['result_matches'])): ?>
                    <?php foreach ($data['result_matches'] as $match): ?>
                        <article class="match-card">
                            <div class="match-top">
                                <span>M<?= (int) ($match->match_no ?? 0) ?> · <?= esc($match->category_name ?? '-') ?></span>
                                <span><?= esc(substr((string) ($match->start_time ?? ''), 0, 5)) ?></span>
                            </div>
                            <div class="match-team"><?= esc($match->team_a_label ?? '-') ?></div>
                            <div class="score"><?= esc($match->score_text ?? '-') ?></div>
                            <div class="match-team"><?= esc($match->team_b_label ?? '-') ?></div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty">Chưa có kết quả mới</div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <div class="ticker">
        <span id="last-updated">Cập nhật lúc: <strong>—</strong></span>
        <span>URL: <strong id="source-url"><?= esc($data['api_endpoint'] ?? base_url('live-scores/tv')) ?></strong></span>
    </div>
</main>

<script>
const initialData = <?= json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK) ?>;
const sequence = <?= json_encode($data['slides'] ?? ['live', 'next', 'call', 'results'], JSON_UNESCAPED_UNICODE) ?>;
const stages = {
    live: document.getElementById('tv-live'),
    next: document.getElementById('tv-next'),
    call: document.getElementById('tv-call'),
    results: document.getElementById('tv-results')
};
const dataUrl = <?= json_encode($data['api_endpoint'] ?? '') ?>;
let state = 0;
let current = normalizeData(initialData);
let refreshSeconds = Math.max(5, Number(<?= max(5, (int) ($data['refresh_seconds'] ?? 10)) ?>) || 10);
let slideHold = Math.max(6, Math.round(refreshSeconds * 1.15));
let slideTimer = null;
let refreshTimer = null;

function normalizeData(payload) {
    const safe = payload && typeof payload === 'object' ? payload : {};
    return {
        tournament: safe.tournament || null,
        live_matches: Array.isArray(safe.live_matches) ? safe.live_matches : [],
        called_matches: Array.isArray(safe.called_matches) ? safe.called_matches : [],
        next_matches: Array.isArray(safe.next_matches) ? safe.next_matches : [],
        result_matches: Array.isArray(safe.result_matches) ? safe.result_matches : [],
        refresh_seconds: Number(safe.refresh_seconds) || refreshSeconds,
        slides: Array.isArray(safe.slides) && safe.slides.length ? safe.slides : ['live', 'next', 'call', 'results'],
        sequence: safe.sequence || (Array.isArray(safe.slides) ? safe.slides.join(',') : 'live,next,call,results')
    };
}

function fmtTime(v) {
    const d = new Date(v);
    if (isNaN(d.getTime())) {
        const now = new Date();
        return now.toLocaleTimeString('vi-VN', {hour: '2-digit', minute: '2-digit', second: '2-digit'});
    }
    return d.toLocaleTimeString('vi-VN', {hour: '2-digit', minute: '2-digit', second: '2-digit'});
}

function showCard(containerId, rows, fallback) {
    const root = document.getElementById(containerId);
    if (!root) {
        return;
    }
    if (!rows.length) {
        root.innerHTML = `<div class=\"empty\">${fallback}</div>`;
        return;
    }
    root.innerHTML = rows.map((match) => {
        return `<article class=\"match-card\">\n            <div class=\"match-top\">\n                <span>M${Number(match.match_no || 0)} · ${match.court_name || 'Chưa phân sân'}</span>\n                <span>${(match.start_time || '').slice(0, 5)}</span>\n            </div>\n            <div class=\"match-team\">${match.team_a_label || '-'}</div>\n            ${containerId === 'next-items' ? '<div class=\"match-team\">vs</div>' : `<div class=\"score\">${match.score_text || '-'}</div>`}\n            <div class=\"match-team\">${match.team_b_label || '-'}</div>\n        </article>`;
    }).join('');
}

function render(payload) {
    current = normalizeData(payload.data || payload);
    document.getElementById('status-text').textContent = `Hiển thị live: ${current.live_matches.length} trận | kế tiếp: ${current.next_matches.length} | đợi gọi: ${current.called_matches.length} | kết quả: ${current.result_matches.length}`;
    document.getElementById('sequence-tag').textContent = current.slides.map((s) => String(s).toUpperCase()).join(' → ');
    document.getElementById('last-updated').innerHTML = `Cập nhật lúc: <strong>${fmtTime(new Date())}</strong>`;
    showCard('live-items', current.live_matches, 'Chưa có trận đang thi đấu');
    showCard('next-items', current.next_matches.slice(0, 6), 'Chưa có trận kế tiếp');
    showCard('results-items', current.result_matches.slice(0, 6), 'Chưa có kết quả mới');

    const call = current.called_matches[0] || null;
    const callNode = document.querySelector('#tv-call .call-card');
    if (callNode) {
        if (call) {
            callNode.innerHTML = `<div class=\"stage-label\">CALL PLAYER</div>\n                <div class=\"call-title\">Sân ${call.court_name || '—'}</div>\n                <div class=\"call-sub\">M${Number(call.match_no || 0)} · ${call.team_a_label || '-'} vs ${call.team_b_label || '-'}</div>\n                <p class=\"text-white-50 mt-3 mb-0\">Đã gọi: ${fmtTime(new Date())} · Sẵn sàng lên sân</p>`;
        } else {
            callNode.innerHTML = '<div class=\"stage-label\">CALL PLAYER</div><div class=\"call-title\">Các vận động viên chuẩn bị</div><div class=\"call-sub\">Hệ thống tự chuyển sang kỳ mới kế tiếp</div><p class=\"text-white-50 mt-3 mb-0\">Đang chờ trạng thái cuộc đấu kế tiếp</p>';
        }
    }
}

function switchSlide() {
    const slides = current.slides.length ? current.slides : ['live', 'next', 'call', 'results'];
    Object.keys(stages).forEach((k) => stages[k].classList.remove('active'));
    const key = String(slides[state % slides.length] || 'live');
    if (stages[key]) {
        stages[key].classList.add('active');
    } else if (stages.live) {
        stages.live.classList.add('active');
    }
    state += 1;
}

function pollData() {
    if (!dataUrl) {
        render(initialData);
        return;
    }
    fetch(dataUrl + (dataUrl.includes('?') ? '&' : '?') + 'r=' + Date.now(), {cache: 'no-store'})
        .then((res) => res.ok ? res.json() : null)
        .then((json) => {
            if (!json || !json.success || !json.data) return;
            render(json.data);
            refreshSeconds = Math.max(5, Number(json.data.refresh_seconds) || refreshSeconds);
            slideHold = Math.max(6, Math.round(refreshSeconds * 1.15));
        })
        .catch(() => {
            render(current);
        });
}

function startLoop() {
    switchSlide();
    slideTimer = setInterval(switchSlide, slideHold * 1000);
    refreshTimer = setInterval(pollData, refreshSeconds * 1000);
}

render(initialData);
startLoop();
</script>
</body>
</html>
