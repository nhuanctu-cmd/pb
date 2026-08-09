<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<form method="post" action="/admin/settings/update" class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Key</th><th>Value</th><th>Group</th></tr></thead>
            <tbody>
                <?php foreach (($settings ?? []) as $setting): ?>
                <tr>
                    <td><code><?= esc($setting->key) ?></code></td>
                    <td><input class="form-control" name="settings[<?= esc($setting->key) ?>]" value="<?= esc($setting->value) ?>"></td>
                    <td><?= esc($setting->group ?? 'general') ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($settings)): ?>
                <tr><td colspan="3" class="text-center text-muted py-4"><?= lang('App.no_data') ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer text-end">
        <button class="btn btn-primary" type="submit"><i class="bi bi-save"></i> <?= lang('App.save') ?></button>
    </div>
</form>
<?= $this->endSection() ?>
