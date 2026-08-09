<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('title') ?>Chi tiết hóa đơn<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Hóa đơn: <?= esc($invoice['invoice_code']) ?></h5>
                <div>
                    <?php if ($invoice['status'] === 'unpaid' || $invoice['status'] === 'partial'): ?>
                    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#payCashModal">
                        <i class="fas fa-money-bill"></i> Thanh toán tiền mặt
                    </button>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#payBankQrModal">
                        <i class="fas fa-qrcode"></i> Chuyển khoản QR
                    </button>
                    <?php endif; ?>
                    <?php if ($invoice['paid_amount'] > 0 && $invoice['status'] !== 'cancelled'): ?>
                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#refundModal">
                        <i class="fas fa-undo"></i> Hoàn tiền
                    </button>
                    <?php endif; ?>
                    <?php if ($invoice['status'] !== 'cancelled' && $invoice['paid_amount'] == 0): ?>
                    <button class="btn btn-danger btn-sm" onclick="cancelInvoice(<?= $invoice['id'] ?>)">
                        <i class="fas fa-times"></i> Hủy hóa đơn
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless">
                            <tr><td class="text-muted">Mã hóa đơn</td><td><strong><?= esc($invoice['invoice_code']) ?></strong></td></tr>
                            <tr><td class="text-muted">Khách hàng</td><td><?= esc($invoice['player_name'] ?? 'Khách vãng lai') ?></td></tr>
                            <tr><td class="text-muted">Loại</td><td><?= $invoice['customer_type'] === 'player' ? 'Người chơi' : 'Khách' ?></td></tr>
                            <tr><td class="text-muted">Tham chiếu</td><td><?= $invoice['ref_type'] ? ucfirst($invoice['ref_type']) . ' #' . $invoice['ref_id'] : '-' ?></td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless">
                            <tr><td class="text-muted">Tạm tính</td><td class="text-end"><?= number_format($invoice['subtotal'], 0, ',', '.') ?>đ</td></tr>
                            <tr><td class="text-muted">Giảm giá</td><td class="text-end"><?= number_format($invoice['discount_amount'], 0, ',', '.') ?>đ</td></tr>
                            <tr><td class="text-muted fw-bold">Tổng tiền</td><td class="text-end fw-bold fs-5"><?= number_format($invoice['total_amount'], 0, ',', '.') ?>đ</td></tr>
                            <tr><td class="text-muted">Đã thanh toán</td><td class="text-end text-success"><?= number_format($invoice['paid_amount'], 0, ',', '.') ?>đ</td></tr>
                            <tr><td class="text-muted">Còn lại</td><td class="text-end text-danger fw-bold"><?= number_format($invoice['total_amount'] - $invoice['paid_amount'], 0, ',', '.') ?>đ</td></tr>
                            <tr>
                                <td>Trạng thái</td>
                                <td class="text-end">
                                    <?php $statusClass = ['unpaid' => 'warning', 'partial' => 'info', 'paid' => 'success', 'cancelled' => 'danger', 'refunded' => 'secondary']; ?>
                                    <span class="badge bg-<?= $statusClass[$invoice['status']] ?? 'secondary' ?> fs-6">
                                        <?= lang('Payment.' . $invoice['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">Lịch sử thanh toán</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Mã thanh toán</th>
                                <th>Phương thức</th>
                                <th>Số tiền</th>
                                <th>Trạng thái</th>
                                <th>Mã giao dịch</th>
                                <th>Ngày</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($payments)): ?>
                            <tr><td colspan="6" class="text-center text-muted">Chưa có thanh toán</td></tr>
                            <?php else: ?>
                            <?php foreach ($payments as $p): ?>
                            <tr>
                                <td><?= esc($p['payment_code']) ?></td>
                                <td><?= lang('Payment.' . $p['method']) ?></td>
                                <td class="text-end"><?= number_format($p['amount'], 0, ',', '.') ?>đ</td>
                                <td>
                                    <span class="badge bg-<?= $p['status'] === 'success' ? 'success' : ($p['status'] === 'pending' ? 'warning' : 'danger') ?>">
                                        <?= lang('Payment.' . $p['status']) ?>
                                    </span>
                                </td>
                                <td><?= esc($p['transaction_ref'] ?? '-') ?></td>
                                <td><?= $p['paid_at'] ? date('d/m/Y H:i', strtotime($p['paid_at'])) : '-' ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php if (!empty($refunds)): ?>
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Lịch sử hoàn tiền</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Số tiền</th>
                                <th>Lý do</th>
                                <th>Trạng thái</th>
                                <th>Ngày</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($refunds as $r): ?>
                            <tr>
                                <td class="text-end text-danger">-<?= number_format($r['amount'], 0, ',', '.') ?>đ</td>
                                <td><?= esc($r['reason'] ?? '-') ?></td>
                                <td>
                                    <span class="badge bg-<?= $r['status'] === 'completed' ? 'success' : 'warning' ?>">
                                        <?= lang('Payment.' . $r['status']) ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Pay Cash Modal -->
<div class="modal fade" id="payCashModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="payCashForm">
                <div class="modal-header">
                    <h5 class="modal-title">Thanh toán tiền mặt</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Số tiền cần thanh toán</label>
                        <h3 class="text-primary"><?= number_format($invoice['total_amount'] - $invoice['paid_amount'], 0, ',', '.') ?>đ</h3>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Số tiền khách đưa</label>
                        <input type="number" class="form-control form-control-lg" name="amount"
                               min="<?= $invoice['total_amount'] - $invoice['paid_amount'] ?>"
                               value="<?= $invoice['total_amount'] - $invoice['paid_amount'] ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tiền thừa</label>
                        <h4 class="text-success" id="changeAmount">0đ</h4>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Xác nhận thanh toán
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bank QR Modal -->
<div class="modal fade" id="payBankQrModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chuyển khoản QR</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center" id="bankQrContent">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Đang tạo QR...</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-primary" onclick="confirmBankPayment()">
                    <i class="fas fa-check"></i> Xác nhận đã chuyển khoản
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Refund Modal -->
<div class="modal fade" id="refundModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="refundForm">
                <div class="modal-header">
                    <h5 class="modal-title">Hoàn tiền</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Số tiền hoàn</label>
                        <input type="number" class="form-control" name="amount"
                               max="<?= $invoice['paid_amount'] ?>"
                               value="<?= $invoice['paid_amount'] ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Lý do</label>
                        <textarea class="form-control" name="reason" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-undo"></i> Xác nhận hoàn tiền
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.getElementById('payCashForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('<?= site_url('admin/payments/pay-cash/' . $invoice['id']) ?>', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Thanh toán thành công!');
            location.reload();
        } else {
            alert(data.error || 'Lỗi thanh toán');
        }
    })
    .catch(e => alert('Lỗi: ' + e.message));
});

document.getElementById('refundForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    if (!confirm('Xác nhận hoàn tiền?')) return;
    const formData = new FormData(this);
    fetch('<?= site_url('admin/payments/refund/' . $invoice['id']) ?>', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Hoàn tiền thành công!');
            location.reload();
        } else {
            alert(data.error || 'Lỗi hoàn tiền');
        }
    })
    .catch(e => alert('Lỗi: ' + e.message));
});

function cancelInvoice(id) {
    if (!confirm('Xác nhận hủy hóa đơn này?')) return;
    fetch('<?= site_url('admin/payments/cancel/') ?>' + id, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'reason=Hủy bởi admin'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Hủy hóa đơn thành công!');
            location.reload();
        } else {
            alert(data.error || 'Lỗi hủy hóa đơn');
        }
    })
    .catch(e => alert('Lỗi: ' + e.message));
}

// Bank QR
document.getElementById('payBankQrModal')?.addEventListener('show.bs.modal', function() {
    fetch('<?= site_url('admin/payments/create-bank-qr/' . $invoice['id']) ?>', {
        method: 'POST'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('bankQrContent').innerHTML = `
                <div class="mb-3">
                    <h5>Chuyển khoản đến</h5>
                    <p class="mb-1"><strong>Ngân hàng:</strong> ${data.bank_info.bank_name}</p>
                    <p class="mb-1"><strong>Số TK:</strong> ${data.bank_info.account_number}</p>
                    <p class="mb-1"><strong>Chủ TK:</strong> ${data.bank_info.account_name}</p>
                    <p class="mb-1"><strong>Số tiền:</strong> ${data.amount.toLocaleString()}đ</p>
                    <p><strong>Nội dung:</strong> ${data.payment_code}</p>
                    <div class="border p-3 bg-light">
                        <code>${data.qr_content}</code>
                    </div>
                </div>
                <input type="hidden" id="currentPaymentId" value="${data.payment_id}">
            `;
        } else {
            document.getElementById('bankQrContent').innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
        }
    })
    .catch(e => {
        document.getElementById('bankQrContent').innerHTML = `<div class="alert alert-danger">Lỗi: ${e.message}</div>`;
    });
});

function confirmBankPayment() {
    const paymentId = document.getElementById('currentPaymentId')?.value;
    if (!paymentId) return alert('Chưa có QR thanh toán');
    if (!confirm('Xác nhận đã nhận được chuyển khoản?')) return;

    fetch('<?= site_url('admin/payments/confirm-bank-payment/') ?>' + paymentId, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'transaction_ref=manual_' + Date.now()
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Xác nhận thanh toán thành công!');
            location.reload();
        } else {
            alert(data.error || 'Lỗi xác nhận');
        }
    })
    .catch(e => alert('Lỗi: ' + e.message));
}
</script>
<?= $this->endSection() ?>
