<?= $this->extend('layouts/admin_master') ?>

<?= $this->section('styles') ?>
<style>
.bracket-page { --bracket-gap: 46px; }
.bracket-toolbar { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:18px; }
.bracket-toolbar .title { display:flex; align-items:center; gap:12px; }
.bracket-toolbar .title-icon { width:42px; height:42px; display:grid; place-items:center; border-radius:12px; color:#fff; background:linear-gradient(135deg,#4f46e5,#7c3aed); }
.bracket-category { overflow:hidden; border:1px solid #dfe4ef; border-radius:14px; background:#f8fafc; box-shadow:0 8px 22px rgba(30,41,59,.06); margin-bottom:22px; }
.bracket-category-head { display:flex; justify-content:space-between; align-items:center; gap:14px; flex-wrap:wrap; padding:18px 20px; background:#fff; border-bottom:1px solid #e5e7eb; }
.bracket-category-head h2 { margin:0; font-size:18px; color:#172033; }
.bracket-category-head p { margin:4px 0 0; font-size:12px; color:#718096; }
.bracket-actions { display:flex; gap:8px; flex-wrap:wrap; }
.bracket-actions a, .bracket-actions button { border:1px solid #cbd5e1; border-radius:7px; padding:7px 11px; background:#fff; color:#4f46e5; font-size:12px; text-decoration:none; cursor:pointer; }
.bracket-actions .danger { color:#dc2626; border-color:#fca5a5; }
.bracket-actions .warning { color:#b45309; border-color:#fbbf24; }
.bracket-scroll { overflow-x:auto; padding:26px 26px 32px; }
.bracket-board { display:flex; align-items:stretch; gap:var(--bracket-gap); min-width:max-content; }
.bracket-round { width:328px; display:flex; flex-direction:column; }
.bracket-round-title { text-align:center; font-weight:700; color:#172033; margin:0 0 18px; }
.bracket-round-items { display:flex; flex:1; flex-direction:column; justify-content:space-around; gap:18px; }
.bracket-match { position:relative; width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:8px 9px; background:#fff; box-shadow:0 2px 7px rgba(15,23,42,.08); }
.bracket-match::after { display:none; }
.bracket-round:last-child .bracket-match::after { display:none; }
.bracket-match::before { display:none; }
.bracket-connectors { position:absolute; inset:0; z-index:0; width:100%; height:100%; overflow:visible; pointer-events:none; }
.bracket-connector-path { fill:none; stroke:#94a3b8; stroke-width:1.4; vector-effect:non-scaling-stroke; }
.bracket-match-meta { display:flex; justify-content:space-between; align-items:center; margin-bottom:7px; font-size:11px; font-weight:700; color:#334155; }
.bracket-state { border-radius:999px; padding:2px 7px; background:#eef2ff; color:#4338ca; font-weight:600; }
.bracket-state.done { background:#dcfce7; color:#15803d; }
.bracket-state.live { background:#fee2e2; color:#b91c1c; }
.bracket-team { display:flex; align-items:center; gap:8px; min-height:34px; margin-top:4px; padding:5px 6px; border:1px solid #dbe3ef; border-radius:6px; background:#f8fafc; font-size:12px; font-weight:600; color:#172033; }
.bracket-team.winner { border-color:#86efac; background:#ecfdf5; color:#047857; }
.bracket-seed { flex:0 0 25px; display:grid; place-items:center; height:24px; border-radius:5px; background:#2563eb; color:#fff; font-size:12px; }
.bracket-team:first-of-type .bracket-seed { background:#dc2626; }
.bracket-team-name { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.bracket-match-info { display:flex; justify-content:space-between; gap:8px; color:#64748b; font-size:10px; margin-top:6px; }
.bracket-score { margin-top:6px; padding:5px 7px; border-radius:5px; background:#ecfdf5; color:#047857; font-size:11px; font-weight:700; }
.bracket-empty { padding:44px 20px; text-align:center; color:#64748b; }
.bracket-roster { padding:18px 20px 4px; border-bottom:1px solid #e5e7eb; background:#fff; }
.bracket-roster-head { display:flex; justify-content:space-between; align-items:center; gap:10px; margin-bottom:12px; }
.bracket-roster-head h3 { margin:0; color:#172033; font-size:14px; }
.bracket-roster-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:9px; padding-bottom:14px; }
.bracket-athlete { display:flex; align-items:center; gap:9px; min-width:0; padding:9px 10px; border:1px solid #e3e8f0; border-radius:9px; background:#f8fafc; }
.bracket-athlete-seed { flex:0 0 26px; display:grid; place-items:center; width:26px; height:26px; border-radius:7px; background:#4f46e5; color:#fff; font-size:11px; font-weight:700; }
.bracket-athlete-info { min-width:0; }
.bracket-athlete-name { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:#172033; font-size:12px; font-weight:700; }
.bracket-athlete-meta { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:#64748b; font-size:10px; }
.bracket-athlete-status { margin-left:auto; flex:0 0 auto; }
@media (max-width: 768px) { .bracket-scroll { padding-left:14px; padding-right:14px; } .bracket-round { width:270px; } .bracket-category-head { padding:15px; } }
@media (max-width: 1100px) { .bracket-roster-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
@media (max-width: 520px) { .bracket-roster-grid { grid-template-columns:1fr; } }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="bracket-page">
    <div class="bracket-toolbar">
        <div class="title">
            <div class="title-icon"><i class="bi bi-diagram-3"></i></div>
            <div><h1 class="h4 mb-1">Cây đấu giải</h1><div class="text-muted small"><?= esc($tournament->name_vi ?? 'Chọn giải đấu để xem cây đấu') ?></div></div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <?php if ($tournament): ?>
                <a class="btn btn-outline-primary btn-sm" href="/admin/tournaments/scheduler?category_id=<?= (int) ($categories[0]->id ?? 0) ?>"><i class="bi bi-calendar3"></i> Xếp lịch</a>
                <a class="btn btn-outline-secondary btn-sm" href="/admin/print-center?tournament_id=<?= (int) $tournamentId ?>"><i class="bi bi-printer"></i> Print Center</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (! $tournament): ?>
        <div class="erp-card bracket-empty"><i class="bi bi-diagram-3 fs-2 d-block mb-2"></i>Truy cập từ chi tiết giải hoặc dùng <code>?tournament_id=...</code> để xem cây đấu.</div>
    <?php elseif (empty($categories)): ?>
        <div class="erp-card bracket-empty">Giải chưa có hạng mục thi đấu.</div>
    <?php else: ?>
        <?php foreach ($categories as $category): $rounds = $brackets[(int) $category->id] ?? []; ?>
        <section class="bracket-category">
            <header class="bracket-category-head">
                <div><h2><?= esc($category->name_vi) ?></h2><p>Trạng thái nhánh đấu: <?= empty($rounds) ? 'chưa tạo' : 'đã tạo' ?></p></div>
                <div class="bracket-actions">
                    <a href="/admin/tournaments/scheduler?category_id=<?= (int) $category->id ?>"><i class="bi bi-sliders2"></i> Chỉnh khung</a>
                    <form method="post" action="/admin/tournaments/bracket/rerun/<?= (int) $category->id ?>" onsubmit="return confirm('Chạy lại sẽ tạo lại các trận knockout chưa khóa. Tiếp tục?')">
                        <?= csrf_field() ?><button class="danger" type="submit"><i class="bi bi-arrow-counterclockwise"></i> Reset khung</button>
                    </form>
                    <form method="post" action="/admin/tournaments/bracket/rerun/<?= (int) $category->id ?>" onsubmit="return confirm('Chạy lại cây đấu?')">
                        <?= csrf_field() ?><button class="warning" type="submit"><i class="bi bi-shuffle"></i> Chạy lại cây</button>
                    </form>
                    <a href="/admin/print-center/print?document=bracket&tournament_id=<?= (int) $tournamentId ?>" target="_blank"><i class="bi bi-printer"></i> In nhánh này</a>
                    <a href="/admin/tournaments/bracket/export/<?= (int) $category->id ?>"><i class="bi bi-filetype-csv"></i> Xuất nhánh</a>
                    <a href="/admin/scores?category_id=<?= (int) $category->id ?>"><i class="bi bi-pencil-square"></i> Nhập kết quả</a>
                    <span class="badge rounded-pill text-bg-dark align-self-center"><?= array_sum(array_map('count', $rounds)) ?> trận</span>
                </div>
            </header>
            <?php $categoryAthletes = $athletes[(int) $category->id] ?? []; ?>
            <div class="bracket-roster">
                <div class="bracket-roster-head"><h3><i class="bi bi-people me-2 text-primary"></i>Danh sách vận động viên</h3><span class="small text-muted"><?= count($categoryAthletes) ?> hồ sơ hợp lệ</span></div>
                <?php if (empty($categoryAthletes)): ?>
                    <div class="small text-muted pb-3">Chưa có vận động viên được duyệt trong hạng mục này.</div>
                <?php else: ?>
                    <div class="bracket-roster-grid">
                        <?php foreach ($categoryAthletes as $index => $athlete): ?>
                            <?php $athleteName = trim((string) ($athlete->player_name ?? $athlete->contact_name ?? 'Vận động viên')); $partnerName = trim((string) ($athlete->partner_name ?? '')); ?>
                            <div class="bracket-athlete">
                                <span class="bracket-athlete-seed"><?= $index + 1 ?></span>
                                <div class="bracket-athlete-info"><div class="bracket-athlete-name"><?= esc($athleteName) ?><?= $partnerName !== '' ? ' / ' . esc($partnerName) : '' ?></div><div class="bracket-athlete-meta"><?= esc($athlete->player_code ?? 'Chưa có mã') ?><?= isset($athlete->rating_score) && $athlete->rating_score !== null ? ' · Rating ' . esc((string) $athlete->rating_score) : '' ?></div></div>
                                <span class="bracket-athlete-status"><?= renderStatusBadge($athlete->approval_status ?? 'pending') ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php if (empty($rounds)): ?>
                <div class="bracket-empty">Chưa có cây knockout. Hãy chạy <strong>Auto schedule</strong> hoặc tạo cây từ màn hình xếp lịch.</div>
            <?php else: ?>
                <div class="bracket-scroll"><div class="bracket-board">
                    <?php $roundIndex = 0; foreach ($rounds as $roundNo => $matches): $roundIndex++; $roundTitle = $matches[0]->round_name ?? ($roundIndex === count($rounds) ? 'Chung kết' : 'Vòng ' . $roundNo); ?>
                    <div class="bracket-round">
                        <div class="bracket-round-title"><?= esc($roundTitle) ?></div>
                        <div class="bracket-round-items">
                            <?php foreach ($matches as $match): $isWinner = !empty($match->winner_team_id); $isLive = in_array($match->status ?? '', ['running','in_progress','on_court'], true); ?>
                            <article class="bracket-match" data-bracket-match>
                                <div class="bracket-match-meta"><span>Trận #<?= (int) $match->match_no ?></span><span class="bracket-state <?= $isWinner ? 'done' : ($isLive ? 'live' : '') ?>"><?= esc($match->status_label) ?></span></div>
                                <div class="bracket-team <?= $match->winner_team_id && (int) $match->winner_team_id === (int) $match->team_a_id ? 'winner' : '' ?>"><span class="bracket-seed">A</span><span class="bracket-team-name"><?= esc($match->team_a_label) ?></span></div>
                                <div class="bracket-team <?= $match->winner_team_id && (int) $match->winner_team_id === (int) $match->team_b_id ? 'winner' : '' ?>"><span class="bracket-seed">B</span><span class="bracket-team-name"><?= esc($match->team_b_label) ?></span></div>
                                <?php if ($isWinner): ?><div class="bracket-score">Thắng: <?= esc($match->winner_team_id == $match->team_a_id ? $match->team_a_label : $match->team_b_label) ?></div><?php endif; ?>
                                <div class="bracket-match-info"><span><?= esc($match->scheduled_date ?? '-') ?> <?= $match->start_time ? esc(substr($match->start_time, 0, 5)) : '' ?></span><span><?= $match->court_id ? 'Sân ' . (int) $match->court_id : 'Chưa xếp sân' ?></span></div>
                                <div class="mt-2 d-flex gap-1"><a class="btn btn-sm btn-outline-secondary py-0 px-2" href="/admin/scores/<?= (int) $match->id ?>">Chi tiết</a><a class="btn btn-sm btn-outline-primary py-0 px-2" href="/admin/tournaments/scheduler?category_id=<?= (int) $category->id ?>">Lịch</a></div>
                            </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div></div>
            <?php endif; ?>
        </section>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<script>
(function () {
    function drawBracketConnectors() {
        document.querySelectorAll('.bracket-board').forEach(function (board) {
            var previous = board.querySelector('.bracket-connectors');
            if (previous) previous.remove();

            var boardRect = board.getBoundingClientRect();
            var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            svg.setAttribute('class', 'bracket-connectors');
            svg.setAttribute('viewBox', '0 0 ' + board.offsetWidth + ' ' + board.offsetHeight);
            svg.setAttribute('aria-hidden', 'true');

            var rounds = board.querySelectorAll('.bracket-round');
            for (var roundIndex = 0; roundIndex < rounds.length - 1; roundIndex++) {
                var sourceMatches = rounds[roundIndex].querySelectorAll('[data-bracket-match]');
                var targetMatches = rounds[roundIndex + 1].querySelectorAll('[data-bracket-match]');
                for (var matchIndex = 0; matchIndex < sourceMatches.length; matchIndex += 2) {
                    var first = sourceMatches[matchIndex];
                    var second = sourceMatches[matchIndex + 1];
                    var target = targetMatches[Math.floor(matchIndex / 2)];
                    if (!first || !target) continue;

                    var firstRect = first.getBoundingClientRect();
                    var targetRect = target.getBoundingClientRect();
                    var x1 = firstRect.right - boardRect.left;
                    var y1 = firstRect.top + (firstRect.height / 2) - boardRect.top;
                    var x2 = targetRect.left - boardRect.left;
                    var y2 = targetRect.top + (targetRect.height / 2) - boardRect.top;
                    var middleX = x1 + ((x2 - x1) / 2);
                    var path = document.createElementNS('http://www.w3.org/2000/svg', 'path');

                    if (second) {
                        var secondRect = second.getBoundingClientRect();
                        var y3 = secondRect.top + (secondRect.height / 2) - boardRect.top;
                        path.setAttribute('d', 'M ' + x1 + ' ' + y1 + ' H ' + middleX + ' M ' + (secondRect.right - boardRect.left) + ' ' + y3 + ' H ' + middleX + ' M ' + middleX + ' ' + y1 + ' V ' + y3 + ' M ' + middleX + ' ' + ((y1 + y3) / 2) + ' H ' + x2);
                    } else {
                        path.setAttribute('d', 'M ' + x1 + ' ' + y1 + ' H ' + x2);
                    }
                    path.setAttribute('class', 'bracket-connector-path');
                    svg.appendChild(path);
                }
            }
            board.prepend(svg);
        });
    }

    window.addEventListener('load', drawBracketConnectors);
    window.addEventListener('resize', drawBracketConnectors);
})();
</script>
<?= $this->endSection() ?>
