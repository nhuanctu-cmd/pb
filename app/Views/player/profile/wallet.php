<?= $this->extend('player/layouts/main') ?>

<?= $this->section('content') ?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3"><?= lang('App.wallet') ?></h1>
        <a href="/player/profile" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> <?= lang('App.back') ?></a>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body text-center py-4">
                    <h5><?= lang('App.current_balance') ?></h5>
                    <h1 class="mb-0"><?= $wallet ? $wallet->getBalanceFormatted() : '0đ' ?></h1>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header"><h5><?= lang('App.topup') ?></h5></div>
                <div class="card-body">
                    <form method="post" action="/player/wallet/topup">
                        <div class="mb-3">
                            <label class="form-label"><?= lang('App.amount') ?></label>
                            <div class="input-group">
                                <input type="number" name="amount" class="form-control" placeholder="100000" required min="10000" step="10000">
                                <span class="input-group-text">đ</span>
                            </div>
                        </div>
                        <div class="d-grid gap-2 mb-3">
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="document.querySelector('[name=amount]').value=50000">50,000đ</button>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="document.querySelector('[name=amount]').value=100000">100,000đ</button>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="document.querySelector('[name=amount]').value=200000">200,000đ</button>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="document.querySelector('[name=amount]').value=500000">500,000đ</button>
                        </div>
                        <button type="submit" class="btn btn-success w-100"><?= lang('App.topup') ?></button>
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
                                    <th><?= lang('App.balance') ?></th>
                                    <th><?= lang('App.note') ?></th>
                                    <th><?= lang('App.date') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($transactions)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted"><?= lang('App.no_data') ?></td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($transactions as $txn): ?>
                                <tr>
                                    <td><?= $txn->id ?></td>
                                    <td><?= $txn->getTypeBadge() ?></td>
                                    <td class="<?= in_array($txn->type, ['topup', 'refund']) ? 'text-success' : 'text-danger' ?>"><?= $txn->getAmountFormatted() ?></td>
                                    <td><?= number_format($txn->balance_after, 0, ',', '.') ?>đ</td>
                                    <td><?= esc($txn->note) ?></td>
                                    <td><small><?= date('H:i d/m/Y', strtotime($txn->created_at)) ?></small></td>
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
</div>
<?= $this->endSection() ?>
