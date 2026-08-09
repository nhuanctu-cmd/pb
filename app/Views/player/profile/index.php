<?= $this->extend('player/layouts/main') ?>

<?= $this->section('content') ?>
<div class="container py-4">
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <img src="<?= $player->getAvatarUrl() ?>" class="rounded-circle mb-3" width="120" height="120" alt="">
                    <h4><?= esc($player->full_name) ?></h4>
                    <p class="text-muted"><code><?= $player->player_code ?></code></p>
                    <?= $player->getLevelBadge() ?>
                    <hr>
                    <div class="d-flex justify-content-around">
                        <div class="text-center">
                            <h5 class="text-primary mb-0"><?= number_format($player->rating_score, 1) ?></h5>
                            <small class="text-muted"><?= lang('App.rating_score') ?></small>
                        </div>
                        <div class="text-center">
                            <h5 class="text-success mb-0"><?= $wallet ? number_format($wallet->balance, 0, ',', '.') : 0 ?>đ</h5>
                            <small class="text-muted"><?= lang('App.balance') ?></small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header"><h5><?= lang('App.membership') ?></h5></div>
                <div class="card-body">
                    <?php if ($membership): ?>
                    <span class="badge bg-success mb-2"><?= lang('App.membership_active') ?></span>
                    <p class="mb-1"><small><?= lang('App.end_date') ?>: <?= $membership->end_date ?></small></p>
                    <p><small><?= lang('App.remaining') ?>: <?= $membership->getRemainingDays() ?> <?= lang('App.days') ?></small></p>
                    <?php else: ?>
                    <p class="text-muted"><?= lang('App.no_membership') ?></p>
                    <a href="/player/profile/membership" class="btn btn-sm btn-primary"><?= lang('App.buy_membership') ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header"><h5><?= lang('App.personal_info') ?></h5></div>
                <div class="card-body">
                    <form method="post" action="/player/profile/update">
                        <div class="mb-3">
                            <label class="form-label"><?= lang('App.full_name') ?></label>
                            <input type="text" name="full_name" class="form-control" value="<?= esc($player->full_name) ?>" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?= lang('App.phone') ?></label>
                                <input type="text" name="phone" class="form-control" value="<?= esc($player->phone) ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?= lang('App.email') ?></label>
                                <input type="email" class="form-control" value="<?= esc($player->email) ?>" disabled>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?= lang('App.gender') ?></label>
                                <select name="gender" class="form-select">
                                    <option value="other" <?= $player->gender === 'other' ? 'selected' : '' ?>><?= lang('App.other') ?></option>
                                    <option value="male" <?= $player->gender === 'male' ? 'selected' : '' ?>><?= lang('App.male') ?></option>
                                    <option value="female" <?= $player->gender === 'female' ? 'selected' : '' ?>><?= lang('App.female') ?></option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?= lang('App.birthday') ?></label>
                                <input type="date" name="birthday" class="form-control" value="<?= $player->birthday ?>">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary"><?= lang('App.update') ?></button>
                    </form>
                </div>
            </div>

            <?php if ($stats): ?>
            <div class="card mt-3">
                <div class="card-header"><h5><?= lang('App.statistics') ?></h5></div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-3">
                            <h3 class="text-primary"><?= $stats->total_matches ?></h3>
                            <small class="text-muted"><?= lang('App.matches') ?></small>
                        </div>
                        <div class="col-3">
                            <h3 class="text-success"><?= $stats->total_wins ?></h3>
                            <small class="text-muted"><?= lang('App.wins') ?></small>
                        </div>
                        <div class="col-3">
                            <h3 class="text-danger"><?= $stats->total_losses ?></h3>
                            <small class="text-muted"><?= lang('App.losses') ?></small>
                        </div>
                        <div class="col-3">
                            <h3 class="text-info"><?= $stats->getWinRateFormatted() ?></h3>
                            <small class="text-muted"><?= lang('App.win_rate') ?></small>
                        </div>
                    </div>
                    <div class="row text-center mt-2">
                        <div class="col-6">
                            <small class="text-muted"><?= lang('App.current_streak') ?>: <strong><?= $stats->current_streak ?></strong></small>
                        </div>
                        <div class="col-6">
                            <small class="text-muted"><?= lang('App.best_streak') ?>: <strong><?= $stats->best_streak ?></strong></small>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
