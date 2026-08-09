<div class="erp-empty py-2">
    <div class="erp-empty-icon"><i class="bi bi-grid-3x3-gap"></i></div>
    <h3><?= esc($court->code) ?> - <?= esc($court->getName()) ?></h3>
    <?= renderStatusBadge($court->status, 'court') ?>
</div>
<div class="erp-info-list">
    <div class="erp-info-row"><span>Loại sân</span><strong><?= esc($court->court_type_name_vi ?? $court->court_type_id) ?></strong></div>
    <div class="erp-info-row"><span>Tầng</span><strong><?= esc($court->floor ?? 1) ?></strong></div>
    <div class="erp-info-row"><span>Trong nhà</span><strong><?= $court->is_indoor ? 'Có' : 'Không' ?></strong></div>
    <div class="erp-info-row"><span>Tiện ích</span><strong><?= $court->has_light ? 'Đèn ' : '' ?><?= $court->has_fan ? 'Quạt ' : '' ?><?= $court->has_camera ? 'Camera' : '' ?></strong></div>
</div>
<div class="erp-section-title mt-3"><h3>Rule giá đang áp dụng</h3></div>
<div class="erp-calendar-strip">
    <?php foreach ($rules as $rule): ?>
        <div class="erp-calendar-item">
            <strong>#<?= (int) $rule->priority ?></strong>
            <div><strong><?= esc($rule->name_vi) ?></strong><div class="erp-muted"><?= format_money($rule->price_amount) ?><?= $rule->price_type === 'hourly' ? '/giờ' : '' ?></div></div>
            <?= renderStatusBadge($rule->status) ?>
        </div>
    <?php endforeach; ?>
    <?php if (empty($rules)): ?><div class="erp-empty py-3">Chưa có rule giá.</div><?php endif; ?>
</div>
