<?= $this->extend('admin/layouts/main') ?>
<?= $this->section('content') ?>

<?php
$options = $segmentOptions ?? [];
?>

<div class="mb-3">
    <h1 class="h3 mb-1">CRM Campaign</h1>
    <div class="text-muted">Tạo chiến dịch theo phân khúc khách hàng và đồng bộ danh sách người nhận.</div>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header"><strong>Tạo chiến dịch nháp</strong></div>
            <div class="card-body">
                <form method="post" action="/admin/crm-campaigns/store">
                    <?= csrf_field() ?>
                    <div class="mb-2"><label class="form-label">Tên chiến dịch</label><input class="form-control" name="name" required placeholder="Nhắc gia hạn tháng 9"></div>
                    <div class="row g-2">
                        <div class="col-6"><label class="form-label">Kênh</label><select name="channel" class="form-select"><option value="in_app">In-app</option><option value="email">Email</option><option value="sms">SMS</option><option value="zalo">Zalo</option></select></div>
                        <div class="col-6"><label class="form-label">Phân khúc</label><select name="segment" class="form-select"><?php foreach ($options as $key => $option): ?><option value="<?= esc($key) ?>"><?= esc($option['label'] ?? $key) ?></option><?php endforeach; ?></select></div>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label">Rate limit (items / phút)</label>
                            <input class="form-control" type="number" name="throttle_per_minute" min="1" max="1200" value="60">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Retry tối đa</label>
                            <input class="form-control" type="number" name="max_retries" min="1" max="10" value="3">
                        </div>
                    </div>
                    <div class="mb-2 mt-2"><label class="form-label">Lên lịch gửi</label><input type="datetime-local" name="scheduled_at" class="form-control" value=""></div>
                    <div class="small text-muted mt-2"><?php if (isset($options['all'])): ?><span class="d-block">Mô tả mẫu: <?= esc($options['all']['description']) ?></span><?php endif; ?></div>
                    <div class="mb-2 mt-2"><label class="form-label">Tiêu đề</label><input class="form-control" name="subject"></div>
                    <div class="mb-3"><label class="form-label">Nội dung</label><textarea class="form-control" name="message" rows="4" required placeholder="Xin chào {{customer_name}}..."></textarea></div>
                    <button class="btn btn-primary w-100"><i class="bi bi-save me-1"></i>Lưu nháp</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header"><strong>Chiến dịch đã tạo</strong></div>
            <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Chiến dịch</th><th>Phân khúc</th><th>Kênh</th><th>Trạng thái</th><th class="text-end">Thao tác</th></tr></thead>
                    <tbody>
                        <?php if (empty($campaigns)): ?><tr><td colspan="5" class="text-center text-muted py-4">Chưa có chiến dịch.</td></tr><?php endif; ?>
                        <?php foreach ($campaigns as $campaign): ?>
                            <tr>
                                <td><strong><?= esc($campaign->name) ?></strong><br><small class="text-muted"><?= esc($campaign->subject) ?></small></td>
                                <td><strong><?= esc($options[$campaign->segment]['label'] ?? $campaign->segment) ?></strong><br><small class="text-muted"><?= esc($options[$campaign->segment]['description'] ?? '-') ?></small></td>
                                <td><?= esc($campaign->channel) ?></td>
                                <td>
                                    <span class="badge text-bg-light"><?= esc($campaign->status) ?></span>
                                    <span class="small text-muted d-block">Dự kiến: <?= esc($campaign->scheduled_at ?? '-') ?></span>
                                    <small class="text-muted d-block">Dự kiến nhận: <?= (int) ($campaign->recipient_count ?? 0) ?> khách</small>
                                    <?php if (!empty($campaign->discrepancy_reason ?? '')): ?>
                                        <small class="text-danger d-block"><?= esc($campaign->discrepancy_reason) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($campaign->status === 'draft' || $campaign->status === 'scheduled'): ?>
                                        <form method="post" action="/admin/crm-campaigns/launch/<?= (int) $campaign->id ?>" class="d-inline">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-people me-1"></i><?= $campaign->status === 'scheduled' ? 'Chạy ngay' : 'Tạo người nhận' ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($campaign->status !== 'cancelled' && $campaign->status !== 'completed'): ?>
                                        <form method="post" action="/admin/crm-campaigns/cancel/<?= (int) $campaign->id ?>" class="d-inline">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-sm btn-outline-danger ms-1">Huỷ</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if (in_array($campaign->status, ['running', 'completed', 'scheduled'], true)): ?>
                                        <form method="post" action="/admin/crm-campaigns/retry/<?= (int) $campaign->id ?>" class="d-inline">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-sm btn-outline-warning ms-1">Retry</button>
                                        </form>
                                    <?php endif; ?>
                                    <div class="mt-2">
                                        <form method="post" action="/admin/crm-campaigns/send-test/<?= (int) $campaign->id ?>" class="d-flex gap-1 justify-content-end">
                                            <?= csrf_field() ?>
                                            <input type="text" class="form-control form-control-sm" style="max-width: 200px" name="recipient" placeholder="SĐT/Email test" required>
                                            <button class="btn btn-sm btn-outline-secondary">Test gửi</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card shadow-sm mt-3">
            <div class="card-header">Chiến dịch nền</div>
            <div class="card-body">
                <form method="post" action="/admin/crm-campaigns/dispatch" class="d-flex gap-2 align-items-center">
                    <?= csrf_field() ?>
                    <span class="text-muted">Chạy toàn bộ campaign đến hạn theo cấu hình rate limit + retry.</span>
                    <button class="btn btn-outline-primary btn-sm ms-auto">Dispatch ngay</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
