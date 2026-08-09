<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>User</th><th>Email</th><th>Tenant</th><th>Status</th><th>Last Login</th></tr></thead>
            <tbody>
                <?php foreach (($users ?? []) as $user): ?>
                <tr>
                    <td><?= esc(trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: $user->username) ?></td>
                    <td><?= esc($user->email) ?></td>
                    <td><?= esc($user->tenant_id ?? '-') ?></td>
                    <td><span class="badge bg-<?= $user->status === 'active' ? 'success' : 'secondary' ?>"><?= esc($user->status) ?></span></td>
                    <td><?= format_datetime($user->last_login ?? null) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($users)): ?>
                <tr><td colspan="5" class="text-center text-muted py-4"><?= lang('App.no_data') ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
