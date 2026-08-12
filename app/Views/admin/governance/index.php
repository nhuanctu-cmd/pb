<?= $this->extend('layouts/admin_master') ?>

<?= $this->section('styles') ?>
<style>
.governance-page { --gov-ink:#102a43; --gov-line:#e5edf5; }
.governance-hero { display:flex; justify-content:space-between; align-items:center; gap:20px; padding:22px 24px; border-radius:16px; color:#fff; background:linear-gradient(115deg,#102a43,#2563eb); box-shadow:0 12px 28px rgba(16,42,67,.15); }
.governance-hero h1 { letter-spacing:-.03em; }
.governance-hero p { color:rgba(255,255,255,.75)!important; }
.governance-card { border:1px solid var(--gov-line); border-radius:15px; background:#fff; box-shadow:0 6px 18px rgba(15,23,42,.04); overflow:hidden; }
.governance-card-header { display:flex; justify-content:space-between; align-items:center; gap:12px; padding:16px 18px; border-bottom:1px solid var(--gov-line); }
.governance-card-header h2 { color:var(--gov-ink); font-size:1rem; margin:0; }
.governance-card .table { margin:0; }
.governance-card .table thead th { background:#f8fbff; color:#64748b; font-size:.72rem; letter-spacing:.06em; text-transform:uppercase; white-space:nowrap; }
.governance-card .table tbody tr:hover { background:#f8fbff; }
.governance-card code { display:block; max-width:320px; overflow:auto; padding:6px 8px; border-radius:7px; background:#f1f5f9; color:#334155; font-size:.78rem; }
.governance-action { min-width:340px; }
.governance-empty { padding:34px 16px!important; }
@media (max-width:768px) { .governance-hero { align-items:flex-start; flex-direction:column; } .governance-action { min-width:280px; flex-wrap:wrap; } }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="governance-page">
<div class="governance-hero mb-4">
    <div><h1 class="h4 mb-1">Match Governance</h1><p class="text-muted mb-0">Dispute, correction và audit boundary cho kết quả official.</p></div>
    <span class="badge rounded-pill text-bg-light text-primary px-3 py-2"><i class="bi bi-building me-1"></i> Tenant #<?= (int)$tenantId ?></span>
</div>
<div class="governance-card mb-4"><div class="governance-card-header"><h2><i class="bi bi-flag me-2 text-warning"></i>Dispute đang mở</h2><span class="badge text-bg-warning"><?= count($disputes) ?></span></div><div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>Match</th><th>Lý do</th><th>Trạng thái</th><th>Thao tác</th></tr></thead><tbody>
<?php foreach ($disputes as $item): ?><tr><td><strong><?= esc($item->public_id ?: '#' . $item->match_id) ?></strong><div class="small text-muted">Match status: <?= esc($item->match_status ?? '-') ?></div></td><td><?= esc($item->reason) ?></td><td><span class="badge text-bg-warning"><?= esc($item->status) ?></span></td><td><form method="post" action="<?= base_url('admin/governance/disputes/' . (int)$item->id . '/resolve') ?>" class="governance-action d-flex gap-2"><?= csrf_field() ?><select name="status" class="form-select form-select-sm"><option value="rejected">Bác dispute</option><option value="upheld">Giữ dispute</option><option value="resolved">Đã xử lý</option></select><input name="resolution" class="form-control form-control-sm" placeholder="Kết luận" required><button class="btn btn-sm btn-primary"><i class="bi bi-check2"></i> Lưu</button></form></td></tr><?php endforeach; ?><?php if (!$disputes): ?><tr><td colspan="4" class="governance-empty text-center text-muted"><i class="bi bi-check2-circle d-block fs-3 text-success mb-2"></i>Không có dispute mở.</td></tr><?php endif; ?></tbody></table></div></div>
<div class="governance-card"><div class="governance-card-header"><h2><i class="bi bi-pencil-square me-2 text-primary"></i>Correction đang chờ duyệt</h2><span class="badge text-bg-primary"><?= count($corrections) ?></span></div><div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>Match</th><th>Lý do</th><th>Requested result</th><th>Thao tác</th></tr></thead><tbody>
<?php foreach ($corrections as $item): $requested = is_string($item->requested_result) ? json_decode($item->requested_result, true) : (array)$item->requested_result; ?><tr><td><strong><?= esc($item->public_id ?: '#' . $item->match_id) ?></strong><div class="small text-muted">Match status: <?= esc($item->match_status ?? '-') ?></div></td><td><?= esc($item->reason) ?></td><td><code><?= esc(json_encode($requested, JSON_UNESCAPED_UNICODE)) ?></code></td><td><form method="post" action="<?= base_url('admin/governance/corrections/' . (int)$item->id . '/approve') ?>" class="d-inline"><?= csrf_field() ?><input type="hidden" name="reason" value="Approved by governance reviewer"><button class="btn btn-sm btn-success"><i class="bi bi-check2"></i> Duyệt</button></form> <form method="post" action="<?= base_url('admin/governance/corrections/' . (int)$item->id . '/reject') ?>" class="d-inline"><?= csrf_field() ?><input type="hidden" name="reason" value="Rejected by governance reviewer"><button class="btn btn-sm btn-outline-danger"><i class="bi bi-x"></i> Từ chối</button></form></td></tr><?php endforeach; ?><?php if (!$corrections): ?><tr><td colspan="4" class="governance-empty text-center text-muted"><i class="bi bi-check2-circle d-block fs-3 text-success mb-2"></i>Không có correction mở.</td></tr><?php endif; ?></tbody></table></div></div>
</div>
<?= $this->endSection() ?>
