<?= $this->extend('layouts/admin_master') ?>

<?= $this->section('content') ?>
<?php
$selectedDays = $rule && $rule->day_of_week ? explode(',', $rule->day_of_week) : [];
$action = $mode === 'create' ? '/admin/pricing-rules/store' : '/admin/pricing-rules/update/' . $rule->id;
$errors = session('errors') ?? [];
$isEdit = $mode !== 'create';
?>

<?php if (! empty($errors)): ?>
    <div class="erp-notice mb-3" role="alert">
        <strong>Dữ liệu chưa hợp lệ.</strong>
        <div class="erp-muted">Vui lòng kiểm tra các trường được đánh dấu trước khi lưu rule.</div>
    </div>
<?php endif; ?>

<div class="erp-grid-2">
    <form method="post" action="<?= $action ?>" class="erp-form">
        <?= csrf_field() ?>

        <section class="erp-form-section">
            <div class="erp-form-section-header">
                <h2>Thông tin rule</h2>
                <div class="erp-muted">Đặt tên, mã rule và phạm vi áp dụng để vận hành dễ đọc.</div>
            </div>
            <div class="erp-form-section-body erp-form-grid">
                <div class="erp-field">
                    <label>Mã rule</label>
                    <input name="code" class="erp-control" value="<?= esc(old('code', $rule->code ?? '')) ?>" placeholder="PEAK_18_22">
                    <div class="erp-hint">Dùng mã ngắn để tra cứu log giá nhanh hơn.</div>
                </div>
                <div class="erp-field <?= isset($errors['name_vi']) ? 'is-invalid' : '' ?>">
                    <label>Tên rule <span class="erp-required">*</span></label>
                    <input name="name_vi" class="erp-control" required value="<?= esc(old('name_vi', $rule->name_vi ?? '')) ?>" placeholder="Giờ cao điểm buổi tối">
                    <?php if (isset($errors['name_vi'])): ?><div class="erp-validation"><?= esc($errors['name_vi']) ?></div><?php endif; ?>
                </div>
                <div class="erp-field">
                    <label>Tên tiếng Anh</label>
                    <input name="name_en" class="erp-control" value="<?= esc(old('name_en', $rule->name_en ?? '')) ?>" placeholder="Evening peak hours">
                </div>
            </div>
        </section>

        <section class="erp-form-section">
            <div class="erp-form-section-header">
                <h2>Phạm vi áp dụng</h2>
                <div class="erp-muted">Rule càng cụ thể càng dễ kiểm soát khi booking tự tính giá.</div>
            </div>
            <div class="erp-form-section-body erp-form-grid">
                <div class="erp-field">
                    <label>Chi nhánh</label>
                    <select name="branch_id" class="erp-select">
                        <option value="">Tất cả chi nhánh</option>
                        <?php foreach ($branches ?? [] as $branch): ?>
                            <option value="<?= $branch->id ?>" <?= (string) old('branch_id', $rule->branch_id ?? '') === (string) $branch->id ? 'selected' : '' ?>><?= esc($branch->getName()) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="erp-field">
                    <label>Loại sân</label>
                    <select name="court_type_id" class="erp-select">
                        <option value="">Tất cả loại sân</option>
                        <?php foreach ($courtTypes ?? [] as $type): ?>
                            <option value="<?= $type->id ?>" <?= (string) old('court_type_id', $rule->court_type_id ?? '') === (string) $type->id ? 'selected' : '' ?>><?= esc($type->name_vi) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="erp-field">
                    <label>Sân cụ thể</label>
                    <select name="court_id" class="erp-select">
                        <option value="">Tất cả sân</option>
                        <?php foreach ($courts ?? [] as $court): ?>
                            <option value="<?= $court->id ?>" <?= (string) old('court_id', $rule->court_id ?? '') === (string) $court->id ? 'selected' : '' ?>><?= esc($court->code . ' - ' . $court->getName()) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </section>

        <section class="erp-form-section">
            <div class="erp-form-section-header">
                <h2>Lịch áp dụng</h2>
                <div class="erp-muted">Có thể để trống ngày/giờ để rule áp dụng mặc định.</div>
            </div>
            <div class="erp-form-section-body erp-form-grid">
                <div class="erp-field"><label>Từ ngày</label><input type="date" name="start_date" class="erp-control" value="<?= esc(old('start_date', $rule->start_date ?? '')) ?>"></div>
                <div class="erp-field"><label>Đến ngày</label><input type="date" name="end_date" class="erp-control" value="<?= esc(old('end_date', $rule->end_date ?? '')) ?>"></div>
                <div class="erp-field"><label>Giờ bắt đầu</label><input type="time" name="start_time" class="erp-control" value="<?= esc(old('start_time', isset($rule->start_time) ? substr($rule->start_time, 0, 5) : '')) ?>"></div>
                <div class="erp-field"><label>Giờ kết thúc</label><input type="time" name="end_time" class="erp-control" value="<?= esc(old('end_time', isset($rule->end_time) ? substr($rule->end_time, 0, 5) : '')) ?>"></div>
            </div>
            <div class="erp-form-section-body pt-0">
                <label class="d-block fw-bold mb-2">Thứ trong tuần</label>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ([1 => 'T2', 2 => 'T3', 3 => 'T4', 4 => 'T5', 5 => 'T6', 6 => 'T7', 7 => 'CN'] as $day => $label): ?>
                        <label class="erp-btn">
                            <input type="checkbox" name="day_of_week[]" value="<?= $day ?>" <?= in_array((string) $day, $selectedDays, true) ? 'checked' : '' ?>> <?= $label ?>
                        </label>
                    <?php endforeach; ?>
                    <label class="erp-switch"><input type="checkbox" name="is_holiday" value="1" <?= old('is_holiday', $rule->is_holiday ?? 0) ? 'checked' : '' ?>> Rule ngày lễ</label>
                </div>
            </div>
        </section>

        <section class="erp-form-section">
            <div class="erp-form-section-header">
                <h2>Giá & ưu tiên</h2>
                <div class="erp-muted">Booking sẽ lấy rule có độ ưu tiên cao nhất trong danh sách rule khớp.</div>
            </div>
            <div class="erp-form-section-body erp-form-grid">
                <div class="erp-field">
                    <label>Kiểu giá</label>
                    <select name="price_type" class="erp-select">
                        <option value="hourly" <?= old('price_type', $rule->price_type ?? 'hourly') === 'hourly' ? 'selected' : '' ?>>Theo giờ</option>
                        <option value="fixed" <?= old('price_type', $rule->price_type ?? '') === 'fixed' ? 'selected' : '' ?>>Giá cố định</option>
                    </select>
                </div>
                <div class="erp-field <?= isset($errors['price_amount']) ? 'is-invalid' : '' ?>">
                    <label>Giá thường <span class="erp-required">*</span></label>
                    <input type="number" step="1000" name="price_amount" class="erp-control" required value="<?= esc(old('price_amount', $rule->price_amount ?? 0)) ?>">
                    <?php if (isset($errors['price_amount'])): ?><div class="erp-validation"><?= esc($errors['price_amount']) ?></div><?php endif; ?>
                </div>
                <div class="erp-field">
                    <label>Giá hội viên</label>
                    <input type="number" step="1000" name="member_price_amount" class="erp-control" value="<?= esc(old('member_price_amount', $rule->member_price_amount ?? '')) ?>">
                </div>
                <div class="erp-field">
                    <label>Độ ưu tiên</label>
                    <input type="number" name="priority" class="erp-control" value="<?= esc(old('priority', $rule->priority ?? 10)) ?>">
                </div>
                <div class="erp-field">
                    <label>Trạng thái</label>
                    <select name="status" class="erp-select">
                        <option value="active" <?= old('status', $rule->status ?? 'active') === 'active' ? 'selected' : '' ?>>Bật</option>
                        <option value="inactive" <?= old('status', $rule->status ?? '') === 'inactive' ? 'selected' : '' ?>>Tắt</option>
                    </select>
                </div>
            </div>

            <div class="erp-sticky-save">
                <a href="/admin/pricing-rules" class="erp-btn">Hủy</a>
                <button type="button" class="erp-btn">Lưu nháp</button>
                <button class="erp-btn erp-btn-primary"><i class="bi bi-save"></i> Lưu rule</button>
            </div>
        </section>
    </form>

    <aside class="erp-dashboard-stack">
        <section class="erp-card">
            <div class="erp-card-header"><strong>Tóm tắt rule</strong><?= renderStatusBadge(old('status', $rule->status ?? 'active')) ?></div>
            <div class="erp-card-body erp-info-list">
                <div class="erp-info-row"><span>Chế độ</span><strong><?= $isEdit ? 'Cập nhật rule' : 'Tạo rule mới' ?></strong></div>
                <div class="erp-info-row"><span>Giá thường</span><strong><?= format_money(old('price_amount', $rule->price_amount ?? 0)) ?></strong></div>
                <div class="erp-info-row"><span>Giá hội viên</span><strong><?= old('member_price_amount', $rule->member_price_amount ?? '') !== '' ? format_money(old('member_price_amount', $rule->member_price_amount ?? 0)) : 'Chưa đặt' ?></strong></div>
                <div class="erp-info-row"><span>Ưu tiên</span><span class="erp-status erp-status-dark">#<?= esc(old('priority', $rule->priority ?? 10)) ?></span></div>
            </div>
        </section>
        <section class="erp-card">
            <div class="erp-card-header"><strong>Liên kết nghiệp vụ</strong></div>
            <div class="erp-card-body">
                <div class="erp-notice mb-2">Rule được dùng trong `PricingService::getPrice()` khi booking chọn sân và khung giờ.</div>
                <div class="erp-notice">Nếu chọn sân cụ thể, drawer sân sẽ hiển thị rule này trong danh sách giá đang áp dụng.</div>
            </div>
        </section>
    </aside>
</div>
<?= $this->endSection() ?>
