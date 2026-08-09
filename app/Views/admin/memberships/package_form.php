<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3"><?= $pageTitle ?></h1>
    <a href="/admin/memberships/packages" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> <?= lang('App.back') ?></a>
</div>

<div class="card">
    <div class="card-body">
        <form method="post" action="/admin/memberships/<?= isset($package) ? 'update-package/' . $package->id : 'store-package' ?>">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?= lang('App.name_vi') ?> <span class="text-danger">*</span></label>
                    <input type="text" name="name_vi" class="form-control" value="<?= esc($package->name_vi ?? old('name_vi')) ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?= lang('App.name_en') ?></label>
                    <input type="text" name="name_en" class="form-control" value="<?= esc($package->name_en ?? old('name_en')) ?>">
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label"><?= lang('App.duration_days') ?> <span class="text-danger">*</span></label>
                    <input type="number" name="duration_days" class="form-control" value="<?= $package->duration_days ?? old('duration_days', 30) ?>" required min="1">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label"><?= lang('App.price') ?> <span class="text-danger">*</span></label>
                    <input type="number" step="1000" name="price" class="form-control" value="<?= $package->price ?? old('price', 0) ?>" required min="0">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label"><?= lang('App.discount_percent') ?></label>
                    <input type="number" step="0.1" name="discount_percent" class="form-control" value="<?= $package->discount_percent ?? old('discount_percent', 0) ?>" min="0" max="100">
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label"><?= lang('App.booking_priority') ?></label>
                    <input type="number" name="booking_priority" class="form-control" value="<?= $package->booking_priority ?? old('booking_priority', 0) ?>" min="0" max="10">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label"><?= lang('App.status') ?></label>
                    <select name="status" class="form-select">
                        <option value="active" <?= ($package->status ?? 'active') === 'active' ? 'selected' : '' ?>><?= lang('App.active') ?></option>
                        <option value="inactive" <?= ($package->status ?? '') === 'inactive' ? 'selected' : '' ?>><?= lang('App.inactive') ?></option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><?= lang('App.save') ?></button>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
