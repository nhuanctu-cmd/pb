<?= $this->extend('layouts/admin_master') ?>

<?= $this->section('content') ?>
<div class="erp-grid-2">
    <form method="post" class="erp-form">
        <?= csrf_field() ?>
        <section class="erp-form-section">
            <div class="erp-form-section-header">
                <h2>Test giá sân</h2>
                <div class="erp-muted">Kiểm thử rule trước khi áp dụng vào booking thực tế.</div>
            </div>
            <div class="erp-form-section-body erp-form-grid">
                <div class="erp-field">
                    <label>Chi nhánh <span class="erp-required">*</span></label>
                    <select name="branch_id" class="erp-select" required>
                        <option value="">Chọn chi nhánh</option>
                        <?php foreach ($branches as $branch): ?>
                            <option value="<?= $branch->id ?>" <?= (string) ($input['branch_id'] ?? '') === (string) $branch->id ? 'selected' : '' ?>><?= esc($branch->getName()) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="erp-field">
                    <label>Sân <span class="erp-required">*</span></label>
                    <select name="court_id" class="erp-select" required>
                        <option value="">Chọn sân</option>
                        <?php foreach ($courts as $court): ?>
                            <option value="<?= $court->id ?>" <?= (string) ($input['court_id'] ?? '') === (string) $court->id ? 'selected' : '' ?>><?= esc($court->code . ' - ' . $court->getName()) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="erp-field"><label>Ngày</label><input type="date" name="date" class="erp-control" value="<?= esc($input['date']) ?>" required></div>
                <div class="erp-field"><label>Bắt đầu</label><input type="time" name="start_time" class="erp-control" value="<?= esc(substr($input['start_time'], 0, 5)) ?>" required></div>
                <div class="erp-field"><label>Kết thúc</label><input type="time" name="end_time" class="erp-control" value="<?= esc(substr($input['end_time'], 0, 5)) ?>" required></div>
                <div class="erp-field">
                    <label>Hội viên / khách lẻ</label>
                    <select name="player_id" class="erp-select">
                        <option value="">Khách lẻ</option>
                        <?php foreach ($players as $player): ?>
                            <option value="<?= $player->id ?>" <?= (string) ($input['player_id'] ?? '') === (string) $player->id ? 'selected' : '' ?>><?= esc($player->full_name) ?> - <?= esc($player->player_code) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="erp-sticky-save">
                <a href="/admin/pricing-rules" class="erp-btn">Rule giá</a>
                <button class="erp-btn erp-btn-primary"><i class="bi bi-calculator"></i> Tính giá</button>
            </div>
        </section>
    </form>

    <aside class="erp-dashboard-stack">
        <section class="erp-card">
            <div class="erp-card-header"><strong>Kết quả test</strong><span class="erp-chip erp-status-info">PricingService</span></div>
            <div class="erp-card-body">
                <?php if ($result): ?>
                    <div class="erp-info-list">
                        <div class="erp-info-row"><span>Giá cuối</span><strong><?= format_money((float) $result['final_price']) ?></strong></div>
                        <div class="erp-info-row"><span>Giá gốc</span><strong><?= format_money((float) $result['base_price']) ?></strong></div>
                        <div class="erp-info-row"><span>Thời lượng</span><strong><?= (int) $result['duration_minutes'] ?> phút</strong></div>
                        <div class="erp-info-row"><span>Log ID</span><strong>#<?= esc($result['log_id']) ?></strong></div>
                    </div>
                    <div class="erp-table-wrap mt-3">
                        <table class="erp-table">
                            <thead><tr><th>Rule</th><th>Ưu tiên</th><th>Giá tính</th><th>Chọn</th></tr></thead>
                            <tbody>
                            <?php foreach ($result['breakdown'] as $row): ?>
                                <tr>
                                    <td><?= esc($row['name_vi']) ?></td>
                                    <td>#<?= esc($row['priority']) ?></td>
                                    <td><?= format_money((float) $row['calculated_price']) ?></td>
                                    <td><?= $row['selected'] ? renderStatusBadge('active') : '<span class="erp-muted">-</span>' ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="erp-mobile-list mt-3">
                        <?php foreach ($result['breakdown'] as $row): ?>
                            <div class="erp-mobile-card">
                                <strong><?= esc($row['name_vi']) ?></strong>
                                <div class="erp-muted">Ưu tiên #<?= esc($row['priority']) ?> · <?= format_money((float) $row['calculated_price']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="erp-empty">
                        <div class="erp-empty-icon"><i class="bi bi-calculator"></i></div>
                        Nhập thông tin để xem giá cuối, rule áp dụng và log tính giá.
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </aside>
</div>
<?= $this->endSection() ?>
