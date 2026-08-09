<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3"><?= $pageTitle ?></h1>
    <div>
        <a href="/admin/players/edit/<?= $player->id ?>" class="btn btn-info"><i class="bi bi-person"></i> <?= lang('App.player_profile') ?></a>
        <a href="/admin/players" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> <?= lang('App.back') ?></a>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <img src="<?= $player->getAvatarUrl() ?>" class="rounded-circle mb-3" width="80" height="80" alt="">
                <h5><?= esc($player->full_name) ?></h5>
                <p class="text-muted"><code><?= $player->player_code ?></code></p>
                <hr>
                <h2 class="text-primary mb-0"><?= $wallet ? $wallet->getBalanceFormatted() : '0đ' ?></h2>
                <small class="text-muted"><?= lang('App.current_balance') ?></small>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><h5><?= lang('App.topup') ?></h5></div>
            <div class="card-body">
                <form method="post" action="/admin/players/topup/<?= $player->id ?>">
                    <div class="mb-3">
                        <label class="form-label"><?= lang('App.amount') ?></label>
                        <input type="number" step="1000" name="amount" class="form-control" required min="1000">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= lang('App.note') ?></label>
                        <input type="text" name="note" class="form-control" placeholder="Ghi chú nạp tiền">
                    </div>
                    <button type="submit" class="btn btn-success w-100"><i class="bi bi-plus-circle"></i> <?= lang('App.topup') ?></button>
                </form>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><h5><?= lang('App.adjust_balance') ?></h5></div>
            <div class="card-body">
                <form method="post" action="/admin/players/adjust-wallet/<?= $player->id ?>">
                    <div class="mb-3">
                        <label class="form-label"><?= lang('App.new_balance') ?></label>
                        <input type="number" step="1000" name="balance" class="form-control" value="<?= $wallet ? $wallet->balance : 0 ?>" required min="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= lang('App.reason') ?></label>
                        <input type="text" name="note" class="form-control" placeholder="Lý do điều chỉnh">
                    </div>
                    <button type="submit" class="btn btn-warning w-100"><i class="bi bi-pencil-square"></i> <?= lang('App.adjust') ?></button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h5><?= lang('App.transaction_history') ?></h5></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th><?= lang('App.type') ?></th>
                                <th><?= lang('App.amount') ?></th>
                                <th><?= lang('App.balance_before') ?></th>
                                <th><?= lang('App.balance_after') ?></th>
                                <th><?= lang('App.note') ?></th>
                                <th><?= lang('App.created_at') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted"><?= lang('App.no_data') ?></td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($transactions as $txn): ?>
                            <tr>
                                <td><?= $txn->id ?></td>
                                <td><?= $txn->getTypeBadge() ?></td>
                                <td class="<?= in_array($txn->type, ['topup', 'refund']) ? 'text-success' : 'text-danger' ?>"><?= $txn->getAmountFormatted() ?></td>
                                <td><?= number_format($txn->balance_before, 0, ',', '.') ?>đ</td>
                                <td><?= number_format($txn->balance_after, 0, ',', '.') ?>đ</td>
                                <td><?= esc($txn->note) ?></td>
                                <td><?= $txn->created_at ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
