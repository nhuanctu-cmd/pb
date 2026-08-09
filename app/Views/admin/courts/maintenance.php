<?= $this->extend('layouts/admin_master') ?>

<?= $this->section('content') ?>
<div class="erp-action-bar">
    <div class="d-flex gap-2 flex-wrap">
        <a href="/admin/courts/edit/<?= $court->id ?>" class="erp-btn"><i class="bi bi-pencil"></i> Sửa sân</a>
        <a href="/admin/courts?branch_id=<?= $court->branch_id ?>" class="erp-btn"><i class="bi bi-arrow-left"></i> Danh sách sân</a>
    </div>
    <?= renderStatusBadge($court->status, 'court') ?>
</div>

<div class="erp-grid-2">
    <form action="/admin/courts/store-maintenance/<?= $court->id ?>" method="post" class="erp-form">
        <?= csrf_field() ?>
        <section class="erp-form-section">
            <div class="erp-form-section-header">
                <h2>Lên lịch bảo trì</h2>
                <div class="erp-muted"><?= esc($court->code) ?> - <?= esc($court->name_vi) ?></div>
            </div>
            <div class="erp-form-section-body erp-form-grid">
                <div class="erp-field">
                    <label>Bắt đầu <span class="erp-required">*</span></label>
                    <input type="datetime-local" name="start_time" class="erp-control" value="<?= old('start_time') ?>" required>
                </div>
                <div class="erp-field">
                    <label>Kết thúc</label>
                    <input type="datetime-local" name="end_time" class="erp-control" value="<?= old('end_time') ?>">
                    <div class="erp-hint">Để trống nếu chưa xác định thời điểm hoàn tất.</div>
                </div>
            </div>
            <div class="erp-form-section-body pt-0">
                <div class="erp-field">
                    <label>Lý do <span class="erp-required">*</span></label>
                    <textarea name="reason" class="erp-textarea" required placeholder="Ví dụ: thay lưới, vệ sinh mặt sân, sửa đèn"><?= old('reason') ?></textarea>
                </div>
            </div>
            <div class="erp-sticky-save">
                <a href="/admin/courts?branch_id=<?= $court->branch_id ?>" class="erp-btn">Hủy</a>
                <button class="erp-btn erp-btn-primary" data-confirm="Tạo lịch bảo trì cho sân này?"><i class="bi bi-tools"></i> Lưu bảo trì</button>
            </div>
        </section>
    </form>

    <aside class="erp-dashboard-stack">
        <section class="erp-card">
            <div class="erp-card-header"><strong>Lịch sử bảo trì</strong><span class="erp-muted"><?= count($maintenanceRecords ?? []) ?> bản ghi</span></div>
            <div class="erp-card-body erp-timeline">
                <?php foreach ($maintenanceRecords ?? [] as $record): ?>
                    <?= renderTimelineItem(
                        esc($record->reason),
                        date('d/m/Y H:i', strtotime($record->start_time)) . ($record->end_time ? ' - ' . date('d/m/Y H:i', strtotime($record->end_time)) : ''),
                        $record->status === 'completed' ? 'success' : ($record->status === 'cancelled' ? 'muted' : 'warning'),
                        'Trạng thái: ' . esc($record->status)
                    ) ?>
                <?php endforeach; ?>
                <?php if (empty($maintenanceRecords)): ?>
                    <div class="erp-empty">
                        <div class="erp-empty-icon"><i class="bi bi-tools"></i></div>
                        Chưa có lịch bảo trì.
                    </div>
                <?php endif; ?>
            </div>
        </section>
        <section class="erp-card">
            <div class="erp-card-header"><strong>Tác động vận hành</strong></div>
            <div class="erp-card-body">
                <div class="erp-notice">BookingService sẽ chặn booking nếu khung giờ mới trùng lịch bảo trì `scheduled` hoặc `doing`.</div>
            </div>
        </section>
    </aside>
</div>
<?= $this->endSection() ?>
