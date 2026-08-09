<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-body">
        <form method="post" action="/admin/profile" class="row g-3">
            <div class="col-md-6">
                <label class="form-label">First name</label>
                <input name="first_name" class="form-control" value="<?= old('first_name', $user->first_name ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Last name</label>
                <input name="last_name" class="form-control" value="<?= old('last_name', $user->last_name ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Email</label>
                <input class="form-control" value="<?= esc($user->email ?? '') ?>" disabled>
            </div>
            <div class="col-md-6">
                <label class="form-label">Phone</label>
                <input name="phone" class="form-control" value="<?= old('phone', $user->phone ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">New password</label>
                <input type="password" name="password" class="form-control" autocomplete="new-password">
            </div>
            <div class="col-12">
                <button class="btn btn-primary" type="submit"><i class="bi bi-save"></i> <?= lang('App.save') ?></button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
