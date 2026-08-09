<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-end gap-2 mb-3">
    <form method="post" action="/admin/players/profile/<?= $player->id ?>/check-in">
        <button class="btn btn-outline-primary" type="submit"><i class="bi bi-qr-code-scan"></i> Check In</button>
    </form>
    <a href="/admin/players/edit/<?= $player->id ?>" class="btn btn-outline-secondary"><i class="bi bi-pencil"></i> Edit</a>
    <a href="/admin/players/match-history/<?= $player->id ?>" class="btn btn-outline-success"><i class="bi bi-controller"></i> Matches</a>
    <a href="/admin/players/wallet/<?= $player->id ?>" class="btn btn-outline-warning"><i class="bi bi-wallet2"></i> Wallet</a>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center">
                <img src="<?= $player->getAvatarUrl() ?>" class="rounded-circle mb-3" width="112" height="112" alt="">
                <h4><?= esc($player->full_name) ?></h4>
                <div class="text-muted"><code><?= esc($player->player_code) ?></code></div>
                <div class="mt-2"><?= $player->getLevelBadge() ?> <?= $player->getStatusBadge() ?></div>
                <hr>
                <div class="row text-center">
                    <div class="col-4"><div class="fw-semibold"><?= (int) ($player->rating->rating ?? $player->rating_score ?? 1000) ?></div><small class="text-muted">ELO</small></div>
                    <div class="col-4"><div class="fw-semibold"><?= (int) ($player->checkin_streak ?? 0) ?></div><small class="text-muted">Streak</small></div>
                    <div class="col-4"><div class="fw-semibold"><?= (int) ($player->mvp_count ?? 0) ?></div><small class="text-muted">MVP</small></div>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header fw-semibold">Badges</div>
            <div class="card-body">
                <?php foreach (($player->badges ?? []) as $badge): ?>
                    <span class="badge bg-<?= $badge->rarity === 'epic' ? 'primary' : ($badge->rarity === 'rare' ? 'info' : ($badge->rarity === 'legendary' ? 'warning' : 'secondary')) ?> me-1 mb-1">
                        <i class="bi <?= esc($badge->icon ?? 'bi-award') ?>"></i> <?= esc($badge->name) ?>
                    </span>
                <?php endforeach; ?>
                <?php if (empty($player->badges)): ?>
                    <div class="text-muted"><?= lang('App.no_data') ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header fw-semibold">Achievements</div>
            <div class="card-body">
                <?php foreach (($player->achievements ?? []) as $achievement): ?>
                    <div class="d-flex justify-content-between align-items-start border-bottom py-2">
                        <div>
                            <div class="fw-semibold"><?= esc($achievement->name) ?></div>
                            <small class="text-muted"><?= esc($achievement->description ?? '') ?></small>
                        </div>
                        <span class="badge bg-success"><?= (int) $achievement->points ?> pts</span>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($player->achievements)): ?>
                    <div class="text-muted"><?= lang('App.no_data') ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="row g-3 mb-3">
            <?php $stats = $player->statistics; ?>
            <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">Matches</small><div class="fs-4 fw-semibold"><?= (int) ($stats->total_matches ?? 0) ?></div></div></div></div>
            <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">Wins</small><div class="fs-4 fw-semibold text-success"><?= (int) ($stats->total_wins ?? 0) ?></div></div></div></div>
            <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">Win rate</small><div class="fs-4 fw-semibold"><?= number_format((float) ($stats->win_rate ?? 0), 1) ?>%</div></div></div></div>
            <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted">Bookings</small><div class="fs-4 fw-semibold"><?= (int) ($stats->total_bookings ?? 0) ?></div></div></div></div>
        </div>

        <div class="card mb-3">
            <div class="card-header fw-semibold">Recent Match History</div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Date</th><th>Opponent</th><th>Result</th><th>Score</th><th>ELO</th></tr></thead>
                    <tbody>
                    <?php foreach (($player->match_history ?? []) as $match): ?>
                        <tr>
                            <td><?= format_datetime($match->match_date) ?></td>
                            <td><?= esc($match->opponent_name ?? '-') ?></td>
                            <td><span class="badge bg-<?= $match->result === 'win' ? 'success' : ($match->result === 'loss' ? 'danger' : 'secondary') ?>"><?= esc($match->result) ?></span></td>
                            <td><?= esc($match->score ?? '-') ?></td>
                            <td><?= (int) $match->rating_after ?> <small class="<?= $match->rating_delta >= 0 ? 'text-success' : 'text-danger' ?>"><?= $match->rating_delta >= 0 ? '+' : '' ?><?= (int) $match->rating_delta ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($player->match_history)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4"><?= lang('App.no_data') ?></td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header fw-semibold">Recent Booking History</div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Code</th><th>Date</th><th>Status</th><th>Total</th></tr></thead>
                    <tbody>
                    <?php foreach (($bookings ?? []) as $booking): ?>
                        <tr>
                            <td><code><?= esc($booking->booking_code ?? $booking->id) ?></code></td>
                            <td><?= esc($booking->booking_date ?? '') ?></td>
                            <td><?= esc($booking->status ?? '') ?></td>
                            <td><?= number_format((float) ($booking->total_amount ?? 0), 0, ',', '.') ?>đ</td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($bookings)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4"><?= lang('App.no_data') ?></td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
