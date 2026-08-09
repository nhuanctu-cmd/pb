<?= $this->extend('player/layouts/main') ?>

<?= $this->section('title') ?><?= lang('App.my_bookings') ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><?= lang('App.my_bookings') ?></h1>
        <a href="/player/bookings/create" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> <?= lang('App.new_booking') ?>
        </a>
    </div>

    <!-- Status filter -->
    <div class="d-flex gap-2 mb-3 overflow-auto">
        <a href="/player/bookings" class="btn btn-sm <?= !$status ? 'btn-dark' : 'btn-outline-secondary' ?>"><?= lang('App.all') ?></a>
        <a href="/player/bookings?status=pending" class="btn btn-sm <?= $status === 'pending' ? 'btn-warning' : 'btn-outline-secondary' ?>"><?= lang('App.status_pending') ?></a>
        <a href="/player/bookings?status=reserved" class="btn btn-sm <?= $status === 'reserved' ? 'btn-info' : 'btn-outline-secondary' ?>"><?= lang('App.status_reserved') ?></a>
        <a href="/player/bookings?status=paid" class="btn btn-sm <?= $status === 'paid' ? 'btn-primary' : 'btn-outline-secondary' ?>"><?= lang('App.status_paid') ?></a>
        <a href="/player/bookings?status=checked_in" class="btn btn-sm <?= $status === 'checked_in' ? 'btn-success' : 'btn-outline-secondary' ?>"><?= lang('App.status_checked_in') ?></a>
        <a href="/player/bookings?status=completed" class="btn btn-sm <?= $status === 'completed' ? 'btn-secondary' : 'btn-outline-secondary' ?>"><?= lang('App.status_completed') ?></a>
        <a href="/player/bookings?status=cancelled" class="btn btn-sm <?= $status === 'cancelled' ? 'btn-danger' : 'btn-outline-secondary' ?>"><?= lang('App.status_cancelled') ?></a>
    </div>

    <?php if (empty($bookings)): ?>
    <div class="text-center py-5">
        <i class="bi bi-calendar-x" style="font-size: 3rem; color: #ccc;"></i>
        <p class="text-muted mt-3"><?= lang('App.no_data') ?></p>
        <a href="/player/bookings/create" class="btn btn-primary"><?= lang('App.new_booking') ?></a>
    </div>
    <?php else: ?>
    <div class="list-group">
        <?php foreach ($bookings as $booking): ?>
        <a href="/player/bookings/detail/<?= $booking->id ?>" class="list-group-item list-group-item-action">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong><?= esc($booking->customer_name) ?></strong>
                    <br>
                    <small class="text-muted">
                        <code><?= esc($booking->booking_code) ?></code> |
                        <?= $booking->booking_date ?> |
                        <?= $booking->getTimeRange() ?>
                    </small>
                </div>
                <div class="text-end">
                    <?= $booking->getStatusBadge() ?>
                    <br>
                    <small class="text-muted"><?= $booking->getTotalAmountFormatted() ?></small>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
