<?= $this->extend('player/layouts/main') ?>
<?= $this->section('title') ?><?= esc($team->team_name) ?><?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="container py-4">
    <h1 class="h3"><?= esc($team->team_name) ?></h1>
    <p class="text-muted">Rating trung bình <?= esc($team->rating_avg) ?> · <?= esc($team->team_type) ?></p>

    <div class="card mb-3"><div class="card-body">
        <h2 class="h6">Mời thành viên</h2>
        <form method="post" action="/player/teams/invite/<?= $team->id ?>" class="d-flex gap-2">
            <?= csrf_field() ?>
            <select name="player_id" class="form-select" required>
                <option value="">Chọn người chơi</option>
                <?php foreach ($players as $player): ?><option value="<?= $player->id ?>"><?= esc($player->full_name) ?> · <?= esc($player->rating_score) ?></option><?php endforeach; ?>
            </select>
            <button class="btn btn-primary"><i class="bi bi-send"></i></button>
        </form>
    </div></div>

    <div class="list-group">
        <?php foreach ($members as $member): ?>
            <div class="list-group-item d-flex justify-content-between align-items-center">
                <div><strong><?= esc($member->full_name) ?></strong><br><small class="text-muted"><?= esc($member->role) ?> · <?= esc($member->status) ?> · <?= esc($member->rating_score) ?></small></div>
                <?php if ($member->role !== 'captain'): ?>
                    <form method="post" action="/player/teams/remove/<?= $team->id ?>/<?= $member->player_id ?>"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle"></i></button></form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?= $this->endSection() ?>
