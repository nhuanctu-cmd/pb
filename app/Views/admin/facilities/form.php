<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-body">
        <?php if (session()->has('errors')): ?>
            <div class="alert alert-danger">
                <?php foreach (session('errors') as $error): ?>
                    <div><?= esc($error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="/admin/facilities/<?= isset($facility) ? 'update/' . $facility->id : 'create' ?>" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Code</label>
                <input name="code" class="form-control" value="<?= old('code', $facility->code ?? '') ?>" required>
            </div>
            <div class="col-md-5">
                <label class="form-label">Name VI</label>
                <input name="name_vi" class="form-control" value="<?= old('name_vi', $facility->name_vi ?? '') ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Name EN</label>
                <input name="name_en" class="form-control" value="<?= old('name_en', $facility->name_en ?? '') ?>">
            </div>
            <div class="col-md-8">
                <label class="form-label">Address</label>
                <input name="address" class="form-control" value="<?= old('address', $facility->address ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Phone</label>
                <input name="phone" class="form-control" value="<?= old('phone', $facility->phone ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <?php foreach (['active', 'inactive', 'suspended'] as $status): ?>
                    <option value="<?= $status ?>" <?= old('status', $facility->status ?? 'active') === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= old('email', $facility->email ?? '') ?>">
            </div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary" type="submit"><i class="bi bi-save"></i> <?= lang('App.save') ?></button>
                <a href="/admin/facilities" class="btn btn-outline-secondary"><?= lang('App.cancel') ?></a>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
