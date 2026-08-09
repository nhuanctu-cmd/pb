<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card"><div class="card-body">
            <div class="text-muted">Facility</div>
            <h4><?= esc($facility->getName()) ?></h4>
            <div><?= esc($facility->address ?? '') ?></div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card"><div class="card-body">
            <div class="text-muted">Branches</div>
            <h4><?= count($branches ?? []) ?></h4>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card"><div class="card-body">
            <div class="text-muted">Courts</div>
            <h4><?= esc($facility->total_courts ?? 0) ?></h4>
        </div></div>
    </div>
</div>

<div class="card">
    <div class="card-header fw-semibold">Branches</div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Name</th><th>Code</th><th>Status</th><th></th></tr></thead>
            <tbody>
                <?php foreach (($branches ?? []) as $branch): ?>
                <tr>
                    <td><?= esc($branch->name) ?></td>
                    <td><code><?= esc($branch->code) ?></code></td>
                    <td><?= esc($branch->status) ?></td>
                    <td class="text-end"><a href="/admin/courts?branch_id=<?= $branch->id ?>" class="btn btn-sm btn-outline-primary">Courts</a></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($branches)): ?>
                <tr><td colspan="4" class="text-center text-muted py-4"><?= lang('App.no_data') ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
