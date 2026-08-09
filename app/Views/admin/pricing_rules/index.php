<?= $this->extend('layouts/admin_master') ?>

<?= $this->section('content') ?>
<?php
$total = count($rules ?? []);
$active = count(array_filter($rules ?? [], fn ($r) => $r->status === 'active'));
$peak = count(array_filter($rules ?? [], fn ($r) => str_contains(strtolower(($r->code ?? '') . ' ' . ($r->name_vi ?? '')), 'peak') || str_contains($r->name_vi ?? '', 'cao điểm')));
$member = count(array_filter($rules ?? [], fn ($r) => $r->member_price_amount !== null));
?>

<div class="erp-action-bar">
    <div class="d-flex gap-2 flex-wrap">
        <a href="/admin/pricing-rules/create" class="erp-btn erp-btn-primary"><i class="bi bi-plus-lg"></i> Thêm rule</a>
        <button class="erp-btn"><i class="bi bi-file-earmark-excel"></i> Xuất Excel</button>
        <button class="erp-btn" onclick="location.reload()"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
    </div>
    <div class="erp-muted">Rule ưu tiên cao nhất sẽ được chọn khi nhiều rule cùng khớp.</div>
</div>

<?= view('layouts/partials/stat_cards', ['stats' => [
    ['label' => 'Tổng rule', 'value' => (string) $total, 'trend' => 'Trong tenant hiện tại', 'icon' => 'bi-sliders'],
    ['label' => 'Rule đang bật', 'value' => (string) $active, 'trend' => 'Sẵn sàng áp dụng', 'icon' => 'bi-toggle-on'],
    ['label' => 'Rule cao điểm', 'value' => (string) $peak, 'trend' => 'Khung giờ demand cao', 'icon' => 'bi-lightning-charge'],
    ['label' => 'Rule hội viên', 'value' => (string) $member, 'trend' => 'Có giá member', 'icon' => 'bi-award'],
]]) ?>

<?= view('layouts/partials/filter_bar', ['left' => '
    <select name="branch_id" class="erp-select" style="width:200px"><option value="">Tất cả chi nhánh</option>' .
        implode('', array_map(fn ($b) => '<option value="' . $b->id . '" ' . (($filters['branch_id'] ?? '') == $b->id ? 'selected' : '') . '>' . esc($b->name) . '</option>', $branches ?? [])) .
    '</select>
    <select name="court_type_id" class="erp-select" style="width:190px"><option value="">Tất cả loại sân</option>' .
        implode('', array_map(fn ($t) => '<option value="' . $t->id . '" ' . (($filters['court_type_id'] ?? '') == $t->id ? 'selected' : '') . '>' . esc($t->name_vi) . '</option>', $courtTypes ?? [])) .
    '</select>
    <select name="status" class="erp-select" style="width:160px"><option value="">Tất cả trạng thái</option><option value="active">Đang bật</option><option value="inactive">Đang tắt</option></select>
    <select class="erp-select" style="width:170px"><option>Tất cả loại rule</option><option>Cao điểm</option><option>Hội viên</option><option>Ngày lễ</option></select>
']) ?>

<div class="erp-grid-2">
    <section>
        <div class="erp-table-wrap">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>Tên rule</th>
                        <th>Phạm vi áp dụng</th>
                        <th>Giờ áp dụng</th>
                        <th>Giá</th>
                        <th>Ưu tiên</th>
                        <th>Trạng thái</th>
                        <th class="col-actions">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rules ?? [] as $rule): ?>
                    <tr>
                        <td><strong><?= esc($rule->name_vi) ?></strong><div class="erp-muted"><?= esc($rule->code ?: $rule->name_en) ?></div></td>
                        <td>
                            <div>Chi nhánh: <strong><?= $rule->branch_id ?: 'Tất cả' ?></strong></div>
                            <div class="erp-muted">Loại sân: <?= $rule->court_type_id ?: 'Tất cả' ?> · Sân: <?= $rule->court_id ?: 'Tất cả' ?></div>
                        </td>
                        <td>
                            <strong><?= esc(substr((string) $rule->start_time, 0, 5) ?: '--:--') ?> - <?= esc(substr((string) $rule->end_time, 0, 5) ?: '--:--') ?></strong>
                            <div class="erp-muted">Thứ <?= esc($rule->day_of_week ?: 'mọi ngày') ?><?= $rule->is_holiday ? ' · Ngày lễ' : '' ?></div>
                        </td>
                        <td>
                            <strong><?= format_money($rule->price_amount) ?><?= $rule->price_type === 'hourly' ? '/giờ' : '' ?></strong>
                            <?php if ($rule->member_price_amount !== null): ?><div class="text-success">HV: <?= format_money($rule->member_price_amount) ?></div><?php endif; ?>
                        </td>
                        <td><span class="erp-status erp-status-dark">#<?= (int) $rule->priority ?></span></td>
                        <td><?= renderStatusBadge($rule->status) ?></td>
                        <td class="col-actions">
                            <a class="erp-btn erp-btn-icon" href="/admin/pricing-rules/edit/<?= $rule->id ?>"><i class="bi bi-pencil"></i></a>
                            <a class="erp-btn erp-btn-icon" href="/admin/pricing-rules/toggle/<?= $rule->id ?>"><i class="bi bi-power"></i></a>
                            <a class="erp-btn erp-btn-icon" href="/admin/pricing-rules/delete/<?= $rule->id ?>" data-confirm="Xóa rule giá này?"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($rules)): ?><tr><td colspan="7"><div class="erp-empty">Chưa có rule giá động.</div></td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (! empty($rules)): ?>
            <div class="erp-mobile-list">
                <?php foreach ($rules as $rule): ?>
                    <article class="erp-mobile-card">
                        <div class="d-flex justify-content-between gap-2 mb-2">
                            <div>
                                <strong><?= esc($rule->name_vi) ?></strong>
                                <div class="erp-muted"><?= esc($rule->code ?: $rule->name_en) ?></div>
                            </div>
                            <?= renderStatusBadge($rule->status) ?>
                        </div>
                        <div class="erp-info-list">
                            <div class="erp-info-row"><span>Phạm vi</span><strong>CN <?= $rule->branch_id ?: 'Tất cả' ?> · Sân <?= $rule->court_id ?: 'Tất cả' ?></strong></div>
                            <div class="erp-info-row"><span>Giờ</span><strong><?= esc(substr((string) $rule->start_time, 0, 5) ?: '--:--') ?> - <?= esc(substr((string) $rule->end_time, 0, 5) ?: '--:--') ?></strong></div>
                            <div class="erp-info-row"><span>Giá</span><strong><?= format_money($rule->price_amount) ?><?= $rule->price_type === 'hourly' ? '/giờ' : '' ?></strong></div>
                            <div class="erp-info-row"><span>Ưu tiên</span><span class="erp-status erp-status-dark">#<?= (int) $rule->priority ?></span></div>
                        </div>
                        <div class="d-flex gap-2 flex-wrap mt-3">
                            <a class="erp-btn" href="/admin/pricing-rules/edit/<?= $rule->id ?>"><i class="bi bi-pencil"></i> Sửa</a>
                            <a class="erp-btn" href="/admin/pricing-rules/toggle/<?= $rule->id ?>"><i class="bi bi-power"></i> Bật/tắt</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <section class="erp-card mt-3">
            <div class="erp-card-header"><strong>Log tính giá gần đây</strong></div>
            <div class="erp-card-body erp-calendar-strip">
                <?php foreach ($logs ?? [] as $log): ?>
                    <div class="erp-calendar-item">
                        <strong>#<?= (int) $log->id ?></strong>
                        <div><strong>Sân #<?= esc($log->court_id) ?></strong><div class="erp-muted"><?= esc($log->created_at) ?> · <?= esc($log->matched_rule_ids) ?></div></div>
                        <strong><?= format_money($log->final_price) ?></strong>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($logs)): ?><div class="erp-empty py-3">Chưa có log.</div><?php endif; ?>
            </div>
        </section>
    </section>

    <aside class="erp-dashboard-stack">
        <section class="erp-card">
            <div class="erp-card-header"><strong>Test pricing</strong><span class="erp-chip erp-status-info">Realtime</span></div>
            <div class="erp-card-body">
                <div class="erp-form-grid">
                    <div class="erp-field">
                        <label>Chi nhánh</label>
                        <select id="pricingBranch" class="erp-select">
                            <?php foreach ($branches ?? [] as $branch): ?><option value="<?= $branch->id ?>"><?= esc($branch->name) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="erp-field">
                        <label>Sân</label>
                        <select id="pricingCourt" class="erp-select">
                            <?php foreach ($courts ?? [] as $court): ?><option value="<?= $court->id ?>"><?= esc($court->code) ?> - <?= esc($court->getName()) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="erp-field"><label>Ngày</label><input id="pricingDate" type="date" class="erp-control" value="<?= date('Y-m-d') ?>"></div>
                    <div class="erp-field"><label>Bắt đầu</label><input id="pricingStart" type="time" class="erp-control" value="18:00"></div>
                    <div class="erp-field"><label>Kết thúc</label><input id="pricingEnd" type="time" class="erp-control" value="19:00"></div>
                    <div class="erp-field">
                        <label>Hội viên</label>
                        <select id="pricingPlayer" class="erp-select">
                            <option value="">Khách lẻ</option>
                            <?php foreach ($players ?? [] as $player): ?><option value="<?= $player->id ?>"><?= esc($player->full_name) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="button" class="erp-btn erp-btn-primary w-100 mt-3" onclick="testPricingPanel()"><i class="bi bi-calculator"></i> Tính giá</button>
                <div id="pricingResult" class="erp-notice mt-3">Chọn thông tin để xem giá cuối, rule trúng và breakdown.</div>
            </div>
        </section>
    </aside>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
function testPricingPanel() {
    const params = new URLSearchParams({
        branch_id: document.getElementById('pricingBranch').value,
        court_id: document.getElementById('pricingCourt').value,
        date: document.getElementById('pricingDate').value,
        start_time: document.getElementById('pricingStart').value,
        end_time: document.getElementById('pricingEnd').value,
        player_id: document.getElementById('pricingPlayer').value
    });
    const box = document.getElementById('pricingResult');
    box.innerHTML = '<div class="erp-skeleton mb-2"></div><div class="erp-skeleton mb-2"></div><div class="erp-skeleton"></div>';
    fetch('/admin/ops/pricing-test', {method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: params})
        .then(response => response.json())
        .then(data => {
            if (!data.success) { box.innerHTML = data.message || 'Không tính được giá.'; return; }
            const rows = (data.breakdown || []).map(item => `<div class="erp-info-row"><span>${item.name_vi}</span><strong>${Number(item.calculated_price).toLocaleString('vi-VN')}đ</strong></div>`).join('');
            box.innerHTML = `<div class="erp-info-list"><div class="erp-info-row"><span>Giá cuối</span><strong>${data.formatted_price}</strong></div><div class="erp-info-row"><span>Rule trúng</span><strong>${data.selected_rule ? data.selected_rule.name : 'Không có'}</strong></div>${rows}</div>`;
        });
}
</script>
<?= $this->endSection() ?>
