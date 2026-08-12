<?= $this->extend('layouts/admin_master') ?>

<?= $this->section('content') ?>
<div class="erp-card">
    <div class="d-flex justify-content-between align-items-center mb-4"><div><h2 class="h5 mb-1">Thêm tenant</h2><p class="text-muted mb-0">Tạo một cụm sân/đơn vị mới trong nền tảng.</p></div><a href="<?= base_url('admin/tenants') ?>" class="erp-btn">Quay lại</a></div>
    <?php $errors = session('errors') ?? []; if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?= esc($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="post" action="<?= base_url('admin/tenants/store') ?>" class="row g-3">
        <?= csrf_field() ?>
        <div class="col-md-3"><label class="form-label">Mã tenant *</label><input name="code" class="form-control" value="<?= esc(old('code')) ?>" maxlength="50" required></div>
        <div class="col-md-9"><label class="form-label">Tên tenant *</label><input name="name" class="form-control" value="<?= esc(old('name')) ?>" maxlength="255" required></div>
        <div class="col-md-4"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= esc(old('email')) ?>"></div>
        <div class="col-md-4"><label class="form-label">Điện thoại</label><input name="phone" class="form-control" value="<?= esc(old('phone')) ?>"></div>
        <div class="col-md-4"><label class="form-label">Trạng thái *</label><select name="status" class="form-select"><option value="active" <?= old('status', 'active') === 'active' ? 'selected' : '' ?>>Hoạt động</option><option value="inactive" <?= old('status') === 'inactive' ? 'selected' : '' ?>>Không hoạt động</option><option value="suspended" <?= old('status') === 'suspended' ? 'selected' : '' ?>>Tạm ngưng</option></select></div>
        <div class="col-12"><label class="form-label">Địa chỉ</label><textarea name="address" rows="3" class="form-control"><?= esc(old('address')) ?></textarea></div>
        <div class="col-12 d-flex justify-content-end gap-2"><a href="<?= base_url('admin/tenants') ?>" class="erp-btn">Hủy</a><button class="erp-btn erp-btn-primary"><i class="bi bi-building-add"></i> Tạo tenant</button></div>
    </form>
</div>
<?= $this->endSection() ?>
