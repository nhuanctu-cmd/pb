<?= $this->extend('layouts/admin_master') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= esc(asset_url('assets/css/tournament-operations.css')) ?>">
<style>
.control-stat { border: 1px solid var(--erp-border, #e5e7eb); border-radius: 14px; padding: 16px; background: #fff; height: 100%; }
.control-stat strong { display:block; font-size: 1.7rem; line-height:1; margin-top:8px; }
.court-card { border: 1px solid #e5e7eb; border-top: 4px solid #64748b; border-radius: 12px; background:#fff; padding:15px; height:100%; }
.court-card.live { border-top-color:#16a34a; } .court-card.delayed { border-top-color:#dc2626; } .court-card.called { border-top-color:#f59e0b; }
.match-chip { background:#f8fafc; border-radius:9px; padding:10px; margin-top:8px; }
.match-chip small { color:#64748b; display:block; } .ops-table td { vertical-align:middle; }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<form method="get" class="erp-card ops-toolbar mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-lg-6">
            <label class="form-label">Giải đấu</label>
            <select name="tournament_id" class="form-select" onchange="this.form.submit()">
                <option value="">Chọn giải đấu</option>
                <?php foreach ($tournaments as $item): ?>
                    <option value="<?= (int) $item->id ?>" <?= (int) $tournamentId === (int) $item->id ? 'selected' : '' ?>><?= esc($item->name_vi) ?> · <?= esc($item->start_date ?: '-') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-lg-3"><label class="form-label">Ngày vận hành</label><input type="date" name="date" value="<?= esc($dashboard['date'] ?? date('Y-m-d')) ?>" class="form-control"></div>
        <div class="col-lg-3"><button class="erp-btn erp-btn-primary w-100" type="submit"><i class="bi bi-arrow-repeat"></i> Tải bảng điều phối</button></div>
    </div>
</form>

<?php if (! $dashboard): ?>
    <div class="erp-card erp-empty"><div class="erp-empty-icon"><i class="bi bi-broadcast"></i></div><h3>Chọn một giải đấu</h3><p>Control Room sẽ gom sân, trận, check-in và cảnh báo trễ vào cùng một màn hình.</p></div>
<?php else: ?>
    <div id="control-room" class="ops-surface" data-tournament-id="<?= (int) $tournamentId ?>" data-date="<?= esc($dashboard['date']) ?>">
        <div class="ops-hero mb-4"><div><div class="small text-uppercase fw-bold opacity-75 mb-1">Tournament Control Room</div><h2 class="h4 mb-1"><?= esc($dashboard['tournament']->name_vi) ?></h2><div class="ops-meta"><i class="bi bi-calendar3 me-1"></i> <?= esc($dashboard['date']) ?> <span class="mx-2">·</span> <i class="bi bi-arrow-repeat me-1"></i> Cập nhật tự động mỗi 15 giây</div></div><a class="erp-btn" href="<?= base_url('live-scores/tv?tournament_id=' . $tournamentId) ?>" target="_blank"><i class="bi bi-display me-1"></i> Mở TV / LED</a></div>
        <div class="row row-cols-2 row-cols-md-3 row-cols-xl-5 g-3 mb-4">
            <?php $statLabels = ['total_matches' => 'Tổng trận', 'completed' => 'Đã xong', 'live' => 'Đang đánh', 'delayed' => 'Đang trễ', 'waiting' => 'Đang chờ', 'available_courts' => 'Sân trống', 'checked_in' => 'Đã check-in', 'no_shows' => 'No-show']; foreach ($statLabels as $key => $label): ?><div class="col"><div class="control-stat"><span class="text-muted small"><?= $label ?></span><strong><?= (int) ($dashboard['stats'][$key] ?? 0) ?></strong></div></div><?php endforeach; ?>
        </div>

        <div class="ops-section-heading"><h3 class="h5">Bảng sân</h3><span>LIVE → NEXT → ON DECK</span></div>
        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-4">
            <?php foreach ($dashboard['courts'] as $row): $status = $row['status']; ?>
                <div class="col"><div class="court-card <?= esc($status) ?>"><div class="d-flex justify-content-between"><strong><?= esc($row['court']->name_vi ?? $row['court']->code ?? '-') ?></strong><?= renderStatusBadge($status, 'general') ?></div><?php foreach (['current' => 'LIVE', 'next' => 'NEXT', 'on_deck' => 'ON DECK'] as $key => $label): $match = $row[$key]; ?><?php if ($match): ?><div class="match-chip"><small><?= $label ?> · M<?= (int) $match->match_no ?> · <?= esc(substr((string) ($match->start_time ?? ''), 0, 5)) ?></small><strong><?= esc($match->team_a_label) ?></strong><span class="text-muted"> vs </span><strong><?= esc($match->team_b_label) ?></strong><?php if ($key === 'next' && in_array($match->effective_status, ['scheduled', 'pending', 'delayed'], true)): ?><form method="post" action="<?= base_url('admin/tournaments/control-room/call/' . $match->id) ?>" class="mt-2"><?= csrf_field() ?><button class="btn btn-sm btn-outline-warning w-100"><i class="bi bi-megaphone"></i> Gọi VĐV</button></form><?php endif; ?></div><?php endif; ?><?php endforeach; ?><?php if (! $row['current'] && ! $row['next']): ?><div class="text-muted small mt-3">Sân đang trống</div><?php endif; ?></div></div>
            <?php endforeach; ?>
        </div>

        <div class="row g-3"><div class="col-xl-8"><div class="ops-card ops-alert-table"><div class="ops-card-header"><h3><i class="bi bi-exclamation-triangle me-2 text-warning"></i>Trận cần chú ý</h3><span class="small text-muted">Xử lý ngay tại đây</span></div><div class="ops-card-body table-responsive"><table class="erp-table ops-table"><thead><tr><th>Trận</th><th>Hạng mục</th><th>Sân / giờ</th><th>Trạng thái</th><th></th></tr></thead><tbody><?php foreach ($dashboard['matches'] as $match): ?><?php if (in_array($match->effective_status, ['delayed', 'called', 'on_court'], true)): ?><tr><td><strong>M<?= (int) $match->match_no ?></strong><div class="small text-muted"><?= esc($match->team_a_label) ?> vs <?= esc($match->team_b_label) ?></div></td><td><?= esc($match->category_name ?? '-') ?></td><td><?= esc($match->court_name ?: 'Chưa phân sân') ?><div class="small text-muted"><?= esc(substr((string) $match->start_time, 0, 5)) ?></div></td><td><?= renderStatusBadge($match->effective_status, 'general') ?></td><td><form method="post" action="<?= base_url('admin/tournaments/control-room/status/' . $match->id) ?>" class="d-flex gap-1"><?= csrf_field() ?><select name="status" class="form-select form-select-sm"><option value="running">LIVE</option><option value="completed">Hoàn tất</option><option value="delayed">Trễ</option></select><button class="btn btn-sm btn-primary">Lưu</button></form></td></tr><?php endif; ?><?php endforeach; ?><?php if (empty(array_filter($dashboard['matches'], static fn ($m) => in_array($m->effective_status, ['delayed', 'called', 'on_court'], true)))): ?><tr><td colspan="5" class="text-muted">Không có cảnh báo vận hành.</td></tr><?php endif; ?></tbody></table></div></div></div>
            <div class="col-xl-4"><div class="ops-card"><div class="ops-card-header"><h3><i class="bi bi-person-exclamation me-2 text-warning"></i>Chưa check-in</h3><span class="badge text-bg-warning"><?= count($dashboard['not_checked_in']) ?></span></div><div class="ops-card-body ops-checkin-list"><?php foreach ($dashboard['not_checked_in'] as $registration): ?><div class="ops-checkin-item"><strong><?= esc($registration->player_name ?: $registration->contact_name ?: 'VĐV chưa định danh') ?></strong><div class="small text-muted"><?= esc($registration->category_name ?? '-') ?> · <?= esc($registration->contact_phone ?? '') ?></div></div><?php endforeach; ?><?php if (empty($dashboard['not_checked_in'])): ?><div class="ops-empty"><i class="bi bi-check2-circle d-block fs-3 text-success mb-2"></i>Đã check-in đủ.</div><?php endif; ?></div></div></div></div>
    </div>
<?php endif; ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?php if ($dashboard): ?><script>
setInterval(() => { const root = document.getElementById('control-room'); if (!root) return; const url = '<?= base_url('admin/tournaments/control-room/data') ?>?tournament_id=' + root.dataset.tournamentId + '&date=' + root.dataset.date; fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest'}}).then(() => window.location.reload()); }, 15000);
</script><?php endif; ?>
<?= $this->endSection() ?>
