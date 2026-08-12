<?= $this->extend('layouts/admin_master') ?>

<?= $this->section('content') ?>
<div class="erp-card">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div><h2 class="h5 mb-1">Cấu hình QR thanh toán</h2><p class="text-muted mb-0">Thông tin tài khoản nhận chuyển khoản cho tenant đang chọn.</p></div>
        <a href="<?= base_url('admin/payments') ?>" class="erp-btn">Quay lại hóa đơn</a>
    </div>
    <?php if (session('success')): ?><div class="alert alert-success"><?= esc(session('success')) ?></div><?php endif; ?>
    <?php if (session('error')): ?><div class="alert alert-danger"><?= esc(session('error')) ?></div><?php endif; ?>
    <form method="post" action="<?= base_url('admin/payments/save-qr-config') ?>" class="row g-3">
        <?= csrf_field() ?>
        <div class="col-md-4"><label class="form-label">Ngân hàng *</label><input class="form-control" name="bank_name" value="<?= esc(old('bank_name', $config->bank_name ?? '')) ?>" required></div>
        <div class="col-md-4"><label class="form-label">Số tài khoản *</label><input class="form-control" name="bank_account" value="<?= esc(old('bank_account', $config->bank_account ?? '')) ?>" required></div>
        <div class="col-md-4"><label class="form-label">Tên chủ tài khoản *</label><input class="form-control" name="account_name" value="<?= esc(old('account_name', $config->account_name ?? '')) ?>" required></div>
        <div class="col-12"><label class="form-label">QR template <span class="text-muted">(tuỳ chọn)</span></label><textarea class="form-control" name="qr_template" rows="3" placeholder="https://... hoặc mẫu QR của nhà cung cấp"><?= esc(old('qr_template', $config->qr_template ?? '')) ?></textarea></div>
        <div class="col-12 d-flex justify-content-end gap-2"><a href="<?= base_url('admin/payments') ?>" class="erp-btn">Hủy</a><button class="erp-btn erp-btn-primary"><i class="bi bi-save"></i> Lưu cấu hình</button></div>
    </form>
</div>
<?= $this->endSection() ?>
