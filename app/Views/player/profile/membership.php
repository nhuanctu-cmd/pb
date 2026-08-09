<?= $this->extend('player/layouts/main') ?>

<?= $this->section('content') ?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3"><?= lang('App.membership_card') ?></h1>
        <a href="/player/profile" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> <?= lang('App.back') ?></a>
    </div>

    <?php if ($active): ?>
    <div class="card bg-primary text-white mb-4">
        <div class="card-body text-center py-4">
            <h4><?= lang('App.membership_active') ?></h4>
            <h2 class="my-3"><?= lang('App.member') ?></h2>
            <div class="mb-2">
                <?php $memberQr = 'MEMBER|' . $player->tenant_id . '|' . $player->id . '|' . $player->player_code . '|' . $active->end_date; ?>
                <img width="128" height="128" class="bg-white rounded p-2" alt="<?= lang('App.qr_member') ?>" src="https://api.qrserver.com/v1/create-qr-code/?size=128x128&data=<?= urlencode($memberQr) ?>">
            </div>
            <p><strong><?= esc($player->full_name) ?></strong></p>
            <p class="mb-1"><code><?= $player->player_code ?></code></p>
            <p><?= lang('App.valid_until') ?>: <strong><?= $active->end_date ?></strong></p>
            <p><?= lang('App.remaining') ?>: <?= $active->getRemainingDays() ?> <?= lang('App.days') ?></p>
        </div>
    </div>
    <?php else: ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> <?= lang('App.no_membership') ?>
    </div>
    <?php endif; ?>

    <h4 class="mb-3"><?= lang('App.membership_packages') ?></h4>
    <div class="row">
        <?php foreach ($packages as $pkg): ?>
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <h5><?= esc($pkg->getName()) ?></h5>
                    <h2 class="text-primary"><?= $pkg->getPriceFormatted() ?></h2>
                    <p><?= $pkg->duration_days ?> <?= lang('App.days') ?></p>
                    <?php if ($pkg->discount_percent > 0): ?>
                    <p class="text-danger"><i class="bi bi-tag"></i> -<?= $pkg->discount_percent ?>%</p>
                    <?php endif; ?>
                    <form method="post" action="/player/profile/buy-package">
                        <input type="hidden" name="package_id" value="<?= $pkg->id ?>">
                        <button type="submit" class="btn btn-primary w-100" <?= $active ? 'onclick="return confirm(\'Gói hiện tại sẽ bị hủy. Tiếp tục?\')"' : '' ?>>
                            <?= lang('App.register') ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($memberships)): ?>
    <h4 class="mt-4 mb-3"><?= lang('App.history') ?></h4>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th><?= lang('App.package') ?></th>
                            <th><?= lang('App.start_date') ?></th>
                            <th><?= lang('App.end_date') ?></th>
                            <th><?= lang('App.status') ?></th>
                            <th><?= lang('App.actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($memberships as $mem): ?>
                        <tr>
                            <td><?= esc($mem->package_name_vi ?? $mem->package_name_en ?? (lang('App.package') . ' #' . $mem->package_id)) ?></td>
                            <td><?= $mem->start_date ?></td>
                            <td><?= $mem->end_date ?></td>
                            <td><?= $mem->getStatusBadge() ?></td>
                            <td>
                                <?php if ($mem->status === 'active'): ?>
                                <a href="/player/profile/cancel-membership/<?= $mem->id ?>" class="btn btn-sm btn-danger" onclick="return confirm('<?= lang('App.confirm_cancel') ?>')"><?= lang('App.cancel') ?></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
