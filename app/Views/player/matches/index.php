<?= $this->extend('player/layouts/main') ?>
<?= $this->section('title') ?>Tìm kèo<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Tìm kèo</h1>
        <a class="btn btn-primary" href="/player/matches/create"><i class="bi bi-plus-circle"></i> Tạo kèo</a>
    </div>
    <h2 class="h5">Kèo đang mở</h2>
    <div class="list-group mb-4">
        <?php foreach ($requests as $request): ?>
            <a class="list-group-item list-group-item-action" href="/player/matches/show/<?= $request->id ?>">
                <div class="d-flex justify-content-between"><strong><?= esc($request->full_name) ?></strong><span class="badge text-bg-success"><?= esc($request->match_type) ?></span></div>
                <small class="text-muted"><?= esc($request->branch_name) ?> · <?= esc($request->preferred_date) ?> <?= substr($request->preferred_start_time, 0, 5) ?>-<?= substr($request->preferred_end_time, 0, 5) ?> · cần <?= (int) $request->need_players ?> người</small>
            </a>
        <?php endforeach; ?>
    </div>
    <h2 class="h5">Trận đã xác nhận</h2>
    <div class="list-group">
        <?php foreach ($matches as $match): ?>
            <div class="list-group-item">
                <strong><?= esc($match->branch_name) ?></strong>
                <div class="text-muted small"><?= esc($match->match_date) ?> <?= substr($match->start_time, 0, 5) ?>-<?= substr($match->end_time, 0, 5) ?> · <?= esc($match->status) ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?= $this->endSection() ?>
