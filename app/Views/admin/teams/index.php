<?= $this->extend('layouts/admin_master') ?>
<?= $this->section('content') ?>
<div class="erp-table-wrap">
    <table class="erp-table">
        <thead><tr><th>Team</th><th>Club</th><th>Captain</th><th>Loại</th><th>Rating</th><th>Trạng thái</th><th class="col-actions">Action</th></tr></thead>
        <tbody>
        <?php foreach ($teams as $team): ?>
            <tr>
                <td><strong><?= esc($team->team_name) ?></strong></td>
                <td><?= esc($team->club_name ?? '-') ?></td>
                <td><?= esc($team->captain_name ?? ('#' . $team->captain_player_id)) ?></td>
                <td><?= esc($team->team_type) ?></td>
                <td><?= esc($team->rating_avg) ?></td>
                <td><span class="erp-chip erp-status-neutral"><?= esc($team->status) ?></span></td>
                <td class="col-actions"><a class="erp-btn erp-btn-icon" href="/admin/teams/show/<?= $team->id ?>"><i class="bi bi-eye"></i></a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?= $this->endSection() ?>
