<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3"><?= $pageTitle ?? lang('App.memberships') ?></h1>
    <div>
        <a href="/admin/memberships/packages" class="btn btn-info"><i class="bi bi-box"></i> <?= lang('App.membership_packages') ?></a>
        <a href="/admin/memberships/create" class="btn btn-primary"><i class="bi bi-plus"></i> <?= lang('App.create') ?></a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="get" class="row g-2 mb-3">
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value=""><?= lang('App.all_status') ?></option>
                    <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>><?= lang('App.membership_active') ?></option>
                    <option value="expired" <?= ($filters['status'] ?? '') === 'expired' ? 'selected' : '' ?>><?= lang('App.membership_expired') ?></option>
                    <option value="cancelled" <?= ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>><?= lang('App.membership_cancelled') ?></option>
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-secondary w-100"><i class="bi bi-search"></i></button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th><?= lang('App.player') ?></th>
                        <th><?= lang('App.package') ?></th>
                        <th><?= lang('App.start_date') ?></th>
                        <th><?= lang('App.end_date') ?></th>
                        <th><?= lang('App.remaining_days') ?></th>
                        <th><?= lang('App.status') ?></th>
                        <th><?= lang('App.actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($memberships)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted"><?= lang('App.no_data') ?></td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($memberships as $membership): ?>
                    <tr>
                        <td><?= $membership->id ?></td>
                        <td>
                            <strong><?= esc($membership->full_name) ?></strong>
                            <br><small class="text-muted"><code><?= $membership->player_code ?></code></small>
                        </td>
                        <td><?= esc($membership->package_name_vi ?? $membership->package_name_en) ?></td>
                        <td><?= $membership->start_date ?></td>
                        <td><?= $membership->end_date ?></td>
                        <td>
                            <?php if ($membership->status === 'active'): ?>
                            <span class="badge bg-info"><?= $membership->getRemainingDays() ?> <?= lang('App.days') ?></span>
                            <?php else: ?>
                            -
                            <?php endif; ?>
                        </td>
                        <td><?= $membership->getStatusBadge() ?></td>
                        <td>
                            <?php if ($membership->status === 'active'): ?>
                            <a href="/admin/memberships/cancel/<?= $membership->id ?>" class="btn btn-sm btn-danger" onclick="return confirm('<?= lang('App.confirm_cancel') ?>')"><i class="bi bi-x-circle"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (isset($pager)): ?>
        <div class="d-flex justify-content-center">
            <?= $pager->links() ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
