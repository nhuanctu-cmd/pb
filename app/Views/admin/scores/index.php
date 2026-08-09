<?= $this->extend('layouts/admin_master') ?>

<?= $this->section('content') ?>
<div class="erp-action-bar">
    <div class="d-flex gap-2 flex-wrap">
        <a href="/live-scores/tv" class="erp-btn" target="_blank"><i class="bi bi-tv"></i> TV display</a>
        <a href="/live-scores/bracket" class="erp-btn" target="_blank"><i class="bi bi-diagram-3"></i> Public bracket</a>
    </div>
    <button class="erp-btn" onclick="location.reload()"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
</div>

<?php if (empty($matches)): ?>
    <div class="erp-card erp-empty">
        <div class="erp-empty-icon"><i class="bi bi-trophy"></i></div>
        <h3>Chưa có trận đấu tournament</h3>
        <p>Module điểm đã sẵn sàng. Khi bảng tournament_matches có dữ liệu, danh sách trận sẽ hiện ở đây.</p>
    </div>
<?php else: ?>
    <div class="erp-table-wrap">
        <table class="erp-table">
            <thead>
                <tr>
                    <th>Trận</th>
                    <th>Đội A</th>
                    <th>Đội B</th>
                    <th>Thể thức</th>
                    <th>Trạng thái</th>
                    <th class="col-actions">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($matches as $match): ?>
                <tr>
                    <td><strong>#<?= (int) $match->id ?></strong><div class="erp-muted"><?= esc($match->round_name ?? $match->round ?? '-') ?></div></td>
                    <td><?= esc($match->team_a_name ?? ('Team #' . ($match->team_a_id ?? '-'))) ?></td>
                    <td><?= esc($match->team_b_name ?? ('Team #' . ($match->team_b_id ?? '-'))) ?></td>
                    <td>BO<?= esc($match->best_of ?? $match->number_of_sets ?? 1) ?></td>
                    <td><?= renderStatusBadge($match->status ?? 'scheduled', 'general') ?></td>
                    <td class="col-actions">
                        <a href="/admin/scores/<?= (int) $match->id ?>" class="erp-btn erp-btn-icon" title="Nhập điểm"><i class="bi bi-pencil-square"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?= $this->endSection() ?>
