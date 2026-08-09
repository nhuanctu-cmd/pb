<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3"><?= $pageTitle ?></h1>
    <a href="/admin/memberships" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> <?= lang('App.back') ?></a>
</div>

<div class="card">
    <div class="card-body">
        <form method="post" action="/admin/memberships/store">
            <div class="mb-3">
                <label class="form-label"><?= lang('App.player') ?> <span class="text-danger">*</span></label>
                <select name="player_id" class="form-select" required>
                    <option value=""><?= lang('App.select_player') ?></option>
                    <?php foreach ($players as $p): ?>
                    <option value="<?= $p->id ?>"><?= esc($p->full_name) ?> (<?= $p->player_code ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label"><?= lang('App.package') ?> <span class="text-danger">*</span></label>
                <select name="package_id" class="form-select" required>
                    <option value=""><?= lang('App.select_package') ?></option>
                    <?php foreach ($packages as $pkg): ?>
                    <option value="<?= $pkg->id ?>"><?= esc($pkg->getName()) ?> - <?= $pkg->getPriceFormatted() ?> (<?= $pkg->duration_days ?> <?= lang('App.days') ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary"><?= lang('App.save') ?></button>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
