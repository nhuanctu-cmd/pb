<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th><?= lang('Tenant.name') ?></th>
                        <th><?= lang('Tenant.email') ?></th>
                        <th><?= lang('Tenant.phone') ?></th>
                        <th><?= lang('Tenant.status') ?></th>
                        <th><?= lang('App.actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($tenants)): ?>
                        <?php foreach ($tenants as $tenant): ?>
                        <tr>
                            <td><strong><?= esc($tenant->code) ?></strong></td>
                            <td><?= esc($tenant->name) ?></td>
                            <td><?= esc($tenant->email) ?></td>
                            <td><?= esc($tenant->phone) ?></td>
                            <td>
                                <span class="badge bg-<?= $tenant->status === 'active' ? 'success' : ($tenant->status === 'inactive' ? 'secondary' : 'warning') ?>">
                                    <?= $tenant->status ?>
                                </span>
                            </td>
                            <td>
                                <a href="/admin/tenants/edit/<?= $tenant->id ?>" class="btn btn-sm btn-outline-primary" title="<?= lang('App.edit') ?>">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="/admin/tenants/set-session/<?= $tenant->id ?>" class="btn btn-sm btn-outline-success" title="Select">
                                    <i class="bi bi-box-arrow-in-right"></i>
                                </a>
                                <a href="/admin/tenants/delete/<?= $tenant->id ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('<?= lang('App.confirm_delete') ?>')" title="<?= lang('App.delete') ?>">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted"><?= lang('App.no_data') ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
