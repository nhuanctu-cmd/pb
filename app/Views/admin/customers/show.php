<?= $this->extend('layouts/admin_master') ?>

<?= $this->section('content') ?>
<?php
$statusMap = [
    'active' => ['type' => 'success', 'label' => 'Hoạt động'],
    'inactive' => ['type' => 'warning', 'label' => 'Không hoạt động'],
    'merged' => ['type' => 'neutral', 'label' => 'Merged'],
];
?>
<div class="erp-action-bar">
    <div class="d-flex gap-2 align-items-center flex-wrap">
        <a href="/admin/customers" class="erp-btn"><i class="bi bi-arrow-left"></i> Quay lại CRM</a>
        <span class="erp-chip erp-status-neutral">Customer #<?= (int) $customer->id ?></span>
    </div>
    <div class="d-flex gap-2">
        <a href="/admin/customers/create-booking/<?= (int) $customer->id ?>" class="erp-btn erp-btn-primary"><i class="bi bi-calendar-plus"></i> Tạo booking</a>
        <?php if (! empty($customer->player_id)): ?>
            <a href="/admin/players/profile/<?= (int) $customer->player_id ?>" class="erp-btn"><i class="bi bi-person"></i> Player #<?= (int) $customer->player_id ?></a>
        <?php endif; ?>
    </div>
</div>

<section class="erp-card mb-3">
    <div class="erp-card-body erp-entity-header">
        <div class="erp-entity-main">
            <div class="erp-avatar" style="width:56px;height:56px;font-size:21px"><?= esc(strtoupper(substr($customer->full_name, 0, 1))) ?></div>
            <div>
                <h2 class="erp-entity-title"><?= esc($customer->full_name) ?></h2>
                <div class="erp-muted"><?= esc($customer->phone ?: 'Không có SĐT') ?> <?= $customer->email ? '· ' . esc($customer->email) : '' ?></div>
                <div class="erp-muted mt-1"><?= esc($sourceOptions[0] ?? 'booking') ?> · Mục tiêu CRM</div>
            </div>
        </div>
        <div class="erp-notice">
            <?= renderStatusBadge($customer->status, 'general') ?>
            <div class="mt-2"><strong><?= esc($statusMap[$customer->status ?? 'inactive']['label']) ?></strong> · tạo lúc <?= esc(format_datetime($customer->created_at ?? '')) ?></div>
        </div>
    </div>
    <div class="erp-tabs px-3" data-erp-tabs>
        <a class="erp-tab active" href="#tabOverview" data-erp-tab>Khách hàng</a>
        <a class="erp-tab" href="#tabBookings" data-erp-tab>Bookings</a>
        <a class="erp-tab" href="#tabTimeline" data-erp-tab>Timeline</a>
        <a class="erp-tab" href="#tabGovernance" data-erp-tab>Gỡ nhãn</a>
    </div>
</section>

<div id="tabOverview" data-erp-panel>
    <?= view('layouts/partials/stat_cards', ['stats' => [
        ['label' => 'Booking', 'value' => (string) $bookingStats['total'], 'trend' => 'Tổng số booking', 'icon' => 'bi-calendar-check'],
        ['label' => 'Đã hoàn tất', 'value' => (string) $bookingStats['completed'], 'trend' => 'Tỉ lệ hoàn thành', 'icon' => 'bi-check2-circle'],
        ['label' => 'Đã hủy/No-show', 'value' => (string) ($bookingStats['cancelled'] + $bookingStats['no_show']), 'trend' => 'Mất mát cơ hội', 'icon' => 'bi-x-circle'],
        ['label' => 'Đã thanh toán', 'value' => (string) $bookingStats['paid'], 'trend' => 'Booking đã thu tiền', 'icon' => 'bi-wallet2'],
        ['label' => 'Lần ghé gần nhất', 'value' => esc($customer->last_seen_at ?: '—'), 'trend' => 'Theo hoạt động gần nhất', 'icon' => 'bi-clock-history'],
        ['label' => 'Tổng chi', 'value' => number_format((float) $customer->total_spend, 0, ',', '.') . 'đ', 'trend' => 'Spend', 'icon' => 'bi-cash'],
    ]]) ?>

    <div class="erp-grid-dashboard">
        <div class="erp-dashboard-stack">
            <section class="erp-card">
                <div class="erp-card-header"><strong>Hồ sơ & nguồn</strong></div>
                <div class="erp-card-body erp-info-list">
                    <div class="erp-info-row"><span>Họ tên</span><strong><?= esc($customer->full_name) ?></strong></div>
                    <div class="erp-info-row"><span>Liên hệ</span><strong><?= esc($customer->phone ?: '—') ?> · <?= esc($customer->email ?: '—') ?></strong></div>
                    <div class="erp-info-row"><span>Nguồn</span><strong><?= esc($customer->source ?: 'booking') ?></strong></div>
                    <div class="erp-info-row"><span>Player liên kết</span><strong><?= $customer->player_id ? '<a href="/admin/players/profile/' . (int) $customer->player_id . '">#' . (int) $customer->player_id . '</a>' : 'Chưa liên kết' ?></strong></div>
                    <div class="erp-info-row"><span>Booking đã tạo</span><strong><?= (int) $customer->total_bookings ?></strong></div>
                    <div class="erp-info-row"><span>Hoàn tất</span><strong><?= (int) $customer->completed_bookings ?></strong></div>
                    <div class="erp-info-row"><span>No-show</span><strong><?= (int) $customer->no_show_count ?></strong></div>
                    <div class="erp-info-row"><span>Lần đặt cuối</span><strong><?= esc($customer->last_booking_at ?: '-') ?></strong></div>
                </div>
            </section>

            <section class="erp-card">
                <div class="erp-card-header"><strong>Cập nhật trạng thái</strong></div>
                <form method="post" action="/admin/customers/status/<?= (int) $customer->id ?>" class="erp-form p-3">
                    <?= csrf_field() ?>
                    <div class="erp-form-grid">
                        <div class="erp-field">
                            <label>Trạng thái</label>
                            <select name="status" class="erp-select">
                                <?php foreach (['active' => 'Hoạt động', 'inactive' => 'Không hoạt động', 'merged' => 'Merged'] as $value => $label): ?>
                                    <option value="<?= esc($value) ?>"<?= ((string) $customer->status === $value) ? ' selected' : '' ?>><?= esc($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="erp-field"><label>&nbsp;</label><button class="erp-btn erp-btn-primary"><i class="bi bi-save"></i> Cập nhật</button></div>
                    </div>
                </form>
            </section>
        </div>

        <aside class="erp-dashboard-stack">
            <section class="erp-card">
                <div class="erp-card-header"><strong>Nhãn / Tag</strong></div>
                <div class="erp-card-body">
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <?php foreach (($tags ?? []) as $tag): ?>
                            <span class="erp-chip erp-status-neutral" style="background:<?= esc($tag->color ?: '#e8f0ff') ?>; border-color: <?= esc($tag->color ?: '#cfdcff') ?>; color:#1f2937">
                                <?= esc($tag->name ?? $tag->code) ?>
                                <form method="post" action="/admin/customers/tags/<?= (int) $customer->id ?>/remove/<?= (int) $tag->id ?>" class="d-inline-block ms-1">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-link p-0" type="submit" title="Bỏ tag" data-confirm="Xóa nhãn này?"><i class="bi bi-x-lg"></i></button>
                                </form>
                            </span>
                        <?php endforeach; ?>
                        <?php if (empty($tags)): ?>
                            <span class="erp-muted">Chưa có nhãn</span>
                        <?php endif; ?>
                    </div>

                    <form method="post" action="/admin/customers/tags/<?= (int) $customer->id ?>" class="erp-form">
                        <?= csrf_field() ?>
                        <div class="erp-form-grid-3">
                            <div class="erp-field">
                                <label>Nhãn có sẵn</label>
                                <select name="tag_ids[]" class="erp-select" multiple size="4">
                                    <?php foreach (($allTags ?? []) as $tag): ?>
                                        <option value="<?= (int) $tag->id ?>"<?= in_array($tag->id, array_map(fn($t) => (int) $t->id, $tags ?? []), true) ? ' selected' : '' ?>><?= esc($tag->name) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="erp-hint">Giữ Ctrl/Shift để chọn nhiều nhãn.</div>
                            </div>
                            <div class="erp-field">
                                <label>Tạo nhãn mới</label>
                                <input name="new_tag" class="erp-control" placeholder="Nhập tên nhãn mới">
                            </div>
                            <div class="erp-field"><label>&nbsp;</label><button class="erp-btn erp-btn-primary"><i class="bi bi-tags"></i> Lưu nhãn</button></div>
                        </div>
                    </form>
                </div>
            </section>

            <section class="erp-card">
                <div class="erp-card-header"><strong>Ghi chú CRM</strong></div>
                <form method="post" action="/admin/customers/note/<?= (int) $customer->id ?>" class="erp-form p-3">
                    <?= csrf_field() ?>
                    <div class="erp-field"><label>Nội dung ghi chú</label><textarea name="note" class="erp-textarea" rows="3" placeholder="Ghi lại thông tin khách quan trọng, ưu tiên chăm sóc, ghi chú lịch sử..." required></textarea></div>
                    <button class="erp-btn erp-btn-primary"><i class="bi bi-journal-text"></i> Lưu ghi chú</button>
                </form>
            </section>
        </aside>
    </div>
</div>

<div id="tabBookings" data-erp-panel class="d-none">
    <section class="erp-card">
        <div class="erp-card-header"><strong>Lịch sử booking</strong></div>
        <?php if (empty($bookings)): ?>
            <div class="erp-empty">Chưa có booking.</div>
        <?php else: ?>
            <div class="erp-table-wrap">
                <table class="erp-table">
                    <thead><tr><th>Mã</th><th>Ngày giờ</th><th>Sân</th><th>Trạng thái</th><th>Thanh toán</th><th>Số tiền</th><th class="col-actions">Action</th></tr></thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><strong><?= esc($booking->booking_code) ?></strong></td>
                                <td><?= esc($booking->booking_date) ?> <?= esc(substr((string) $booking->start_time, 0, 5)) ?>-<?= esc(substr((string) $booking->end_time, 0, 5)) ?></td>
                                <td><?= esc($booking->court_codes ?: '—') ?></td>
                                <td><?= renderStatusBadge($booking->status, 'booking') ?></td>
                                <td><?= renderStatusBadge($booking->payment_status, 'payment') ?></td>
                                <td><strong><?= number_format((float) ($booking->total_amount ?? 0), 0, ',', '.') ?>đ</strong></td>
                                <td class="col-actions"><a class="erp-btn erp-btn-icon" href="/admin/bookings/show/<?= (int) $booking->id ?>" title="Chi tiết"><i class="bi bi-eye"></i></a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>

<div id="tabTimeline" data-erp-panel class="d-none">
    <section class="erp-card">
        <div class="erp-card-header"><strong>Timeline CRM</strong></div>
        <div class="erp-card-body erp-timeline">
            <?php if (!$timeline): ?>
                <div class="erp-empty">Chưa có hoạt động.</div>
            <?php else: ?>
                <?php foreach ($timeline as $event): ?>
                    <?php
                        $tone = 'info';
                        if (str_contains((string) $event->event_type, 'no_show') || str_contains((string) $event->event_type, 'canceled')) $tone = 'danger';
                        if ($event->event_type === 'booking_backfilled') $tone = 'warning';
                        if ($event->event_type === 'booking_created') $tone = 'success';
                    ?>
                    <div class="erp-timeline-item">
                        <div class="erp-timeline-dot erp-timeline-dot-<?= esc($tone) ?>"></div>
                        <div>
                            <strong><?= esc($event->title) ?></strong>
                            <div class="erp-muted"><?= esc($event->event_type) ?> · <?= esc($event->created_at) ?></div>
                            <?php if (! empty($event->description)): ?><div><?= esc($event->description) ?></div><?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</div>

<div id="tabGovernance" data-erp-panel class="d-none">
    <section class="erp-card">
        <div class="erp-card-header"><strong>Gỡ nhãn nhanh</strong></div>
        <div class="erp-card-body">
            <p class="erp-muted mb-3">Mục tiêu: CRM chuẩn là luôn giữ hồ sơ sạch, nên nếu nhãn bị sai có thể loại nhanh theo hành vi.</p>
            <div class="erp-quick-grid">
                <a class="erp-quick-tile" href="/admin/customers/tags/<?= (int) $customer->id ?>" onclick="alert('Chọn nhãn bên tab này bằng form ở cột phải.'); return false;"><i class="erp-quick-icon erp-quick-icon-neutral bi bi-arrow-clockwise"></i> Sync nhãn</a>
                <a class="erp-quick-tile" href="/admin/customers/show/<?= (int) $customer->id ?>"><i class="erp-quick-icon erp-quick-icon-warning bi bi-shield-check"></i> Refresh dữ liệu</a>
            </div>
        </div>
    </section>
</div>
<?= $this->endSection() ?>
