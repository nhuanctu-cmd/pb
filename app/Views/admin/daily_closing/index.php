<?= $this->extend('admin/layouts/main') ?>
<?= $this->section('content') ?>

<?php
$payments = $snapshot['payments'] ?? [];
$branchId = (int) ($scopeBranchId ?? 0);
$branchName = 'Toan tenant';
if ($branchId > 0 && !empty($branches)) {
    foreach ($branches as $branch) {
        if ((int) $branch->id === $branchId) {
            $branchName = $branch->name;
            break;
        }
    }
}
$csvQuery = ['date' => $date];
if ($branchId > 0) {
    $csvQuery['branch_id'] = $branchId;
}
$csvQueryString = http_build_query($csvQuery);
$printQueryString = $csvQueryString;
?>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">Daily Closing</h1>
        <div class="text-muted">Doi soat tien ngay (booking + pos + invoice)</div>
        <small class="text-muted">Ban: <?= esc($branchName) ?> — <?= esc($date) ?></small>
    </div>
        <form method="get" class="d-flex gap-2">
        <?php if (is_superadmin()): ?>
            <select name="branch_id" class="form-select form-select-sm">
                <option value="">Toan tenant</option>
                <?php foreach ($branches as $branch): ?>
                    <option value="<?= (int) $branch->id ?>" <?= $branchId === (int) $branch->id ? 'selected' : '' ?>><?= esc($branch->name) ?></option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>
        <input class="form-control form-control-sm" type="date" name="date" value="<?= esc($date) ?>">
        <button class="btn btn-outline-primary btn-sm">Xem</button>
        <a class="btn btn-outline-success btn-sm" href="/admin/daily-closing/csv?<?= $csvQueryString ?>">
            <i class="bi bi-download"></i> Xuất CSV
        </a>
        <a class="btn btn-outline-danger btn-sm" href="/admin/daily-closing/pdf?<?= $printQueryString ?>">
            <i class="bi bi-file-earmark-pdf"></i> In PDF
        </a>
        <a class="btn btn-outline-dark btn-sm" href="/admin/daily-closing/print?<?= $printQueryString ?>" target="_blank">
            <i class="bi bi-printer me-1"></i> In biên bản
        </a>
    </form>
</div>

<?php
$alertClass = (($closing->status ?? '') === 'closed') ? 'success' : 'warning';
$closedText = (($closing->status ?? '') === 'closed') ? 'Da dong' : 'Dang mo';
?>
<div class="alert alert-<?= esc($alertClass) ?>">
    <i class="bi bi-lock<?= (($closing->status ?? '') === 'closed' ? '-fill' : '') ?> me-2"></i>
    Ca: <strong><?= esc($date) ?></strong> / <strong><?= esc($branchName) ?></strong> — trạng thái:
    <strong><?= esc($closedText) ?></strong>.
</div>

<div class="row g-3 mb-4">
    <?php foreach (
        [
            ['Tien mat', $payments['cash'] ?? 0, 'success'],
            ['QR ngan hang', $payments['bank_qr'] ?? 0, 'primary'],
            ['Vi / MoMo', (($payments['wallet'] ?? 0) + ($payments['other'] ?? 0)), 'info'],
            ['Doanh so booking', $snapshot['invoices']['billed'] ?? 0, 'secondary'],
            ['POS + Booking', (($snapshot['billed_total'] ?? 0)), 'warning'],
            ['Da thu', (($snapshot['collected_total'] ?? 0)), 'dark'],
        ] as $metric
    ): ?>
        <div class="col-6 col-xl-2">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small"><?= esc($metric[0]) ?></div>
                    <div class="h4 mb-0 text-<?= esc($metric[2]) ?>"><?= number_format((float) $metric[1], 0, ',', '.') ?>đ</div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-header">Chi tiet ngay</div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2"><span>Booking</span><strong><?= (int) ($snapshot['bookings']['total'] ?? 0) ?></strong></div>
                <div class="d-flex justify-content-between mb-2"><span>No-show</span><strong><?= (int) ($snapshot['bookings']['no_show'] ?? 0) ?></strong></div>
                <div class="d-flex justify-content-between mb-2"><span>Huy/hoan</span><strong><?= (int) ($snapshot['bookings']['cancelled'] ?? 0) ?></strong></div>
                <div class="d-flex justify-content-between mb-2"><span>POS order</span><strong><?= (int) ($snapshot['pos_orders']['count'] ?? 0) ?></strong></div>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header">Chot ca</div>
            <div class="card-body">
                <?php if (($closing->status ?? '') !== 'closed'): ?>
                    <form method="post" action="/admin/daily-closing/close" class="row g-2">
                        <?= csrf_field() ?>
                        <?php if (is_superadmin()): ?>
                            <input type="hidden" name="branch_id" value="<?= (int) ($scopeBranchId ?? 0) ?>">
                        <?php endif; ?>
                        <input type="hidden" name="closing_date" value="<?= esc($date) ?>">
                        <div class="col-md-4">
                            <label class="form-label">So tien mat (điem nhat)</label>
                            <input class="form-control" name="declared_cash" type="number" min="0" step="1000" value="<?= esc((string) ($payments['cash'] ?? 0)) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Dieu chinh tay</label>
                            <input class="form-control" name="manual_adjustment" type="number" step="1000" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Ly do dieu chinh</label>
                            <input class="form-control" name="adjustment_reason" placeholder="Neu co">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Chữ ký số</label>
                            <input class="form-control" name="signature_name" placeholder="Tên nhân viên xác nhận">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Ghi chu</label>
                            <input class="form-control" name="notes" placeholder="Nhãn giao ca, nguoi nhan, bien banh...">
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <button class="btn btn-success w-100">
                                <i class="bi bi-lock me-1"></i> Dong ca
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="mb-2">
                        <small class="text-muted">Chenh lech</small>
                        <div class="h4 mb-0"><?= number_format((float) ($closing->discrepancy_amount ?? 0), 0, ',', '.') ?>đ</div>
                    </div>
                    <p class="text-muted mb-3">Luu y: bang so voi cash he thong sau khi da tinh dieu chinh tay (neu co).</p>
                    <form method="post" action="/admin/daily-closing/reopen" class="d-inline">
                        <?= csrf_field() ?>
                        <?php if (is_superadmin()): ?>
                            <input type="hidden" name="branch_id" value="<?= (int) ($scopeBranchId ?? 0) ?>">
                        <?php endif; ?>
                        <input type="hidden" name="closing_date" value="<?= esc($date) ?>">
                        <button class="btn btn-outline-danger" onclick="return confirm('Mo lai ca cho dieu chinh?')">
                            <i class="bi bi-unlock me-1"></i>Mo lai ca
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
