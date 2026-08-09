<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3"><?= $pageTitle ?></h1>
    <a href="/admin/players" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> <?= lang('App.back') ?></a>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form method="post" action="/admin/players/<?= isset($player) ? 'update/' . $player->id : 'store' ?>">
                    <div class="mb-3">
                        <label class="form-label"><?= lang('App.full_name') ?> <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control" value="<?= esc($player->full_name ?? old('full_name')) ?>" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?= lang('App.phone') ?></label>
                            <input type="text" name="phone" class="form-control" value="<?= esc($player->phone ?? old('phone')) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?= lang('App.email') ?></label>
                            <input type="email" name="email" class="form-control" value="<?= esc($player->email ?? old('email')) ?>">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><?= lang('App.gender') ?></label>
                            <select name="gender" class="form-select">
                                <option value="other" <?= ($player->gender ?? 'other') === 'other' ? 'selected' : '' ?>><?= lang('App.other') ?></option>
                                <option value="male" <?= ($player->gender ?? '') === 'male' ? 'selected' : '' ?>><?= lang('App.male') ?></option>
                                <option value="female" <?= ($player->gender ?? '') === 'female' ? 'selected' : '' ?>><?= lang('App.female') ?></option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><?= lang('App.birthday') ?></label>
                            <input type="date" name="birthday" class="form-control" value="<?= $player->birthday ?? old('birthday') ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><?= lang('App.level') ?></label>
                            <select name="level" class="form-select">
                                <option value="beginner" <?= ($player->level ?? 'beginner') === 'beginner' ? 'selected' : '' ?>><?= lang('App.level_beginner') ?></option>
                                <option value="intermediate" <?= ($player->level ?? '') === 'intermediate' ? 'selected' : '' ?>><?= lang('App.level_intermediate') ?></option>
                                <option value="advanced" <?= ($player->level ?? '') === 'advanced' ? 'selected' : '' ?>><?= lang('App.level_advanced') ?></option>
                                <option value="pro" <?= ($player->level ?? '') === 'pro' ? 'selected' : '' ?>><?= lang('App.level_pro') ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Region</label>
                            <input type="text" name="region" class="form-control" value="<?= esc($player->region ?? old('region')) ?>" placeholder="HCM, Ha Noi, Da Nang...">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Home branch</label>
                            <select name="home_branch_id" class="form-select">
                                <option value="">-- None --</option>
                                <?php foreach (($branches ?? []) as $branch): ?>
                                <option value="<?= $branch->id ?>" <?= (string) ($player->home_branch_id ?? old('home_branch_id')) === (string) $branch->id ? 'selected' : '' ?>><?= esc($branch->name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <?php if (isset($player)): ?>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><?= lang('App.rating_score') ?></label>
                            <input type="number" step="0.01" name="rating_score" class="form-control" value="<?= $player->rating_score ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label"><?= lang('App.status') ?></label>
                            <select name="status" class="form-select">
                                <option value="active" <?= $player->status === 'active' ? 'selected' : '' ?>><?= lang('App.active') ?></option>
                                <option value="inactive" <?= $player->status === 'inactive' ? 'selected' : '' ?>><?= lang('App.inactive') ?></option>
                                <option value="banned" <?= $player->status === 'banned' ? 'selected' : '' ?>><?= lang('App.banned') ?></option>
                            </select>
                        </div>
                    </div>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary"><?= lang('App.save') ?></button>
                </form>
            </div>
        </div>

        <?php if (isset($player) && !empty($transactions)): ?>
        <div class="card mt-3">
            <div class="card-header"><h5><?= lang('App.recent_transactions') ?></h5></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th><?= lang('App.type') ?></th>
                                <th><?= lang('App.amount') ?></th>
                                <th><?= lang('App.balance') ?></th>
                                <th><?= lang('App.note') ?></th>
                                <th><?= lang('App.created_at') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $txn): ?>
                            <tr>
                                <td><?= $txn->getTypeBadge() ?></td>
                                <td><?= $txn->getAmountFormatted() ?></td>
                                <td><?= number_format($txn->balance_after, 0, ',', '.') ?>đ</td>
                                <td><?= esc($txn->note) ?></td>
                                <td><?= $txn->created_at ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-md-4">
        <?php if (isset($player)): ?>
        <div class="card">
            <div class="card-body text-center">
                <img src="<?= $player->getAvatarUrl() ?>" class="rounded-circle mb-3" width="100" height="100" alt="">
                <h5><?= esc($player->full_name) ?></h5>
                <p class="text-muted mb-1"><code><?= $player->player_code ?></code></p>
                <?= $player->getLevelBadge() ?>
                <hr>
                <p><strong><?= lang('App.rating_score') ?>:</strong> <?= number_format($player->rating_score, 1) ?></p>
                <p><strong><?= lang('App.membership') ?>:</strong>
                    <?php
                    $membershipModel = model(\App\Models\MembershipModel::class);
                    $activeMem = $membershipModel->getActiveByPlayer($player->id);
                    ?>
                    <?php if ($activeMem): ?>
                    <span class="badge bg-success"><?= lang('App.member') ?></span>
                    <small class="d-block text-muted"><?= $activeMem->end_date ?> (<?= $activeMem->getRemainingDays() ?> ngày)</small>
                    <?php else: ?>
                    <span class="badge bg-secondary"><?= lang('App.not_member') ?></span>
                    <?php endif; ?>
                </p>
                <div class="d-grid gap-2">
                    <a href="/admin/players/wallet/<?= $player->id ?>" class="btn btn-sm btn-warning"><i class="bi bi-wallet2"></i> <?= lang('App.wallet') ?></a>
                    <a href="/admin/players/profile/<?= $player->id ?>" class="btn btn-sm btn-primary"><i class="bi bi-person-vcard"></i> Profile</a>
                    <a href="/admin/players/match-history/<?= $player->id ?>" class="btn btn-sm btn-success"><i class="bi bi-controller"></i> Matches</a>
                    <a href="/admin/players/booking-history/<?= $player->id ?>" class="btn btn-sm btn-secondary"><i class="bi bi-calendar-check"></i> <?= lang('App.booking_history') ?></a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
