<?= $this->extend('layouts/admin_master') ?>
<?= $this->section('content') ?>
<?php if ($request): ?>
<div class="erp-card mb-3">
    <h3>Kèo #<?= (int) $request->id ?></h3>
    <p class="erp-muted"><?= esc($request->preferred_date) ?> <?= substr($request->preferred_start_time, 0, 5) ?>-<?= substr($request->preferred_end_time, 0, 5) ?> · <?= esc($request->match_type) ?></p>
    <form method="post" action="/admin/matches/approve/<?= $request->id ?>"><?= csrf_field() ?><button class="erp-btn erp-btn-primary"><i class="bi bi-magic"></i> Auto gợi ý và xác nhận</button></form>
</div>
<div class="erp-table-wrap">
    <table class="erp-table"><thead><tr><th>Người phù hợp</th><th>Rating</th><th>Hội viên</th><th>Điểm match</th></tr></thead><tbody>
        <?php foreach ($suggestedPlayers as $player): ?><tr><td><?= esc($player->full_name) ?></td><td><?= esc($player->rating_score) ?></td><td><?= $player->is_member ? 'Có' : 'Không' ?></td><td><?= (int) $player->match_score ?></td></tr><?php endforeach; ?>
    </tbody></table>
</div>
<?php endif; ?>
<?= $this->endSection() ?>
