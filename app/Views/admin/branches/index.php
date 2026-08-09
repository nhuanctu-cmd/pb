<?= $this->extend('layouts/admin_master') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <?= flash_message() ?>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th><?= lang('App.branch_code') ?></th>
                        <th><?= lang('App.branch_name') ?></th>
                        <th><?= lang('App.phone') ?></th>
                        <th><?= lang('App.branch_courts') ?></th>
                        <th><?= lang('App.status') ?></th>
                        <th class="text-end"><?= lang('App.actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($branches)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4"><?= lang('App.no_data') ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($branches as $b): ?>
                            <tr>
                                <td><code><?= esc($b->code) ?></code> <?= $b->is_main ? '<span class="badge bg-primary">' . lang('App.branch_main') . '</span>' : '' ?></td>
                                <td><?= esc($b->name) ?></td>
                                <td><?= esc($b->phone ?? '-') ?></td>
                                <td><span class="badge bg-secondary"><?= (int) ($courtCounts[$b->id] ?? 0) ?></span></td>
                                <td><span class="badge bg-<?= $b->status === 'active' ? 'success' : 'secondary' ?>"><?= lang('App.status_' . $b->status) ?></span></td>
                                <td class="text-end">
                                    <a href="<?= base_url('admin/branches/hours/' . $b->id) ?>" class="btn btn-sm btn-outline-secondary" title="<?= lang('App.branch_hours') ?>"><i class="bi bi-clock"></i></a>
                                    <a href="<?= base_url('admin/branches/holidays/' . $b->id) ?>" class="btn btn-sm btn-outline-secondary" title="<?= lang('App.branch_holidays') ?>"><i class="bi bi-calendar-x"></i></a>
                                    <a href="<?= base_url('admin/branches/edit/' . $b->id) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                    <form method="post" action="<?= base_url('admin/branches/delete/' . $b->id) ?>" class="d-inline" onsubmit="return confirm('<?= lang('App.confirm_delete') ?>')">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
