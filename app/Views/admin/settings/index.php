<?= $this->extend('layouts/admin_master') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <?= flash_message() ?>

    <div class="row">
        <div class="col-md-3">
            <div class="list-group mb-3">
                <?php foreach ($groups as $key => $label): ?>
                    <a href="<?= base_url("admin/settings?group={$key}") ?>"
                       class="list-group-item list-group-item-action <?= $currentGroup === $key ? 'active' : '' ?>">
                        <?= esc($label) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="col-md-9">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><?= esc($groups[$currentGroup] ?? $currentGroup) ?></h5>
                </div>
                <form method="post" action="<?= base_url('admin/settings/update') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="group" value="<?= esc($currentGroup) ?>">
                    <div class="card-body">
                        <?php if (empty($settings)): ?>
                            <div class="text-center text-muted py-4"><?= lang('App.no_data') ?></div>
                        <?php else: ?>
                            <?php foreach ($settings as $key => $setting): ?>
                                <div class="mb-3">
                                    <label class="form-label">
                                        <?= lang("App.setting_{$key}") ?: esc($key) ?>
                                        <?php if ($setting['is_override']): ?>
                                            <span class="badge bg-info"><?= lang('App.settings_override') ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><?= lang('App.settings_default') ?></span>
                                        <?php endif; ?>
                                    </label>
                                    <?php if ($setting['type'] === 'textarea'): ?>
                                        <textarea class="form-control" name="settings[<?= esc($key) ?>]" rows="3"><?= esc($setting['value']) ?></textarea>
                                    <?php elseif ($setting['type'] === 'boolean'): ?>
                                        <select class="form-select" name="settings[<?= esc($key) ?>]">
                                            <option value="1" <?= $setting['value'] == '1' ? 'selected' : '' ?>><?= lang('App.active') ?></option>
                                            <option value="0" <?= $setting['value'] == '0' ? 'selected' : '' ?>><?= lang('App.inactive') ?></option>
                                        </select>
                                    <?php elseif ($setting['type'] === 'number'): ?>
                                        <input type="number" class="form-control" name="settings[<?= esc($key) ?>]" value="<?= esc($setting['value']) ?>">
                                    <?php else: ?>
                                        <input type="text" class="form-control" name="settings[<?= esc($key) ?>]" value="<?= esc($setting['value']) ?>">
                                    <?php endif; ?>
                                    <div class="form-text text-muted"><?= esc($key) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> <?= lang('App.save') ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
