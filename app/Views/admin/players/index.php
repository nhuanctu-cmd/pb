<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3"><?= $pageTitle ?? lang('App.players') ?></h1>
    <div class="d-flex gap-2">
        <a href="/admin/players/dashboard" class="btn btn-outline-primary"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <a href="/admin/players/ranking" class="btn btn-outline-warning"><i class="bi bi-trophy"></i> Ranking</a>
        <a href="/admin/players/create" class="btn btn-primary"><i class="bi bi-plus"></i> <?= lang('App.create') ?></a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="get" class="row g-2 mb-3">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="<?= lang('App.search') ?>..." value="<?= $filters['search'] ?? '' ?>">
            </div>
            <div class="col-md-2">
                <select name="level" class="form-select">
                    <option value=""><?= lang('App.all_levels') ?></option>
                    <option value="beginner" <?= ($filters['level'] ?? '') === 'beginner' ? 'selected' : '' ?>><?= lang('App.level_beginner') ?></option>
                    <option value="intermediate" <?= ($filters['level'] ?? '') === 'intermediate' ? 'selected' : '' ?>><?= lang('App.level_intermediate') ?></option>
                    <option value="advanced" <?= ($filters['level'] ?? '') === 'advanced' ? 'selected' : '' ?>><?= lang('App.level_advanced') ?></option>
                    <option value="pro" <?= ($filters['level'] ?? '') === 'pro' ? 'selected' : '' ?>><?= lang('App.level_pro') ?></option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="region" class="form-select">
                    <option value="">All regions</option>
                    <?php foreach (($regions ?? []) as $region): ?>
                    <option value="<?= esc($region) ?>" <?= ($filters['region'] ?? '') === $region ? 'selected' : '' ?>><?= esc($region) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="home_branch_id" class="form-select">
                    <option value="">All branches</option>
                    <?php foreach (($branches ?? []) as $branch): ?>
                    <option value="<?= $branch->id ?>" <?= (string) ($filters['home_branch_id'] ?? '') === (string) $branch->id ? 'selected' : '' ?>><?= esc($branch->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="is_member" class="form-select">
                    <option value=""><?= lang('App.all_members') ?></option>
                    <option value="yes" <?= ($filters['is_member'] ?? '') === 'yes' ? 'selected' : '' ?>><?= lang('App.member_yes') ?></option>
                    <option value="no" <?= ($filters['is_member'] ?? '') === 'no' ? 'selected' : '' ?>><?= lang('App.member_no') ?></option>
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-secondary w-100"><i class="bi bi-search"></i></button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th><?= lang('App.player_code') ?></th>
                        <th><?= lang('App.full_name') ?></th>
                        <th><?= lang('App.phone') ?></th>
                        <th><?= lang('App.level') ?></th>
                        <th><?= lang('App.rating_score') ?></th>
                        <th>Streak</th>
                        <th><?= lang('App.membership') ?></th>
                        <th><?= lang('App.status') ?></th>
                        <th><?= lang('App.actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($players)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted"><?= lang('App.no_data') ?></td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($players as $player): ?>
                    <tr>
                        <td><code><?= esc($player->player_code) ?></code></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <img src="<?= $player->getAvatarUrl() ?>" class="rounded-circle me-2" width="32" height="32" alt="">
                                <div>
                                    <strong><?= esc($player->full_name) ?></strong>
                                    <?php if ($player->email): ?>
                                    <br><small class="text-muted"><?= esc($player->email) ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td><?= esc($player->phone) ?></td>
                        <td><?= $player->getLevelBadge() ?></td>
                        <td><?= number_format($player->rating_score, 0) ?></td>
                        <td><span class="badge bg-light text-dark"><?= (int) ($player->checkin_streak ?? 0) ?> days</span></td>
                        <td>
                            <?php
                            $membershipModel = model(\App\Models\MembershipModel::class);
                            $activeMem = $membershipModel->getActiveByPlayer($player->id);
                            ?>
                            <?php if ($activeMem): ?>
                            <span class="badge bg-success"><?= lang('App.member') ?></span>
                            <?php else: ?>
                            <span class="badge bg-secondary"><?= lang('App.not_member') ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= $player->getStatusBadge() ?></td>
                        <td>
                            <a href="/admin/players/profile/<?= $player->id ?>" class="btn btn-sm btn-primary"><i class="bi bi-person-vcard"></i></a>
                            <a href="/admin/players/edit/<?= $player->id ?>" class="btn btn-sm btn-info"><i class="bi bi-pencil"></i></a>
                            <a href="/admin/players/match-history/<?= $player->id ?>" class="btn btn-sm btn-success"><i class="bi bi-controller"></i></a>
                            <a href="/admin/players/wallet/<?= $player->id ?>" class="btn btn-sm btn-warning"><i class="bi bi-wallet2"></i></a>
                            <a href="/admin/players/booking-history/<?= $player->id ?>" class="btn btn-sm btn-secondary"><i class="bi bi-calendar-check"></i></a>
                        </td>
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
