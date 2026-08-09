<?= $this->extend('player/layouts/main') ?>
<?= $this->section('title') ?>Chi tiết kèo<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="container py-4">
    <h1 class="h3">Chi tiết kèo</h1>
    <div class="card mb-3"><div class="card-body">
        <div><?= esc($request->preferred_date) ?> <?= substr($request->preferred_start_time, 0, 5) ?>-<?= substr($request->preferred_end_time, 0, 5) ?></div>
        <div class="text-muted">Rating <?= (int) $request->level_from ?> - <?= (int) $request->level_to ?> · cần <?= (int) $request->need_players ?> người</div>
        <form method="post" action="/player/matches/join/<?= $request->id ?>" class="mt-3"><?= csrf_field() ?><button class="btn btn-success"><i class="bi bi-person-plus"></i> Tham gia kèo</button></form>
    </div></div>
    <h2 class="h5">Người phù hợp</h2>
    <form method="post" action="/player/matches/confirm/<?= $request->id ?>">
        <?= csrf_field() ?>
        <div class="list-group mb-3">
            <?php foreach ($suggestedPlayers as $player): ?>
                <label class="list-group-item d-flex justify-content-between align-items-center">
                    <span><input type="checkbox" name="player_ids[]" value="<?= $player->id ?>" class="form-check-input me-2"><?= esc($player->full_name) ?> · <?= esc($player->rating_score) ?></span>
                    <small class="text-muted"><?= $player->is_member ? 'Hội viên' : '' ?> <?= (int) $player->match_score ?></small>
                </label>
            <?php endforeach; ?>
        </div>
        <button class="btn btn-primary"><i class="bi bi-check-circle"></i> Xác nhận trận</button>
    </form>
</div>
<?= $this->endSection() ?>
