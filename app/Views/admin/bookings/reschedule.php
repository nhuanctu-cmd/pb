<?= $this->extend('layouts/admin_master') ?>

<?= $this->section('content') ?>
<div class="erp-grid-2">
    <form method="post" action="/admin/bookings/reschedule/<?= $booking->id ?>" class="erp-form">
        <?= csrf_field() ?>
        <section class="erp-form-section">
            <div class="erp-form-section-header">
                <h2>Đổi lịch booking</h2>
                <div class="erp-muted"><?= esc($booking->booking_code) ?> · hiện tại <?= esc($booking->booking_date) ?> <?= esc(substr($booking->start_time, 0, 5)) ?>-<?= esc(substr($booking->end_time, 0, 5)) ?></div>
            </div>
            <div class="erp-form-section-body erp-form-grid">
                <div class="erp-field">
                    <label>Ngày chơi <span class="erp-required">*</span></label>
                    <input type="date" name="booking_date" id="previewDate" class="erp-control" required value="<?= esc($booking->booking_date) ?>">
                </div>
                <div class="erp-field">
                    <label>Bắt đầu <span class="erp-required">*</span></label>
                    <input type="time" name="start_time" id="previewStart" class="erp-control" required value="<?= esc(substr($booking->start_time, 0, 5)) ?>">
                </div>
                <div class="erp-field">
                    <label>Kết thúc <span class="erp-required">*</span></label>
                    <input type="time" name="end_time" id="previewEnd" class="erp-control" required value="<?= esc(substr($booking->end_time, 0, 5)) ?>">
                </div>
            </div>
            <div class="erp-form-section-body pt-0">
                <div id="reschedulePreview" class="erp-notice">Đổi ngày/giờ để preview tình trạng sân và giá mới.</div>
            </div>
            <div class="erp-sticky-save">
                <a href="/admin/bookings/show/<?= $booking->id ?>" class="erp-btn">Hủy</a>
                <button type="button" class="erp-btn" onclick="previewReschedule()"><i class="bi bi-search"></i> Preview</button>
                <button class="erp-btn erp-btn-primary" data-confirm="Xác nhận đổi lịch booking này?"><i class="bi bi-calendar-check"></i> Lưu đổi lịch</button>
            </div>
        </section>
    </form>

    <aside class="erp-dashboard-stack">
        <section class="erp-card">
            <div class="erp-card-header"><strong>Sân trong booking</strong><?= renderStatusBadge($booking->status, 'booking') ?></div>
            <div class="erp-card-body erp-info-list">
                <?php foreach ($items as $item): ?>
                    <div class="erp-info-row">
                        <span><?= esc(substr($item->start_time, 0, 5)) ?>-<?= esc(substr($item->end_time, 0, 5)) ?></span>
                        <strong><?= esc($item->court_code) ?> - <?= esc($item->court_name_vi ?? $item->court_name_en) ?></strong>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($items)): ?><div class="erp-empty py-3">Booking chưa có sân.</div><?php endif; ?>
            </div>
        </section>
        <section class="erp-card">
            <div class="erp-card-header"><strong>Ràng buộc đổi lịch</strong></div>
            <div class="erp-card-body">
                <div class="erp-notice mb-2">Preview dùng endpoint `reschedule-preview` để kiểm tra trùng sân và tính lại giá.</div>
                <div class="erp-notice">Khi lưu, service vẫn kiểm tra lại availability trước khi cập nhật.</div>
            </div>
        </section>
    </aside>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
function previewReschedule() {
    const box = document.getElementById('reschedulePreview');
    const params = new URLSearchParams({
        date: document.getElementById('previewDate').value,
        start_time: document.getElementById('previewStart').value,
        end_time: document.getElementById('previewEnd').value
    });
    box.innerHTML = '<div class="erp-skeleton mb-2"></div><div class="erp-skeleton"></div>';
    fetch('/admin/ops/reschedule-preview/<?= $booking->id ?>?' + params)
        .then(response => response.json())
        .then(data => {
            if (!data.success) { box.textContent = data.message || 'Không preview được.'; return; }
            box.innerHTML = '<div class="erp-info-list">' + data.data.map(item => `<div class="erp-info-row"><span>Sân #${item.court_id}</span><strong>${item.available ? 'Có thể đổi' : 'Không trống'} · ${item.formatted_price}</strong></div>`).join('') + '</div>';
        });
}
</script>
<?= $this->endSection() ?>
