<?= $this->extend('layouts/admin_master') ?>
<?= $this->section('content') ?>
<div class="erp-action-bar">
    <div>
        <a href="/admin/facilities" class="erp-btn"><i class="bi bi-arrow-left"></i> Cụm sân</a>
        <span class="erp-muted ms-2">Quản lý CLB tại <?= esc($facility->name_vi ?? 'cụm sân') ?></span>
    </div>
    <a href="/admin/clubs/create" class="erp-btn erp-btn-primary"><i class="bi bi-plus-lg"></i> Tạo CLB mới</a>
</div>

<div class="erp-grid-2">
    <section class="erp-card">
        <div class="erp-card-header"><strong>Gán CLB vào cụm sân</strong></div>
        <div class="erp-card-body">
            <form method="post" action="/admin/facilities/clubs/<?= (int) $facility->id ?>/assign" class="erp-form">
                <?= csrf_field() ?>
                <div class="erp-field"><label>CLB <span class="erp-required">*</span></label><select name="club_id" class="erp-select" required><option value="">Chọn CLB</option><?php foreach ($clubs as $club): ?><option value="<?= (int) $club->id ?>"><?= esc($club->name_vi) ?><?= $club->name_en ? ' · ' . esc($club->name_en) : '' ?></option><?php endforeach; ?></select></div>
                <label class="erp-switch"><input type="checkbox" name="is_primary" value="1"> CLB vận hành chính</label>
                <div class="erp-field"><label>Ngày bắt đầu</label><input class="erp-control" type="date" name="start_date" placeholder="YYYY-MM-DD"></div>
                <div class="erp-field"><label>Ngày kết thúc</label><input class="erp-control" type="date" name="end_date" placeholder="YYYY-MM-DD"></div>
                <div class="erp-field"><label>Tỷ lệ chia doanh thu (%)</label><input class="erp-control" type="number" name="revenue_share" min="0" max="100" step="0.01" placeholder="VD: 15.5"></div>
                <div class="erp-field"><label>Ưu tiên đặt lịch</label><input class="erp-control" type="number" name="booking_priority" min="0" max="9" placeholder="0..9"></div>
                <div class="erp-field"><label>Sân được phép booking</label><input class="erp-control" name="allowed_courts" placeholder='VD: ["1","2"] hoặc 1,2'></div>
                <div class="erp-field"><label>Khung giờ được phép</label><input class="erp-control" name="allowed_hours" placeholder='VD: ["18:00-22:00"] hoặc 18:00-22:00'></div>
                <div class="erp-field"><label>Ghi chú</label><textarea name="notes" class="erp-control" rows="3" placeholder="Ví dụ: CLB thuê sân cố định buổi tối"></textarea></div>
                <button class="erp-btn erp-btn-primary"><i class="bi bi-link-45deg"></i> Gán CLB</button>
            </form>
        </div>
    </section>
    <section class="erp-card">
        <div class="erp-card-header"><strong>CLB đã gán</strong><span class="erp-chip erp-status-neutral"><?= count($assignedClubs) ?></span></div>
        <div class="erp-card-body">
            <?php if ($assignedClubs): foreach ($assignedClubs as $assignment): ?>
                <div class="d-flex align-items-center justify-content-between border-bottom py-2">
                    <div>
                        <strong><?= esc($assignment->name_vi) ?></strong>
                        <small class="d-block erp-muted"><?= $assignment->is_primary ? 'CLB chính' : 'CLB liên kết' ?> · <?= esc($assignment->status) ?></small>
                        <div class="d-block erp-muted">Hiệu lực: <?= esc($assignment->start_date ?: 'Không giới hạn') ?> ~ <?= esc($assignment->end_date ?: 'Không giới hạn') ?></div>
                        <div class="d-block erp-muted">Chia sẻ: <?= esc($assignment->revenue_share ?? 0) ?>% · Ưu tiên: <?= esc((int) ($assignment->booking_priority ?? 0)) ?></div>
                        <div class="d-block erp-muted">Sân: <?= esc((string) ($assignment->allowed_courts ?? 'Tất cả')) ?> · Giờ: <?= esc((string) ($assignment->allowed_hours ?? 'Tất cả')) ?></div>
                    </div>
                    <form method="post" action="/admin/facilities/clubs/<?= (int) $facility->id ?>/remove/<?= (int) $assignment->id ?>" class="d-inline">
                        <?= csrf_field() ?>
                        <button type="submit" class="erp-btn erp-btn-icon" data-confirm="Bỏ gán CLB này khỏi cụm sân?" title="Bỏ gán"><i class="bi bi-x-lg"></i></button>
                    </form>
                </div>
            <?php endforeach; else: ?><div class="erp-empty py-4"><p>Chưa có CLB được gán. Hãy chọn CLB để dữ liệu được hiển thị trong quản lý sân.</p></div><?php endif; ?>
        </div>
    </section>
</div>
<?= $this->endSection() ?>
