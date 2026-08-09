<?= $this->extend('layouts/admin_master') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <?= flash_message() ?>
    <?php $errors = session('errors') ?? []; ?>
    <?php if (! empty($errors)): ?>
        <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= esc($e) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <div class="card">
        <form method="post" action="<?= base_url($branch ? 'admin/branches/update/' . $branch->id : 'admin/branches/create') ?>">
            <?= csrf_field() ?>
            <div class="card-body row g-3">
                <div class="col-md-3">
                    <label class="form-label"><?= lang('App.branch_code') ?> *</label>
                    <input type="text" class="form-control" name="code" value="<?= esc(old('code', $branch->code ?? '')) ?>" placeholder="VD: CN-Q1" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label"><?= lang('App.branch_name') ?> *</label>
                    <input type="text" class="form-control" name="name" value="<?= esc(old('name', $branch->name ?? '')) ?>" placeholder="VD: Chi nhánh Quận 1" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?= lang('App.status') ?> *</label>
                    <select class="form-select" name="status">
                        <?php foreach (['active', 'inactive', 'maintenance', 'closed'] as $st): ?>
                            <option value="<?= $st ?>" <?= old('status', $branch->status ?? 'active') === $st ? 'selected' : '' ?>><?= lang('App.status_' . $st) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label"><?= lang('App.phone') ?></label>
                    <input type="text" class="form-control" name="phone" value="<?= esc(old('phone', $branch->phone ?? '')) ?>" placeholder="0901 234 567">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" value="<?= esc(old('email', $branch->email ?? '')) ?>" placeholder="branch@example.com">
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?= lang('App.branch_city') ?></label>
                    <input type="text" class="form-control" name="city" value="<?= esc(old('city', $branch->city ?? '')) ?>" placeholder="TP. HCM">
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?= lang('App.branch_district') ?></label>
                    <input type="text" class="form-control" name="district" value="<?= esc(old('district', $branch->district ?? '')) ?>" placeholder="Quận 1">
                </div>
                <div class="col-12">
                    <label class="form-label"><?= lang('App.branch_address') ?></label>
                    <textarea class="form-control" name="address" rows="2" placeholder="Số nhà, đường, phường..."><?= esc(old('address', $branch->address ?? '')) ?></textarea>
                </div>
                <div class="col-12 form-check ms-2">
                    <input type="checkbox" class="form-check-input" name="is_main" id="is_main" value="1" <?= old('is_main', $branch->is_main ?? 0) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_main"><?= lang('App.branch_is_main') ?></label>
                </div>
            </div>
            <div class="card-footer text-end">
                <a href="<?= base_url('admin/branches') ?>" class="btn btn-outline-secondary"><?= lang('App.cancel') ?></a>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> <?= lang('App.save') ?></button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
