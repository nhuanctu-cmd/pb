<?= $this->extend('layouts/admin_master') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <?= flash_message() ?>

    <div class="card mb-3">
        <div class="card-header fw-semibold"><?= lang('App.branch_holiday_add') ?></div>
        <form method="post" action="<?= base_url('admin/branches/store-holiday/' . $branch->id) ?>" class="card-body row g-2 align-items-end">
            <?= csrf_field() ?>
            <div class="col-md-3">
                <label class="form-label"><?= lang('App.date') ?> *</label>
                <input type="date" class="form-control" name="holiday_date" required>
            </div>
            <div class="col-md-4">
                <label class="form-label"><?= lang('App.branch_holiday_name') ?> *</label>
                <input type="text" class="form-control" name="name_vi" placeholder="VD: Tết Nguyên Đán" required>
            </div>
            <div class="col-md-3">
                <label class="form-label"><?= lang('App.note') ?></label>
                <input type="text" class="form-control" name="note">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100"><i class="bi bi-plus-lg"></i> <?= lang('App.create') ?></button>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th><?= lang('App.date') ?></th>
                        <th><?= lang('App.branch_holiday_name') ?></th>
                        <th><?= lang('App.note') ?></th>
                        <th class="text-end"><?= lang('App.actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($holidays)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4"><?= lang('App.no_data') ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($holidays as $h): ?>
                            <tr>
                                <td><?= format_date($h->holiday_date) ?></td>
                                <td><?= esc($h->name_vi) ?></td>
                                <td><?= esc($h->note ?? '-') ?></td>
                                <td class="text-end">
                                    <form method="post" action="<?= base_url('admin/branches/delete-holiday/' . $branch->id . '/' . $h->id) ?>" class="d-inline" onsubmit="return confirm('<?= lang('App.confirm_delete') ?>')">
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
