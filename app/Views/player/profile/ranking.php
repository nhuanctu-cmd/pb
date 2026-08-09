<?= $this->extend('player/layouts/main') ?>

<?= $this->section('content') ?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0"><?= lang('App.ranking') ?></h1>
        <a href="/player/profile" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> <?= lang('App.back') ?></a>
    </div>

    <div class="list-group">
        <?php foreach (($rankings ?? []) as $index => $row): ?>
        <a href="/player/ranking" class="list-group-item list-group-item-action">
            <div class="d-flex align-items-center gap-3">
                <div class="text-center fw-bold" style="width: 42px;">#<?= $index + 1 ?></div>
                <img src="<?= $row->getAvatarUrl() ?>" class="rounded-circle" width="42" height="42" alt="">
                <div class="flex-fill">
                    <div class="fw-semibold"><?= esc($row->full_name) ?></div>
                    <small class="text-muted"><code><?= esc($row->player_code) ?></code> · <?= esc($row->level) ?></small>
                </div>
                <div class="text-end">
                    <div class="fw-bold"><?= number_format((float) $row->rating_score, 0) ?></div>
                    <small class="text-muted"><?= (float) ($row->win_rate ?? 0) ?>%</small>
                </div>
            </div>
        </a>
        <?php endforeach; ?>

        <?php if (empty($rankings)): ?>
        <div class="list-group-item text-center text-muted py-4"><?= lang('App.no_data') ?></div>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
