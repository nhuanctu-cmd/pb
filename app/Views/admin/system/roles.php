<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Name</th><th>Slug</th><th>Scope</th><th>Status</th></tr></thead>
            <tbody>
                <?php foreach (($roles ?? []) as $role): ?>
                <tr>
                    <td><?= esc($role->name) ?></td>
                    <td><code><?= esc($role->slug) ?></code></td>
                    <td><?= $role->tenant_id ? 'Tenant' : 'System' ?></td>
                    <td><span class="badge bg-<?= $role->status === 'active' ? 'success' : 'secondary' ?>"><?= esc($role->status) ?></span></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($roles)): ?>
                <tr><td colspan="4" class="text-center text-muted py-4"><?= lang('App.no_data') ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
