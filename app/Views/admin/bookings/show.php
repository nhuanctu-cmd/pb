<?= $this->extend('layouts/admin_master') ?>

<?= $this->section('content') ?>
<section class="erp-card mb-3">
    <div class="erp-card-body erp-entity-header">
        <div class="erp-entity-main">
            <div class="erp-avatar" style="width:58px;height:58px"><i class="bi bi-calendar-check"></i></div>
            <div>
                <h2 class="erp-entity-title"><?= esc($booking->booking_code) ?></h2>
                <div class="erp-muted"><?= esc($booking->customer_name) ?> · <?= esc($booking->booking_date) ?> · <?= esc($booking->getTimeRange()) ?></div>
                <div class="d-flex gap-2 mt-2"><?= renderStatusBadge($booking->status, 'booking') ?><?= renderStatusBadge($booking->payment_status, 'payment') ?></div>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap justify-content-end">
            <a href="/admin/bookings" class="erp-btn"><i class="bi bi-arrow-left"></i> Danh sách</a>
            <?php if (! in_array($booking->status, ['completed', 'cancelled', 'refunded', 'no_show'], true)): ?>
                <a href="/admin/bookings/reschedule/<?= $booking->id ?>" class="erp-btn"><i class="bi bi-calendar-event"></i> Đổi lịch</a>
            <?php endif; ?>
            <?php if (in_array($booking->status, ['reserved', 'paid'], true)): ?>
                <form method="post" action="/admin/bookings/check-in/<?= $booking->id ?>"><?= csrf_field() ?><button class="erp-btn erp-btn-primary" data-confirm="Check-in booking này?"><i class="bi bi-qr-code-scan"></i> Check-in</button></form>
            <?php endif; ?>
        </div>
    </div>
</section>

<div class="erp-grid-dashboard">
    <div class="erp-dashboard-stack">
        <section class="erp-card">
            <div class="erp-card-header"><strong>Thông tin khách</strong></div>
            <div class="erp-card-body erp-info-list">
                <div class="erp-info-row"><span>Khách hàng</span><strong><?= esc($booking->customer_name) ?></strong></div>
                <div class="erp-info-row"><span>Số điện thoại</span><strong><?= esc($booking->customer_phone) ?></strong></div>
                <div class="erp-info-row"><span>Email</span><strong><?= esc($booking->customer_email ?: '-') ?></strong></div>
                <div class="erp-info-row"><span>Nguồn tạo</span><strong><?= esc($booking->source) ?></strong></div>
            </div>
        </section>

        <section class="erp-card">
            <div class="erp-card-header"><strong>Thông tin sân</strong></div>
            <div class="erp-table-wrap border-0">
                <table class="erp-table">
                    <thead><tr><th>Sân</th><th>Giờ</th><th>Giá</th><th>Trạng thái</th></tr></thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><strong><?= esc($item->court_code ?? ('#' . $item->court_id)) ?></strong><div class="erp-muted"><?= esc($item->court_name_vi ?? $item->court_name_en ?? '') ?></div></td>
                            <td><?= esc(substr($item->start_time, 0, 5)) ?> - <?= esc(substr($item->end_time, 0, 5)) ?></td>
                            <td><strong><?= format_money($item->price) ?></strong></td>
                            <td><?= renderStatusBadge($item->status === 'active' ? 'success' : 'neutral') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="erp-card">
            <div class="erp-card-header"><strong>Log thao tác</strong></div>
            <div class="erp-card-body erp-timeline">
                <?php foreach ($logs as $log): ?>
                    <?= renderTimelineItem($log->action, (string) $log->created_at, 'info', $log->message ?? null) ?>
                <?php endforeach; ?>
                <?php if (empty($logs)): ?><div class="erp-empty py-3">Chưa có log thao tác.</div><?php endif; ?>
            </div>
        </section>
    </div>

    <aside class="erp-dashboard-stack">
        <section class="erp-card">
            <div class="erp-card-header"><strong>Payment summary</strong></div>
            <div class="erp-card-body erp-info-list">
                <div class="erp-info-row"><span>Tổng tiền</span><strong><?= format_money($booking->total_amount) ?></strong></div>
                <div class="erp-info-row"><span>Tiền cọc</span><strong><?= format_money($booking->deposit_amount) ?></strong></div>
                <div class="erp-info-row"><span>Đã thanh toán</span><strong><?= format_money($booking->paid_amount) ?></strong></div>
                <div class="erp-info-row"><span>Còn lại</span><strong><?= format_money(max(0, $booking->total_amount - $booking->paid_amount)) ?></strong></div>
            </div>
        </section>

        <section class="erp-card">
            <div class="erp-card-header"><strong>QR check-in</strong></div>
            <div class="erp-card-body text-center">
                <div class="erp-empty-icon mx-auto"><i class="bi bi-qr-code"></i></div>
                <code class="d-block text-break"><?= esc($qrCode->qr_token ?? 'Chưa có QR token') ?></code>
                <div class="erp-muted mt-2">Có thể tích hợp thư viện QR để render mã quét tại đây.</div>
            </div>
        </section>

        <section class="erp-card">
            <div class="erp-card-header"><strong>Timeline trạng thái</strong></div>
            <div class="erp-card-body erp-timeline">
                <?= renderTimelineItem('Tạo booking', (string) $booking->created_at, 'success') ?>
                <?php if ($booking->checked_in_at): ?><?= renderTimelineItem('Khách đã check-in', (string) $booking->checked_in_at, 'success') ?><?php endif; ?>
                <?php if ($booking->cancelled_at): ?><?= renderTimelineItem('Booking đã hủy', (string) $booking->cancelled_at, 'danger', $booking->cancelled_reason) ?><?php endif; ?>
                <?php if ($booking->completed_at): ?><?= renderTimelineItem('Hoàn thành', (string) $booking->completed_at, 'dark') ?><?php endif; ?>
            </div>
        </section>

        <?php if (! in_array($booking->status, ['completed', 'cancelled', 'refunded', 'no_show'], true)): ?>
            <section class="erp-card">
                <div class="erp-card-header"><strong>Hủy booking</strong></div>
                <div class="erp-card-body">
                    <form method="post" action="/admin/bookings/cancel/<?= $booking->id ?>">
                        <?= csrf_field() ?>
                        <textarea name="reason" class="erp-textarea mb-2" placeholder="Lý do hủy"></textarea>
                        <button class="erp-btn erp-btn-danger w-100" data-confirm="Xác nhận hủy booking?">Hủy booking</button>
                    </form>
                </div>
            </section>
        <?php endif; ?>
    </aside>
</div>
<?= $this->endSection() ?>
