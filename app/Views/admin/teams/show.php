<?= $this->extend('layouts/admin_master') ?>
<?= $this->section('content') ?>
<?php if ($team): ?>
<div class="erp-card mb-3">
    <h3><?= esc($team->team_name) ?></h3>
    <p class="erp-muted"><?= esc($team->team_type) ?> · Rating <?= esc($team->rating_avg) ?></p>
    <form method="post" action="/admin/teams/status/<?= $team->id ?>" class="d-flex gap-2">
        <?= csrf_field() ?>
        <select name="status" class="erp-select" style="width:180px"><option value="active">Active</option><option value="inactive">Inactive</option><option value="disbanded">Disbanded</option></select>
        <button class="erp-btn"><i class="bi bi-check-lg"></i> Cập nhật</button>
    </form>
</div>
<div class="erp-table-wrap">
    <table class="erp-table"><thead><tr><th>Người chơi</th><th>Vai trò</th><th>Rating</th><th>Trạng thái</th></tr></thead><tbody>
        <?php foreach ($members as $member): ?><tr><td><?= esc($member->full_name) ?></td><td><?= esc($member->role) ?></td><td><?= esc($member->rating_score) ?></td><td><?= esc($member->status) ?></td></tr><?php endforeach; ?>
    </tbody></table>
</div>
<?php endif; ?>
<?= $this->endSection() ?>
