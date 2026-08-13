<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= esc($pageTitle) ?></title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"><style>
body{background:#e5e7eb;color:#111827}
.print-page{max-width:1100px;margin:24px auto;background:#fff;padding:28px;min-height:90vh}
.print-toolbar{position:sticky;top:0;z-index:2;background:#fff;border-bottom:1px solid #e5e7eb;padding-bottom:16px;margin-bottom:20px}
.brand{border-bottom:3px solid #111827;padding-bottom:12px;margin-bottom:20px}
.print-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
.doc-card,.tag-card,.staff-card,.certificate{border:1px solid #cbd5e1;border-radius:10px;padding:16px;min-height:145px;page-break-inside:avoid}
.doc-card h2,.tag-card h2{font-size:1.25rem}
.tag-card{text-align:center;display:flex;flex-direction:column;justify-content:center}
.match-card{border:1px solid #cbd5e1;padding:14px;margin-bottom:12px;page-break-inside:avoid}
.court-sign{border:3px solid #111827;padding:30px;text-align:center;margin-bottom:12px;page-break-inside:avoid}
.court-sign h2{font-size:3rem}
.certificate{text-align:center;min-height:260px;padding:48px}
.certificate h1{font-family:Georgia,serif}
.print-table{width:100%;border-collapse:collapse}
.print-table th,.print-table td{border:1px solid #cbd5e1;padding:7px;font-size:.9rem}
.section-title{font-size:.9rem;letter-spacing:.08em;text-transform:uppercase;color:#64748b;font-weight:700;margin-bottom:8px}
.pack-anchor{display:block;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:8px 12px;margin-bottom:20px;text-decoration:none;color:#0f172a}
.pack-anchor:hover{background:#f1f5f9}
@media(max-width:700px){.print-grid{grid-template-columns:1fr 1fr}}
@media print{body{background:#fff}.print-page{margin:0;max-width:none;padding:0;min-height:0}.print-toolbar{display:none}.no-print{display:none!important}.print-grid{grid-template-columns:repeat(3,1fr)}@page{margin:10mm}}
</style></head>
<body>
<main class="print-page">
<?php
$documents = is_array($printPack ?? null) ? $printPack : [isset($type) ? [
    'type' => $type,
    'tournament' => $tournament,
    'documentMeta' => $documentMeta ?? ['label' => ucfirst((string)$type), 'icon' => 'bi-printer'],
    'documentOptions' => $documentOptions ?? [],
    'categories' => $categories ?? [],
    'sponsors' => $sponsors ?? [],
    'registrations' => $registrations ?? [],
    'matches' => $matches ?? [],
    'results' => $results ?? [],
    'courts' => $courts ?? [],
    'printScopeTitle' => $printScopeTitle ?? 'Toàn bộ dữ liệu',
] : []];
$singleMatchStatus = ['scheduled' => 'Sắp diễn ra', 'pending' => 'Chờ lệnh', 'called' => 'Đã gọi', 'on_court' => 'Đang trận', 'running' => 'Đang thi đấu', 'in_progress' => 'Đang thi đấu', 'completed' => 'Hoàn tất', 'walkover' => 'Walkover', 'cancelled' => 'Huỷ'];
?>

<?php foreach ($documents as $index => $document): ?>
<?php
$type = $document['type'];
$tournament = $document['tournament'];
$matches = $document['matches'] ?? [];
$registrations = $document['registrations'] ?? [];
$results = $document['results'] ?? [];
$documentMeta = $document['documentMeta'] ?? ['label' => ucfirst((string)$type), 'icon' => 'bi-printer'];
if ($index > 0) {
    echo '<div style="page-break-before:always" class="page-break"></div>';
}
?>
<?php if (count($documents) > 1): ?>
    <a class="pack-anchor" href="#document-<?= $type ?>">#<?= $index + 1 ?> · <?= esc($documentMeta['label']) ?> · <?= esc($type) ?></a>
<?php endif; ?>

<div id="document-<?= $type ?>" class="print-toolbar d-flex justify-content-between align-items-center no-print">
    <div>
        <strong><?= esc($tournament->name_vi) ?></strong>
        <div class="text-muted small">Print Center · <?= esc($type) ?> <?= esc($documentMeta['label'] ?? '') ?></div>
        <div class="text-muted small">Phạm vi: <?= esc($document['printScopeTitle'] ?? 'Toàn bộ dữ liệu') ?></div>
    </div>
    <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> In / Lưu PDF</button>
</div>

<div class="brand">
    <h1 class="h3 mb-1"><?= esc($tournament->name_vi) ?></h1>
    <div class="text-muted"><?= esc($tournament->start_date ?: '') ?><?= $tournament->end_date && $tournament->end_date !== $tournament->start_date ? ' → ' . esc($tournament->end_date) : '' ?></div>
</div>
<div class="section-title mb-3"><?= esc($documentMeta['label'] ?? 'Document') ?> · <?= esc($printScopeTitle ?? 'Toàn bộ dữ liệu') ?></div>

<?php if ($type === 'badges' || $type === 'name_tags' || $type === 'team_badges'): ?>
    <div class="print-grid">
        <?php foreach ($registrations as $registration): ?>
            <?php $name = $registration->player_name ?: $registration->contact_name ?: 'VĐV'; ?>
            <?php if ($type === 'badges'): ?>
                <article class="doc-card">
                    <div class="text-muted small">PLAYER BADGE</div>
                    <h2><?= esc($name) ?></h2>
                    <div><?= esc($registration->partner_name ? $name . ' / ' . $registration->partner_name : ($registration->team_name ?: '')) ?></div>
                    <div class="small text-muted"><?= esc($registration->category_name ?? '-') ?></div>
                    <div class="mt-2 small">Registration #<?= (int) $registration->id ?></div>
                    <img alt="QR" width="44" height="44" class="mt-2" src="https://api.qrserver.com/v1/create-qr-code/?size=44x44&data=<?= urlencode(site_url('/tournaments/' . $tournament->slug_vi . '/register')) ?>">
                </article>
            <?php elseif ($type === 'name_tags'): ?>
                <article class="tag-card">
                    <div class="text-muted small"><?= esc($registration->category_name ?? 'PLAYER') ?></div>
                    <h2><?= esc($name) ?></h2>
                    <strong><?= esc($registration->team_name ?? '') ?></strong>
                </article>
            <?php else: ?>
                <article class="doc-card">
                    <div class="text-muted small">TEAM BADGE</div>
                    <h2><?= esc($registration->team_name ?: ('TEAM #' . (int) $registration->id)) ?></h2>
                    <div><?= esc($name) ?><?= $registration->partner_name ? ' / ' . esc($registration->partner_name) : '' ?></div>
                    <div class="small text-muted"><?= esc($registration->category_name ?? '-') ?></div>
                    <div class="mt-2 small"><?= esc($registration->contact_phone ?? '-') ?></div>
                </article>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

<?php elseif ($type === 'staff_badges'): ?>
    <div class="print-grid">
        <?php foreach (['ORGANIZER', 'REFEREE', 'STAFF', 'MEDIA', 'VIP'] as $role): ?>
            <article class="staff-card">
                <div class="text-muted small">EVENT STAFF</div>
                <h2><?= esc($role) ?></h2>
                <div class="small text-muted"><?= esc($tournament->name_vi) ?></div>
            </article>
        <?php endforeach; ?>
    </div>

<?php elseif ($type === 'court_signs'): ?>
    <?php if (!empty($document['courts'])): ?>
        <?php foreach ($document['courts'] as $court): ?>
            <article class="court-sign">
                <div class="text-muted">TOURNAMENT COURT</div>
                <h2><?= esc($court['name']) ?></h2>
                <h3><?= esc($court['category_name'] ?: 'Live Score') ?></h3>
            </article>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="text-muted">Chưa có dữ liệu phân sân.</p>
    <?php endif; ?>

<?php elseif ($type === 'match_cards'): ?>
    <?php foreach ($matches as $match): ?>
        <article class="match-card">
            <div class="d-flex justify-content-between">
                <strong>M<?= (int) $match->match_no ?></strong>
                <span><?= esc($match->scheduled_date ?: '-') ?> · <?= esc(substr((string) $match->start_time, 0, 5)) ?> · <?= esc($match->court_name ?: 'Chưa phân sân') ?></span>
            </div>
            <h3 class="h5 mt-3 mb-3"><?= esc($match->team_a_name ?: ('Team #' . ($match->team_a_id ?? '-'))) ?> <span class="text-muted">vs</span> <?= esc($match->team_b_name ?: ('Team #' . ($match->team_b_id ?? '-'))) ?></h3>
            <div class="row g-2">
                <div class="col-6 border-bottom">Score A: __________</div>
                <div class="col-6 border-bottom">Score B: __________</div>
                <div class="col-6 mt-3">Referee: __________</div>
                <div class="col-6 mt-3">Signature: __________</div>
            </div>
        </article>
    <?php endforeach; ?>

<?php elseif ($type === 'schedule'): ?>
    <table class="print-table">
        <thead><tr><th>Trận</th><th>Hạng mục</th><th>Ngày</th><th>Giờ</th><th>Sân</th><th>Tình trạng</th><th>Ghi chú</th></tr></thead>
        <tbody>
        <?php foreach ($matches as $match): ?>
            <tr>
                <td>M<?= (int) $match->match_no ?></td>
                <td><?= esc($match->category_name ?? '-') ?></td>
                <td><?= esc($match->scheduled_date ?: '-') ?></td>
                <td><?= esc(substr((string) $match->start_time, 0, 5)) ?></td>
                <td><?= esc($match->court_name ?: '-') ?></td>
                <td><?= esc($singleMatchStatus[$match->status ?? ''] ?? ($match->status ?? '-')) ?></td>
                <td></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

<?php elseif ($type === 'bracket'): ?>
    <?php $rounds = []; foreach ($matches as $match) { $rounds[$match->category_name ?: 'Hạng mục'][$match->round_name ?: 'Vòng đấu'][] = $match; } ?>
    <?php foreach ($rounds as $category => $categoryRounds): ?>
        <section class="mb-4">
            <h2 class="h5 border-bottom pb-2"><?= esc($category) ?></h2>
            <div class="print-grid">
                <?php foreach ($categoryRounds as $round => $roundMatches): ?>
                    <div>
                        <h3 class="h6 text-muted"><?= esc($round) ?></h3>
                        <?php foreach ($roundMatches as $match): ?>
                            <article class="match-card">
                                <strong>M<?= (int)$match->match_no ?></strong>
                                <div><?= esc($match->team_a_name ?: 'TBD') ?></div>
                                <div><?= esc($match->team_b_name ?: 'TBD') ?></div>
                                <small><?= esc($match->status) ?></small>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>

<?php elseif ($type === 'participants' || $type === 'checkin'): ?>
    <table class="print-table">
        <thead>
            <tr><th>#</th><th>VĐV</th><th>Đồng đội</th><th>Hạng mục</th><th>Đội</th><?php if ($type === 'checkin'): ?><th>Check-in</th><th>Ký nhận</th><?php endif; ?></tr>
        </thead>
        <tbody>
            <?php foreach ($registrations as $index => $registration): ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= esc($registration->player_name ?: $registration->contact_name ?: '-') ?></td>
                    <td><?= esc($registration->partner_name ?: '-') ?></td>
                    <td><?= esc($registration->category_name ?: '-') ?></td>
                    <td><?= esc($registration->team_name ?: '-') ?></td>
                    <?php if ($type === 'checkin'): ?>
                        <td><?= esc($registration->checkin_status ?: 'pending') ?></td>
                        <td></td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

<?php elseif ($type === 'certificates'): ?>
    <?php foreach ($registrations as $registration): ?>
        <?php $name = $registration->player_name ?: $registration->contact_name ?: 'VĐV'; ?>
        <article class="certificate mb-3">
            <div class="text-muted">CERTIFICATE OF PARTICIPATION</div>
            <h1 class="display-5 my-4">Chứng nhận tham gia</h1>
            <p>Trao tặng cho</p>
            <h2><?= esc($name) ?></h2>
            <p class="text-muted"><?= esc($registration->category_name ?? '') ?> · <?= esc($tournament->name_vi) ?></p>
            <div style="margin-top:22px;color:#64748b">Sự kiện này ghi nhận sự tham gia đầy đủ của vận động viên và đội thi đấu.</div>
        </article>
    <?php endforeach; ?>

<?php else: ?>
    <table class="print-table">
        <thead><tr><th>Vị trí</th><th>Đội/VĐV</th><th>Hạng mục</th><th>Vòng</th><th>Trạng thái</th></tr></thead>
        <tbody>
        <?php $rows = $results['rows'] ?? []; $winners = $results['winners'] ?? []; ?>
        <?php foreach ($rows as $idx => $row): ?>
            <tr>
                <td>#<?= $idx + 1 ?></td>
                <td>
                    <?= esc($row->team_a_name ?? ('TBD')) ?> <small class="text-muted">vs</small> <?= esc($row->team_b_name ?? ('TBD')) ?>
                </td>
                <td><?= esc($row->category_name ?? '-') ?></td>
                <td><?= esc($row->round_name ?? '-') ?></td>
                <td><?= esc($row->status ?? '-') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <h2 class="h6 mt-4">Top winner gần nhất</h2>
    <?php if (!empty($winners)): ?>
        <ul>
            <?php foreach (array_slice($winners, 0, 6) as $winner): ?>
                <li><?= esc($winner) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p class="text-muted">Chưa có trận hoàn tất để tổng hợp kết quả.</p>
    <?php endif; ?>
<?php endif; ?>

<?php endforeach; ?>

</main>
</body>
</html>
