<div class="erp-info-list">
    <div class="erp-info-row"><span>Mã booking</span><strong><?= esc($booking->booking_code) ?></strong></div>
    <div class="erp-info-row"><span>Khách hàng</span><strong><?= esc($booking->customer_name) ?></strong></div>
    <div class="erp-info-row"><span>SĐT</span><strong><?= esc($booking->customer_phone) ?></strong></div>
    <div class="erp-info-row"><span>Ngày giờ</span><strong><?= esc($booking->booking_date) ?> <?= esc(substr($booking->start_time, 0, 5)) ?>-<?= esc(substr($booking->end_time, 0, 5)) ?></strong></div>
    <div class="erp-info-row"><span>Tổng tiền</span><strong><?= format_money($booking->total_amount) ?></strong></div>
    <div class="erp-info-row"><span>Booking</span><?= renderStatusBadge($booking->status, 'booking') ?></div>
    <div class="erp-info-row"><span>Thanh toán</span><?= renderStatusBadge($booking->payment_status, 'payment') ?></div>
</div>
<div class="erp-section-title mt-3"><h3>Sân đã chọn</h3></div>
<div class="erp-calendar-strip">
    <?php foreach ($items as $item): ?>
        <div class="erp-calendar-item">
            <strong><?= esc($item->court_code ?? ('#' . $item->court_id)) ?></strong>
            <div><strong><?= esc($item->court_name_vi ?? $item->court_name_en ?? '') ?></strong><div class="erp-muted"><?= format_money($item->price) ?></div></div>
            <?= renderStatusBadge($item->status === 'active' ? 'success' : 'neutral') ?>
        </div>
    <?php endforeach; ?>
</div>
<div class="d-grid gap-2 mt-3">
    <a class="erp-btn erp-btn-primary" href="/admin/bookings/show/<?= $booking->id ?>">Mở chi tiết</a>
</div>
