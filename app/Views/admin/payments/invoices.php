<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('title') ?>Quản lý thanh toán<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Danh sách hóa đơn</h5>
        <div>
            <a href="<?= site_url('admin/payments/qr-config') ?>" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-qrcode"></i> Cấu hình QR
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-4">
                <select class="form-select" id="statusFilter" onchange="window.location.href='<?= site_url('admin/payments') ?>?status='+this.value">
                    <option value="">Tất cả trạng thái</option>
                    <option value="unpaid" <?= ($current_status ?? '') === 'unpaid' ? 'selected' : '' ?>>Chưa thanh toán</option>
                    <option value="partial" <?= ($current_status ?? '') === 'partial' ? 'selected' : '' ?>>Thanh toán một phần</option>
                    <option value="paid" <?= ($current_status ?? '') === 'paid' ? 'selected' : '' ?>>Đã thanh toán</option>
                    <option value="cancelled" <?= ($current_status ?? '') === 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
                    <option value="refunded" <?= ($current_status ?? '') === 'refunded' ? 'selected' : '' ?>>Đã hoàn tiền</option>
                </select>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Mã hóa đơn</th>
                        <th>Khách hàng</th>
                        <th>Tổng tiền</th>
                        <th>Đã thanh toán</th>
                        <th>Còn lại</th>
                        <th>Trạng thái</th>
                        <th>Ngày tạo</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($invoices)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted">Chưa có hóa đơn nào</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($invoices as $inv): ?>
                    <tr>
                        <td><strong><?= esc($inv['invoice_code']) ?></strong></td>
                        <td><?= esc($inv['player_name'] ?? 'Khách vãng lai') ?></td>
                        <td><?= number_format($inv['total_amount'], 0, ',', '.') ?>đ</td>
                        <td><?= number_format($inv['paid_amount'], 0, ',', '.') ?>đ</td>
                        <td><?= number_format($inv['total_amount'] - $inv['paid_amount'], 0, ',', '.') ?>đ</td>
                        <td>
                            <?php $statusClass = ['unpaid' => 'warning', 'partial' => 'info', 'paid' => 'success', 'cancelled' => 'danger', 'refunded' => 'secondary']; ?>
                            <span class="badge bg-<?= $statusClass[$inv['status']] ?? 'secondary' ?>">
                                <?= lang('Payment.' . $inv['status']) ?>
                            </span>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($inv['created_at'])) ?></td>
                        <td>
                            <a href="<?= site_url('admin/payments/detail/' . $inv['id']) ?>" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
