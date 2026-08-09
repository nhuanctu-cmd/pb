<?= $this->extend('player/layouts/main') ?>
<?= $this->section('title') ?>Team của tôi<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Team của tôi</h1>
        <a class="btn btn-primary" href="/player/teams/create"><i class="bi bi-plus-circle"></i> Tạo team</a>
    </div>

    <?php if (! empty($invites)): ?>
        <div class="card mb-3"><div class="card-body">
            <h2 class="h6">Lời mời</h2>
            <?php foreach ($invites as $invite): ?>
                <form method="post" action="/player/teams/accept/<?= $invite->team_id ?>" class="d-flex justify-content-between align-items-center border-top py-2">
                    <?= csrf_field() ?>
                    <span><?= esc($invite->team_name) ?></span>
                    <button class="btn btn-sm btn-success"><i class="bi bi-check2"></i> Nhận lời</button>
                </form>
            <?php endforeach; ?>
        </div></div>
    <?php endif; ?>

    <div class="list-group">
        <?php foreach ($teams as $team): ?>
            <a class="list-group-item list-group-item-action" href="/player/teams/show/<?= $team->id ?>">
                <div class="d-flex justify-content-between">
                    <strong><?= esc($team->team_name) ?></strong>
                    <span class="badge text-bg-secondary"><?= esc($team->team_type) ?></span>
                </div>
                <small class="text-muted"><?= esc($team->club_name ?? 'Không thuộc club') ?> · Rating <?= esc($team->rating_avg) ?></small>
            </a>
        <?php endforeach; ?>
    </div>
    <?php if (empty($teams)): ?><div class="text-center text-muted py-5">Chưa có team.</div><?php endif; ?>
</div>
<?= $this->endSection() ?>
