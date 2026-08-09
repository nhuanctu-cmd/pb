<?= $this->extend('layouts/admin_master') ?>
<?= $this->section('content') ?>
<?php $isEdit = ! empty($club); ?>
<form method="post" action="<?= $isEdit ? '/admin/clubs/update/' . $club->id : '/admin/clubs/store' ?>" class="erp-form">
    <?= csrf_field() ?>
    <div class="erp-form-grid">
        <div class="erp-field"><label>Tên VI</label><input class="erp-control" name="name_vi" value="<?= esc(old('name_vi', $club->name_vi ?? '')) ?>" required></div>
        <div class="erp-field"><label>Tên EN</label><input class="erp-control" name="name_en" value="<?= esc(old('name_en', $club->name_en ?? '')) ?>"></div>
        <div class="erp-field"><label>Logo URL</label><input class="erp-control" name="logo" value="<?= esc(old('logo', $club->logo ?? '')) ?>"></div>
        <div class="erp-field"><label>Owner</label><select class="erp-select" name="owner_player_id"><option value="">Chọn owner</option><?php foreach ($players as $player): ?><option value="<?= $player->id ?>" <?= (old('owner_player_id', $club->owner_player_id ?? '') == $player->id) ? 'selected' : '' ?>><?= esc($player->full_name) ?></option><?php endforeach; ?></select></div>
        <div class="erp-field"><label>Trạng thái</label><select class="erp-select" name="status"><option value="active">Active</option><option value="pending">Pending</option><option value="inactive">Inactive</option></select></div>
    </div>
    <div class="erp-field mt-3"><label>Mô tả VI</label><textarea class="erp-control" name="description_vi" rows="3"><?= esc(old('description_vi', $club->description_vi ?? '')) ?></textarea></div>
    <div class="erp-field mt-3"><label>Mô tả EN</label><textarea class="erp-control" name="description_en" rows="3"><?= esc(old('description_en', $club->description_en ?? '')) ?></textarea></div>
    <div class="mt-3"><button class="erp-btn erp-btn-primary"><i class="bi bi-check-lg"></i> Lưu</button><a href="/admin/clubs" class="erp-btn">Hủy</a></div>
</form>
<?= $this->endSection() ?>
