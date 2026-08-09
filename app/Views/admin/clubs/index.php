<?= $this->extend('layouts/admin_master') ?>
<?= $this->section('content') ?>
<div class="erp-action-bar">
    <a href="/admin/clubs/create" class="erp-btn erp-btn-primary"><i class="bi bi-plus-lg"></i> Tạo club</a>
</div>
<div class="erp-table-wrap">
    <table class="erp-table">
        <thead><tr><th>Club</th><th>Owner</th><th>Trạng thái</th><th class="col-actions">Action</th></tr></thead>
        <tbody>
        <?php foreach ($clubs as $club): ?>
            <tr>
                <td><strong><?= esc($club->name_vi) ?></strong><div class="erp-muted"><?= esc($club->name_en) ?></div></td>
                <td>#<?= esc($club->owner_player_id ?? '-') ?></td>
                <td><span class="erp-chip erp-status-neutral"><?= esc($club->status) ?></span></td>
                <td class="col-actions">
                    <a class="erp-btn erp-btn-icon" href="/admin/clubs/edit/<?= $club->id ?>"><i class="bi bi-pencil"></i></a>
                    <a class="erp-btn erp-btn-icon" href="/admin/clubs/delete/<?= $club->id ?>" data-confirm="Xóa club này?"><i class="bi bi-trash"></i></a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?= $this->endSection() ?>
