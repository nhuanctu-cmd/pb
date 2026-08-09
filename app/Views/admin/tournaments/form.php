<?= $this->extend('layouts/admin_master') ?>

<?= $this->section('content') ?>
<?php
$isEdit = ($mode ?? 'create') === 'edit';
$action = $isEdit ? '/admin/tournaments/update/' . $tournament->id : '/admin/tournaments/store';
$value = fn (string $field, $default = '') => old($field, $tournament->{$field} ?? $default);
$dt = fn ($v) => $v ? date('Y-m-d\TH:i', strtotime($v)) : '';
$categoryRows = $categories ?: array_fill(0, 3, (object) []);
$sponsorRows = $sponsors ?: array_fill(0, 3, (object) []);
$types = ['single_male' => 'Đơn nam', 'single_female' => 'Đơn nữ', 'double_male' => 'Đôi nam', 'double_female' => 'Đôi nữ', 'mixed_double' => 'Đôi nam nữ', 'team_battle' => 'Đồng đội'];
?>
<form method="post" action="<?= esc($action) ?>">
    <?= csrf_field() ?>
    <div class="erp-card mb-3">
        <div class="d-flex flex-wrap gap-2">
            <?php foreach (['1. Thông tin giải', '2. Hạng mục', '3. Luật', '4. Phí', '5. Nhà tài trợ & đăng ký'] as $step): ?>
                <span class="erp-chip erp-status-info"><?= esc($step) ?></span>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="erp-card mb-3">
        <h5>Thông tin giải</h5>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Chi nhánh</label>
                <select name="branch_id" class="form-select" required>
                    <option value="">Chọn chi nhánh</option>
                    <?php foreach ($branches as $branch): ?>
                        <option value="<?= $branch->id ?>" <?= (string) $value('branch_id') === (string) $branch->id ? 'selected' : '' ?>><?= esc($branch->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4"><label class="form-label">Tên VI</label><input name="name_vi" class="form-control" required value="<?= esc($value('name_vi')) ?>"></div>
            <div class="col-md-4"><label class="form-label">Tên EN</label><input name="name_en" class="form-control" value="<?= esc($value('name_en')) ?>"></div>
            <div class="col-md-4"><label class="form-label">Slug VI</label><input name="slug_vi" class="form-control" value="<?= esc($value('slug_vi')) ?>"></div>
            <div class="col-md-4"><label class="form-label">Slug EN</label><input name="slug_en" class="form-control" value="<?= esc($value('slug_en')) ?>"></div>
            <div class="col-md-4"><label class="form-label">Banner URL</label><input name="banner" class="form-control" value="<?= esc($value('banner')) ?>"></div>
            <div class="col-md-3"><label class="form-label">Ngày bắt đầu</label><input type="date" name="start_date" class="form-control" value="<?= esc($value('start_date')) ?>"></div>
            <div class="col-md-3"><label class="form-label">Ngày kết thúc</label><input type="date" name="end_date" class="form-control" value="<?= esc($value('end_date')) ?>"></div>
            <div class="col-md-3"><label class="form-label">Mở đăng ký</label><input type="datetime-local" name="registration_start" class="form-control" value="<?= esc($dt($value('registration_start'))) ?>"></div>
            <div class="col-md-3"><label class="form-label">Đóng đăng ký</label><input type="datetime-local" name="registration_end" class="form-control" value="<?= esc($dt($value('registration_end'))) ?>"></div>
            <div class="col-md-6"><label class="form-label">Mô tả VI</label><textarea name="description_vi" class="form-control" rows="4"><?= esc($value('description_vi')) ?></textarea></div>
            <div class="col-md-6"><label class="form-label">Mô tả EN</label><textarea name="description_en" class="form-control" rows="4"><?= esc($value('description_en')) ?></textarea></div>
        </div>
    </div>

    <div class="erp-card mb-3">
        <h5>Hạng mục</h5>
        <?php foreach ($categoryRows as $i => $category): ?>
            <div class="row g-2 align-items-end mb-2">
                <div class="col-md-3"><label class="form-label">Tên VI</label><input name="categories[<?= $i ?>][name_vi]" class="form-control" value="<?= esc($category->name_vi ?? '') ?>"></div>
                <div class="col-md-2"><label class="form-label">Tên EN</label><input name="categories[<?= $i ?>][name_en]" class="form-control" value="<?= esc($category->name_en ?? '') ?>"></div>
                <div class="col-md-2"><label class="form-label">Loại</label><select name="categories[<?= $i ?>][category_type]" class="form-select"><?php foreach ($types as $key => $label): ?><option value="<?= $key ?>" <?= ($category->category_type ?? '') === $key ? 'selected' : '' ?>><?= esc($label) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-1"><label class="form-label">Max</label><input type="number" name="categories[<?= $i ?>][max_teams]" class="form-control" value="<?= esc($category->max_teams ?? '') ?>"></div>
                <div class="col-md-1"><label class="form-label">Min</label><input type="number" name="categories[<?= $i ?>][min_rating]" class="form-control" value="<?= esc($category->min_rating ?? '') ?>"></div>
                <div class="col-md-1"><label class="form-label">Max</label><input type="number" name="categories[<?= $i ?>][max_rating]" class="form-control" value="<?= esc($category->max_rating ?? '') ?>"></div>
                <div class="col-md-2"><label class="form-label">Phí</label><input type="number" name="categories[<?= $i ?>][registration_fee]" class="form-control" value="<?= esc($category->registration_fee ?? '') ?>"></div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="erp-card mb-3">
        <h5>Luật & phí chung</h5>
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Luật VI</label><textarea name="rule_content_vi" class="form-control" rows="5"><?= esc($rule->rule_content_vi ?? '') ?></textarea></div>
            <div class="col-md-6"><label class="form-label">Luật EN</label><textarea name="rule_content_en" class="form-control" rows="5"><?= esc($rule->rule_content_en ?? '') ?></textarea></div>
            <div class="col-md-3"><label class="form-label">Tối đa đội</label><input type="number" name="max_teams" class="form-control" value="<?= esc($value('max_teams')) ?>"></div>
            <div class="col-md-3"><label class="form-label">Phí mặc định</label><input type="number" name="registration_fee" class="form-control" value="<?= esc($value('registration_fee', 0)) ?>"></div>
            <div class="col-md-3"><label class="form-label">Trạng thái</label><select name="status" class="form-select"><?php foreach (['draft', 'open', 'closed', 'running', 'completed', 'cancelled'] as $status): ?><option value="<?= $status ?>" <?= $value('status', 'draft') === $status ? 'selected' : '' ?>><?= esc($status) ?></option><?php endforeach; ?></select></div>
        </div>
    </div>

    <div class="erp-card mb-3">
        <h5>Nhà tài trợ</h5>
        <?php foreach ($sponsorRows as $i => $sponsor): ?>
            <div class="row g-2 mb-2">
                <div class="col-md-4"><input name="sponsors[<?= $i ?>][sponsor_name]" class="form-control" placeholder="Tên nhà tài trợ" value="<?= esc($sponsor->sponsor_name ?? '') ?>"></div>
                <div class="col-md-4"><input name="sponsors[<?= $i ?>][logo]" class="form-control" placeholder="Logo URL" value="<?= esc($sponsor->logo ?? '') ?>"></div>
                <div class="col-md-3"><input name="sponsors[<?= $i ?>][website]" class="form-control" placeholder="Website" value="<?= esc($sponsor->website ?? '') ?>"></div>
                <div class="col-md-1"><input type="number" name="sponsors[<?= $i ?>][sort_order]" class="form-control" value="<?= esc($sponsor->sort_order ?? $i) ?>"></div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="d-flex gap-2 justify-content-end">
        <a href="/admin/tournaments" class="erp-btn">Hủy</a>
        <button class="erp-btn erp-btn-primary"><i class="bi bi-save"></i> Lưu giải đấu</button>
    </div>
</form>
<?= $this->endSection() ?>
