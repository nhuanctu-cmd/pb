<?= $this->extend('layouts/admin_master') ?>

<?= $this->section('content') ?>
<div class="erp-action-bar">
    <div class="d-flex gap-2 flex-wrap">
        <a href="/admin/bookings" class="erp-btn"><i class="bi bi-list"></i> Danh sách</a>
        <a href="/admin/bookings/create" class="erp-btn erp-btn-primary"><i class="bi bi-plus-lg"></i> Tạo booking</a>
    </div>
    <a href="/admin/bookings/calendar?branch_id=<?= $branchId ?>&date_from=<?= date('Y-m-d') ?>&date_to=<?= date('Y-m-d', strtotime('+6 days')) ?>" class="erp-btn"><i class="bi bi-calendar-week"></i> Tuần này</a>
</div>

<form method="get">
    <?= view('layouts/partials/filter_bar', ['left' => '
        <select name="branch_id" class="erp-select" style="width:220px" onchange="this.form.submit()">' .
            implode('', array_map(fn ($branch) => '<option value="' . $branch->id . '" ' . (($branchId ?? '') == $branch->id ? 'selected' : '') . '>' . esc($branch->getName()) . '</option>', $branches ?? [])) .
        '</select>
        <input type="date" name="date_from" class="erp-control" style="width:170px" value="' . esc($dateFrom) . '" onchange="this.form.submit()">
        <input type="date" name="date_to" class="erp-control" style="width:170px" value="' . esc($dateTo) . '" onchange="this.form.submit()">
    ']) ?>
</form>

<section class="erp-card">
    <div class="erp-card-header"><strong>Lịch booking theo ngày</strong><span class="erp-muted"><?= esc($dateFrom) ?> - <?= esc($dateTo) ?></span></div>
    <div class="erp-card-body">
        <?php if (empty($events)): ?>
            <div class="erp-empty">
                <div class="erp-empty-icon"><i class="bi bi-calendar-x"></i></div>
                Chưa có booking trong khoảng thời gian này.
            </div>
        <?php else: ?>
            <?php
            $grouped = [];
            foreach ($events as $event) {
                $grouped[$event->booking_date][] = $event;
            }
            ?>
            <div class="erp-dashboard-stack">
                <?php foreach ($grouped as $date => $dayEvents): ?>
                    <section class="erp-card">
                        <div class="erp-card-header">
                            <strong><?= date('D, d/m/Y', strtotime($date)) ?></strong>
                            <?= $date === date('Y-m-d') ? '<span class="erp-chip erp-status-info">Hôm nay</span>' : '<span class="erp-muted">' . count($dayEvents) . ' booking</span>' ?>
                        </div>
                        <div class="erp-card-body erp-timeline">
                            <?php foreach ($dayEvents as $event): ?>
                                <div class="erp-timeline-item">
                                    <div><span class="erp-timeline-dot erp-timeline-dot-<?= in_array($event->status, ['checked_in', 'completed'], true) ? 'success' : ($event->status === 'cancelled' ? 'danger' : 'warning') ?>"></span></div>
                                    <div class="d-flex justify-content-between gap-2 flex-wrap">
                                        <div>
                                            <a href="/admin/bookings/show/<?= $event->id ?>" class="fw-bold text-decoration-none"><?= esc($event->customer_name) ?></a>
                                            <div class="erp-muted"><?= esc($event->booking_code) ?> · <?= esc(substr($event->start_time, 0, 5)) ?>-<?= esc(substr($event->end_time, 0, 5)) ?><?= $event->court_info ? ' · ' . esc($event->court_info) : '' ?></div>
                                        </div>
                                        <div class="d-flex gap-2 align-items-center">
                                            <?= renderStatusBadge($event->status, 'booking') ?>
                                            <?php if (in_array($event->status, ['reserved', 'paid'], true)): ?>
                                                <form method="post" action="/admin/bookings/check-in/<?= $event->id ?>"><?= csrf_field() ?><button type="submit" class="erp-btn erp-btn-icon" data-confirm="Check-in booking này?"><i class="bi bi-check-circle"></i></button></form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?= $this->endSection() ?>
