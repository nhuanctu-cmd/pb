<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Action</th><th>Module</th><th>User</th><th>Description</th><th>Time</th></tr></thead>
            <tbody>
                <?php foreach (($logs ?? []) as $log): ?>
                <tr>
                    <td><span class="badge bg-secondary"><?= esc($log->action) ?></span></td>
                    <td><?= esc($log->module) ?></td>
                    <td><?= esc($log->user_id ?? '-') ?></td>
                    <td><?= esc($log->description ?? '') ?></td>
                    <td><?= format_datetime($log->created_at ?? null) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($logs)): ?>
                <tr><td colspan="5" class="text-center text-muted py-4"><?= lang('App.no_data') ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
