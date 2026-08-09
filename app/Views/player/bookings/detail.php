<?= $this->extend('player/layouts/main') ?>

<?= $this->section('title') ?><?= lang('App.booking_details') ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 mb-0"><?= lang('App.booking_details') ?></h1>
        <a href="/player/bookings" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> <?= lang('App.back') ?>
        </a>
    </div>

    <!-- Status Banner -->
    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body text-center">
            <div class="mb-2"><?= $booking->getStatusBadge() ?></div>
            <h5><code><?= esc($booking->booking_code) ?></code></h5>
            <p class="text-muted mb-0"><?= $booking->booking_date ?> | <?= $booking->getTimeRange() ?></p>
        </div>
    </div>

    <!-- Court Info -->
    <div class="card mb-3">
        <div class="card-header"><strong><?= lang('App.court_info') ?></strong></div>
        <div class="card-body p-0">
            <table class="table mb-0">
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= esc($item->court_name_vi ?? $item->court_name_en ?? $item->court_code) ?></td>
                    <td class="text-end"><?= number_format($item->price, 0, ',', '.') ?>₫</td>
                </tr>
                <?php endforeach; ?>
                <tr class="table-active">
                    <td><strong><?= lang('App.total') ?></strong></td>
                    <td class="text-end"><strong><?= $booking->getTotalAmountFormatted() ?></strong></td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Customer Info -->
    <div class="card mb-3">
        <div class="card-header"><strong><?= lang('App.customer_info') ?></strong></div>
        <div class="card-body">
            <p class="mb-1"><strong><?= lang('App.customer_name') ?>:</strong> <?= esc($booking->customer_name) ?></p>
            <p class="mb-1"><strong><?= lang('App.customer_phone') ?>:</strong> <?= esc($booking->customer_phone) ?></p>
            <?php if ($booking->customer_email): ?>
            <p class="mb-0"><strong><?= lang('App.customer_email') ?>:</strong> <?= esc($booking->customer_email) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Payment -->
    <div class="card mb-3">
        <div class="card-header"><strong><?= lang('App.payment') ?></strong></div>
        <div class="card-body">
            <table class="table table-sm mb-0">
                <tr>
                    <td><?= lang('App.total_amount') ?></td>
                    <td class="text-end"><?= $booking->getTotalAmountFormatted() ?></td>
                </tr>
                <tr>
                    <td><?= lang('App.deposit_amount') ?></td>
                    <td class="text-end"><?= $booking->getDepositAmountFormatted() ?></td>
                </tr>
                <tr>
                    <td><?= lang('App.paid_amount') ?></td>
                    <td class="text-end"><?= $booking->getPaidAmountFormatted() ?></td>
                </tr>
                <tr>
                    <td><?= lang('App.payment_status') ?></td>
                    <td class="text-end"><?= $booking->getPaymentBadge() ?></td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Actions -->
    <?php if (!in_array($booking->status, ['completed', 'cancelled', 'refunded', 'no_show'])): ?>
    <div class="d-grid gap-2">
        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal">
            <i class="bi bi-x-circle"></i> <?= lang('App.cancel_booking') ?>
        </button>
    </div>
    <?php endif; ?>

    <!-- History -->
    <?php if (!empty($logs)): ?>
    <div class="card mt-3">
        <div class="card-header"><strong><?= lang('App.history') ?></strong></div>
        <div class="card-body p-0">
            <?php foreach ($logs as $log): ?>
            <div class="list-group-item border-0 border-bottom">
                <small class="text-muted"><?= $log->created_at ?></small>
                <div><?= $log->action ?></div>
                <?php if ($log->message): ?>
                <small class="text-muted"><?= esc($log->message) ?></small>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Cancel Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="/player/bookings/cancel/<?= $booking->id ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><?= lang('App.cancel_booking') ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><?= lang('App.confirm_cancel') ?></p>
                    <div class="mb-3">
                        <label class="form-label"><?= lang('App.reason') ?></label>
                        <textarea name="reason" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= lang('App.cancel') ?></button>
                    <button type="submit" class="btn btn-danger"><?= lang('App.confirm') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
