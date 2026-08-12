<?= $this->extend('layouts/admin_master') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h1 class="h4 mb-1">Data Quality</h1><p class="text-muted mb-0">Kiểm tra tenant isolation, booking, rating, provenance và queue.</p></div>
    <span class="badge <?= (int)($report['total_issues'] ?? 0) ? 'text-bg-warning' : 'text-bg-success' ?>"><?= (int)($report['total_issues'] ?? 0) ?> vấn đề</span>
</div>
<div class="row g-3">
    <?php foreach (($report['checks'] ?? []) as $check): ?>
        <div class="col-md-6 col-xl-4">
            <div class="card shadow-sm h-100 border-<?= $check['status'] === 'ok' ? 'success' : ($check['severity'] === 'critical' ? 'danger' : 'warning') ?>">
                <div class="card-body"><div class="d-flex justify-content-between gap-2"><h2 class="h6 mb-2"><?= esc($check['label']) ?></h2><span class="badge text-bg-<?= $check['status'] === 'ok' ? 'success' : 'warning' ?>"><?= (int)$check['count'] ?></span></div><small class="text-muted"><?= esc($check['code']) ?> · <?= esc($check['severity']) ?></small></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<p class="text-muted small mt-4">Generated: <?= esc($report['generated_at'] ?? date('Y-m-d H:i:s')) ?></p>
<?= $this->endSection() ?>
