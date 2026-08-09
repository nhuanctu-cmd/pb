<?= $this->extend('layouts/admin_master') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <?= flash_message() ?>

    <div class="card">
        <form method="post" action="<?= base_url('admin/branches/save-hours/' . $branch->id) ?>">
            <?= csrf_field() ?>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th><?= lang('App.day') ?></th>
                            <th><?= lang('App.branch_open_time') ?></th>
                            <th><?= lang('App.branch_close_time') ?></th>
                            <th><?= lang('App.branch_closed_day') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($days as $dayNum => $dayLabel): ?>
                            <?php $h = $hours[$dayNum] ?? null; ?>
                            <tr>
                                <td class="fw-semibold"><?= esc($dayLabel) ?></td>
                                <td><input type="time" class="form-control" name="hours[<?= $dayNum ?>][open_time]" value="<?= esc($h ? substr((string) $h->open_time, 0, 5) : '06:00') ?>"></td>
                                <td><input type="time" class="form-control" name="hours[<?= $dayNum ?>][close_time]" value="<?= esc($h ? substr((string) $h->close_time, 0, 5) : '22:00') ?>"></td>
                                <td>
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="hours[<?= $dayNum ?>][is_closed]" value="1" <?= ($h && (int) $h->is_closed === 1) ? 'checked' : '' ?>>
                                        <label class="form-check-label"><?= lang('App.branch_closed_day') ?></label>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer text-end">
                <a href="<?= base_url('admin/branches') ?>" class="btn btn-outline-secondary"><?= lang('App.back') ?></a>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> <?= lang('App.save') ?></button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
