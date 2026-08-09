<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Name</th><th>Code</th><th>Phone</th><th>Address</th><th>Status</th></tr></thead>
            <tbody>
                <?php foreach (($branches ?? []) as $branch): ?>
                <tr>
                    <td><?= esc($branch->name) ?></td>
                    <td><code><?= esc($branch->code) ?></code></td>
                    <td><?= esc($branch->phone ?? '-') ?></td>
                    <td><?= esc($branch->address ?? '') ?></td>
                    <td><span class="badge bg-<?= $branch->status === 'active' ? 'success' : 'secondary' ?>"><?= esc($branch->status) ?></span></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($branches)): ?>
                <tr><td colspan="5" class="text-center text-muted py-4"><?= lang('App.no_data') ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
