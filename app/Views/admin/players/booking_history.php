<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3"><?= $pageTitle ?></h1>
    <a href="/admin/players/edit/<?= $player->id ?>" class="btn btn-info"><i class="bi bi-person"></i> <?= lang('App.player_profile') ?></a>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex align-items-center mb-3">
            <img src="<?= $player->getAvatarUrl() ?>" class="rounded-circle me-3" width="48" height="48" alt="">
            <div>
                <h5 class="mb-0"><?= esc($player->full_name) ?></h5>
                <small class="text-muted"><code><?= $player->player_code ?></code> | <?= $player->phone ?></small>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th><?= lang('App.booking_code') ?></th>
                        <th><?= lang('App.booking_date') ?></th>
                        <th><?= lang('App.start_time') ?></th>
                        <th><?= lang('App.end_time') ?></th>
                        <th><?= lang('App.total_amount') ?></th>
                        <th><?= lang('App.status') ?></th>
                        <th><?= lang('App.created_at') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bookings)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted"><?= lang('App.no_data') ?></td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($bookings as $booking): ?>
                    <tr>
                        <td><code><?= esc($booking->booking_code) ?></code></td>
                        <td><?= $booking->booking_date ?></td>
                        <td><?= $booking->start_time ?></td>
                        <td><?= $booking->end_time ?></td>
                        <td><?= number_format($booking->total_amount, 0, ',', '.') ?>đ</td>
                        <td>
                            <?php
                            $statusBadges = [
                                'pending' => 'warning', 'reserved' => 'info', 'paid' => 'success',
                                'checked_in' => 'primary', 'completed' => 'success',
                                'cancelled' => 'danger', 'refunded' => 'secondary', 'no_show' => 'dark'
                            ];
                            $badgeColor = $statusBadges[$booking->status] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?= $badgeColor ?>"><?= lang('App.status_' . $booking->status) ?></span>
                        </td>
                        <td><?= $booking->created_at ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (isset($pager)): ?>
        <div class="d-flex justify-content-center">
            <?= $pager->links() ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
