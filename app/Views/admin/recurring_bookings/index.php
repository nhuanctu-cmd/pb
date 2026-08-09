<?= $this->extend('layouts/admin_master') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 mb-1">Lịch đặt sân định kỳ</h1>
        <p class="text-muted mb-0">Tạo lịch lặp, loại trừ ngày nghỉ và sinh booking đến hạn.</p>
    </div>
    <form method="post" action="<?= base_url('admin/recurring-bookings/process-due') ?>">
        <?= csrf_field() ?>
        <button class="btn btn-outline-primary"><i class="bi bi-arrow-repeat"></i> Xử lý occurrence đến hạn</button>
    </form>
</div>

<div class="row g-4">
    <div class="col-xl-5">
        <div class="card shadow-sm">
            <div class="card-header">Tạo lịch định kỳ</div>
            <div class="card-body">
                <form method="post" action="<?= base_url('admin/recurring-bookings/store') ?>">
                    <?= csrf_field() ?>
                    <div class="mb-3"><label class="form-label">Tên lịch</label><input name="name" class="form-control" required value="<?= esc(old('name')) ?>"></div>
                    <div class="row g-2">
                        <div class="col-6"><label class="form-label">Chi nhánh</label><select name="branch_id" class="form-select" required><option value="">-- chọn --</option><?php foreach ($branches as $branch): ?><option value="<?= (int) $branch->id ?>" <?= (int) old('branch_id') === (int) $branch->id ? 'selected' : '' ?>><?= esc($branch->name) ?></option><?php endforeach; ?></select></div>
                        <div class="col-6"><label class="form-label">Sân</label><select name="court_id" class="form-select" required><option value="">-- chọn --</option><?php foreach ($courts as $court): ?><option value="<?= (int) $court->id ?>"><?= esc(($branchNames[(int) $court->branch_id] ?? '') . ' / ' . $court->code . ' - ' . ($court->name_vi ?? '')) ?></option><?php endforeach; ?></select></div>
                    </div>
                    <div class="mb-3 mt-3"><label class="form-label">Người chơi</label><select name="player_id" class="form-select"><option value="">Khách vãng lai</option><?php foreach ($players as $player): ?><option value="<?= (int) $player->id ?>"><?= esc($player->full_name . ' - ' . ($player->phone ?? '')) ?></option><?php endforeach; ?></select></div>
                    <div class="row g-2 mb-3"><div class="col-6"><label class="form-label">Từ ngày</label><input type="date" name="start_date" class="form-control" required></div><div class="col-6"><label class="form-label">Đến ngày</label><input type="date" name="end_date" class="form-control" required></div></div>
                    <div class="row g-2 mb-3"><div class="col-6"><label class="form-label">Bắt đầu</label><input type="time" name="start_time" class="form-control" required></div><div class="col-6"><label class="form-label">Kết thúc</label><input type="time" name="end_time" class="form-control" required></div></div>
                    <div class="row g-2 mb-3"><div class="col-8"><label class="form-label">Kiểu lặp</label><select name="repeat_type" class="form-select"><option value="weekly">Hàng tuần</option><option value="daily">Hàng ngày</option><option value="biweekly">Hai tuần</option><option value="monthly">Hàng tháng</option><option value="custom">Tùy chọn ngày trong tuần</option></select></div><div class="col-4"><label class="form-label">Khoảng</label><input type="number" name="repeat_interval" min="1" max="52" value="1" class="form-control"></div></div>
                    <div class="mb-3"><label class="form-label">Ngày trong tuần (chỉ dùng cho kiểu tùy chọn)</label><div class="d-flex flex-wrap gap-3">
                        <?php foreach ([1 => 'T2', 2 => 'T3', 3 => 'T4', 4 => 'T5', 5 => 'T6', 6 => 'T7', 0 => 'CN'] as $day => $label): ?>
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="repeat_days[]" value="<?= $day ?>" id="repeat-day-<?= $day ?>"><label class="form-check-label" for="repeat-day-<?= $day ?>"><?= $label ?></label></div>
                        <?php endforeach; ?>
                    </div></div>
                    <div class="mb-3"><label class="form-label">Ngày loại trừ</label><input name="exclude_dates" class="form-control" placeholder="YYYY-MM-DD, YYYY-MM-DD"><div class="form-text">Các ngày cách nhau bằng dấu phẩy.</div></div>
                    <button class="btn btn-primary w-100"><i class="bi bi-calendar-plus"></i> Tạo lịch</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-xl-7">
        <div class="card shadow-sm"><div class="card-header">Danh sách lịch</div><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Tên</th><th>Sân</th><th>Tiếp theo</th><th>Tiến độ</th><th>Trạng thái</th><th></th></tr></thead><tbody><?php if (!$templates): ?><tr><td colspan="6" class="text-center text-muted py-4">Chưa có lịch định kỳ.</td></tr><?php endif; ?><?php foreach ($templates as $template): ?><tr><td><?= esc($template->name) ?></td><td><?= (int) $template->court_id ?></td><td><?= esc($template->next_occurrence ?? '-') ?></td><td><?= (int) $template->completed_occurrences ?>/<?= (int) $template->total_occurrences ?></td><td><span class="badge text-bg-<?= $template->status === 'active' ? 'success' : ($template->status === 'paused' ? 'warning' : 'secondary') ?>"><?= esc($template->status) ?></span></td><td><form method="post" action="<?= base_url('admin/recurring-bookings/status/' . $template->id) ?>" class="d-flex gap-1"><?= csrf_field() ?><select name="status" class="form-select form-select-sm"><option value="active">active</option><option value="paused">paused</option><option value="cancelled">cancelled</option></select><button class="btn btn-sm btn-outline-secondary">Lưu</button></form></td></tr><?php endforeach; ?></tbody></table></div></div>
    </div>
</div>

<?= $this->endSection() ?>
