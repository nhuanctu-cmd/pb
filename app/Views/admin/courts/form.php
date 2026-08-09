<?= $this->extend('layouts/admin_master') ?>

<?= $this->section('content') ?>
<?php
$isEdit = isset($court);
$action = $isEdit ? '/admin/courts/update/' . $court->id : '/admin/courts/create';
?>
<div class="erp-grid-2">
    <form method="post" action="<?= $action ?>" class="erp-form">
        <?= csrf_field() ?>

        <section class="erp-form-section">
            <div class="erp-form-section-header">
                <h2>Thông tin cơ bản</h2>
                <div class="erp-muted">Mã sân, tên sân, chi nhánh và phân loại.</div>
            </div>
            <div class="erp-form-section-body erp-form-grid">
                <div class="erp-field">
                    <label>Chi nhánh <span class="erp-required">*</span></label>
                    <select name="branch_id" class="erp-select" required>
                        <option value="">Chọn chi nhánh</option>
                        <?php foreach ($branches ?? [] as $branch): ?>
                            <option value="<?= $branch->id ?>" <?= old('branch_id', $court->branch_id ?? '') == $branch->id ? 'selected' : '' ?>><?= esc($branch->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="erp-field">
                    <label>Loại sân <span class="erp-required">*</span></label>
                    <select name="court_type_id" class="erp-select" required>
                        <option value="">Chọn loại sân</option>
                        <?php foreach ($courtTypes ?? [] as $type): ?>
                            <option value="<?= $type->id ?>" <?= old('court_type_id', $court->court_type_id ?? '') == $type->id ? 'selected' : '' ?>><?= esc($type->name_vi) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="erp-field">
                    <label>Mã sân <span class="erp-required">*</span></label>
                    <input name="code" class="erp-control" required maxlength="50" value="<?= esc(old('code', $court->code ?? '')) ?>" placeholder="A01">
                    <div class="erp-hint">Mã nên ngắn, dễ đọc trên lịch vận hành.</div>
                </div>
                <div class="erp-field">
                    <label>Tên sân <span class="erp-required">*</span></label>
                    <input name="name_vi" class="erp-control" required value="<?= esc(old('name_vi', $court->name_vi ?? '')) ?>" placeholder="Sân A01 Indoor Premium">
                </div>
                <div class="erp-field">
                    <label>Tên tiếng Anh</label>
                    <input name="name_en" class="erp-control" value="<?= esc(old('name_en', $court->name_en ?? '')) ?>">
                </div>
                <div class="erp-field">
                    <label>Thứ tự hiển thị</label>
                    <input type="number" name="sort_order" class="erp-control" value="<?= esc(old('sort_order', $court->sort_order ?? 0)) ?>">
                </div>
            </div>
        </section>

        <section class="erp-form-section">
            <div class="erp-form-section-header"><h2>Tiện ích sân</h2></div>
            <div class="erp-form-section-body erp-form-grid">
                <div class="erp-field">
                    <label>Tầng</label>
                    <input type="number" name="floor" class="erp-control" value="<?= esc(old('floor', $court->floor ?? 1)) ?>">
                </div>
                <div class="erp-field">
                    <label>Diện tích</label>
                    <input type="number" step="0.01" name="area" class="erp-control" value="<?= esc(old('area', $court->area ?? '')) ?>" placeholder="120">
                </div>
                <label class="erp-switch"><input type="checkbox" name="is_indoor" value="1" <?= old('is_indoor', $court->is_indoor ?? 0) ? 'checked' : '' ?>> Trong nhà</label>
                <label class="erp-switch"><input type="checkbox" name="has_light" value="1" <?= old('has_light', $court->has_light ?? 1) ? 'checked' : '' ?>> Có đèn</label>
                <label class="erp-switch"><input type="checkbox" name="has_fan" value="1" <?= old('has_fan', $court->has_fan ?? 0) ? 'checked' : '' ?>> Có quạt</label>
                <label class="erp-switch"><input type="checkbox" name="has_camera" value="1" <?= old('has_camera', $court->has_camera ?? 0) ? 'checked' : '' ?>> Có camera</label>
            </div>
        </section>

        <section class="erp-form-section">
            <div class="erp-form-section-header"><h2>Lịch hoạt động</h2></div>
            <div class="erp-form-section-body">
                <div class="erp-notice">Sân kế thừa giờ mở cửa từ chi nhánh. Các khoảng bảo trì được quản lý tại màn Bảo trì sân.</div>
                <?php if ($isEdit): ?>
                    <div class="mt-3"><a class="erp-btn" href="/admin/courts/maintenance/<?= $court->id ?>"><i class="bi bi-tools"></i> Mở lịch bảo trì</a></div>
                <?php endif; ?>
            </div>
        </section>

        <section class="erp-form-section">
            <div class="erp-form-section-header"><h2>Hình ảnh</h2></div>
            <div class="erp-form-section-body">
                <div class="erp-uploader">
                    <i class="bi bi-cloud-upload fs-2"></i>
                    <div class="fw-bold">Kéo thả ảnh sân hoặc bấm để tải lên</div>
                    <div class="erp-muted">Hỗ trợ JPG, PNG, WebP. Có thể tích hợp upload AJAX hiện có khi cần.</div>
                </div>
            </div>
        </section>

        <section class="erp-form-section">
            <div class="erp-form-section-header"><h2>Cấu hình trạng thái</h2></div>
            <div class="erp-form-section-body erp-form-grid">
                <div class="erp-field">
                    <label>Trạng thái <span class="erp-required">*</span></label>
                    <select name="status" class="erp-select" required>
                        <?php foreach (['available' => 'Trống', 'occupied' => 'Đang dùng', 'maintenance' => 'Bảo trì', 'inactive' => 'Ngưng dùng'] as $value => $label): ?>
                            <option value="<?= $value ?>" <?= old('status', $court->status ?? 'available') === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="erp-field">
                    <label>Ghi chú vận hành</label>
                    <input class="erp-control" value="<?= $isEdit ? 'Sân đang vận hành ổn định' : '' ?>" placeholder="Ví dụ: ưu tiên dùng cho giải đấu">
                </div>
            </div>
            <div class="erp-sticky-save">
                <a href="/admin/courts" class="erp-btn">Hủy</a>
                <button class="erp-btn">Lưu nháp</button>
                <button class="erp-btn erp-btn-primary"><i class="bi bi-save"></i> Lưu sân</button>
            </div>
        </section>
    </form>

    <aside class="erp-dashboard-stack">
        <section class="erp-card">
            <div class="erp-card-header"><strong>Tóm tắt sân</strong><?= renderStatusBadge(old('status', $court->status ?? 'available'), 'court') ?></div>
            <div class="erp-card-body erp-info-list">
                <div class="erp-info-row"><span>Mã sân</span><strong><?= esc(old('code', $court->code ?? 'Chưa có')) ?></strong></div>
                <div class="erp-info-row"><span>Loại</span><strong><?= esc($court->court_type_name_vi ?? 'Pickleball') ?></strong></div>
                <div class="erp-info-row"><span>Trong nhà</span><strong><?= old('is_indoor', $court->is_indoor ?? 0) ? 'Có' : 'Không' ?></strong></div>
                <div class="erp-info-row"><span>Booking hôm nay</span><strong>0</strong></div>
            </div>
        </section>
        <section class="erp-card">
            <div class="erp-card-header"><strong>Quy tắc vận hành</strong></div>
            <div class="erp-card-body">
                <div class="erp-notice mb-2">Sân `inactive` hoặc `maintenance` sẽ không xuất hiện trong danh sách sân trống khi tạo booking.</div>
                <div class="erp-notice">Booking vẫn phải qua kiểm tra bảo trì, giờ mở cửa chi nhánh và dynamic pricing.</div>
            </div>
        </section>
    </aside>
</div>
<?= $this->endSection() ?>
