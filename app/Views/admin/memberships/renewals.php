<?= $this->extend('admin/layouts/main') ?>
<?= $this->section('content') ?>

<?php
$packages = $packages ?? [];
$statusFilter = $statusFilter ?? '';
$packageFilter = (int) ($packageFilter ?? 0);
$days = (int) ($days ?? 30);
$search = (string) ($search ?? '');
$statusFilter = (string) ($statusFilter ?? '');
$statusQuery = $statusFilter === '' ? '' : $statusFilter;
$historyMembershipId = (int) ($historyMembershipId ?? 0);
$reminderTemplates = $reminderTemplates ?? [];
$reminderHistory = $reminderHistory ?? [];
$exportQuery = http_build_query([
    'days' => $days,
    'status' => $statusQuery,
    'package_id' => $packageFilter,
    'q' => $search,
    'history_membership_id' => $historyMembershipId,
]);
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">Gia hạn hội viên</h1>
        <div class="text-muted">Danh sách hội viên cần chăm sóc, gia hạn và theo dõi rời thẻ.</div>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary" onclick="window.print()">In danh sách</button>
        <a class="btn btn-outline-primary" href="/admin/memberships/renewals/export?<?= $exportQuery ?>">Xuất CSV</a>
    </div>
</div>

<form class="card card-body mb-3" method="get">
    <div class="row g-2 align-items-end">
        <div class="col-md-2">
            <label class="form-label">Trong vòng</label>
            <select class="form-select" name="days">
                <?php foreach ([7, 14, 30, 60, 90] as $d): ?>
                    <option value="<?= $d ?>" <?= $days === $d ? 'selected' : '' ?>><?= $d ?> ngày</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Trạng thái</label>
            <select class="form-select" name="status">
                <option value="">Tất cả</option>
                <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="expired" <?= $statusFilter === 'expired' ? 'selected' : '' ?>>Expired</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Gói</label>
            <select class="form-select" name="package_id">
                <option value="0">Tất cả gói</option>
                <?php foreach ($packages as $pkg): ?>
                    <option value="<?= (int) $pkg->id ?>" <?= $packageFilter === (int) $pkg->id ? 'selected' : '' ?>>
                        <?= esc($pkg->name_vi) ?> - <?= number_format((float) $pkg->price, 0, ',', '.') ?>đ
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Tìm kiếm</label>
            <input class="form-control" type="text" name="q" value="<?= esc($search) ?>" placeholder="Tên / SĐT / mã hội viên">
        </div>
        <div class="col-md-2">
            <button class="btn btn-outline-primary w-100">Lọc</button>
        </div>
    </div>
</form>

<form method="post" action="/admin/memberships/renewals/bulk">
    <?= csrf_field() ?>
    <input type="hidden" name="filter_days" value="<?= (int) $days ?>">
    <input type="hidden" name="filter_status" value="<?= esc($statusFilter) ?>">
    <input type="hidden" name="filter_package_id" value="<?= (int) $packageFilter ?>">
    <input type="hidden" name="filter_q" value="<?= esc($search) ?>">
    <input type="hidden" name="filter_history_membership_id" value="<?= (int) $historyMembershipId ?>">
    <div class="card shadow-sm mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Có <?= (int) count($renewals ?? []) ?> hội viên trong danh sách lọc</span>
            <div class="d-flex gap-2">
                <select class="form-select form-select-sm" name="package_id">
                    <option value="0">Giữ nguyên gói cũ</option>
                    <?php foreach ($packages as $pkg): ?>
                        <option value="<?= (int) $pkg->id ?>">Gia hạn sang: <?= esc($pkg->name_vi) ?></option>
                    <?php endforeach; ?>
                </select>
                <select class="form-select form-select-sm" name="reminder_channel">
                    <option value="sms">SMS</option>
                    <option value="zalo">Zalo</option>
                    <option value="email">Email</option>
                </select>
                <input class="form-control form-control-sm" type="text" name="recipient" placeholder="SĐT/email thay thế (tuỳ kênh)">
                <button class="btn btn-sm btn-info" type="submit" formaction="/admin/memberships/reminders/run">Nhắc nhở hàng loạt</button>
                <button class="btn btn-sm btn-success" type="submit">Gia hạn hàng loạt</button>
            </div>
        </div>
        <div class="px-3 pb-2">
            <div class="row g-2 align-items-center">
                <div class="col-md-3">
                    <label class="form-label small mb-1">Mẫu nhắc nhở (tùy chọn)</label>
                    <textarea class="form-control form-control-sm" name="message_template" rows="2" placeholder="<?= esc($reminderTemplates['sms'] ?? '') ?>"></textarea>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Chiến dịch SMS (test mode)</label><br>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="test_mode" id="test_mode_bulk" value="1">
                        <label class="form-check-label" for="test_mode_bulk">Gửi thử (không ghi lịch sử thực tế)</label>
                    </div>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 36px"><input type="checkbox" id="selectAllRenewals"></th>
                        <th>Khách hàng</th>
                        <th>Gói</th>
                        <th>Hết hạn</th>
                        <th>Còn lại</th>
                        <th>Giá</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($renewals)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">Không có hội viên cần gia hạn trong khoảng này.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($renewals as $row): ?>
                        <tr>
                            <td><input type="checkbox" name="membership_ids[]" value="<?= (int) $row->id ?>"></td>
                            <td>
                                <strong><?= esc($row->full_name) ?></strong><br>
                                <small class="text-muted"><?= esc($row->player_code) ?> | <?= esc($row->phone) ?></small>
                            </td>
                            <td><?= esc($row->package_name_vi ?? $row->package_name_en) ?></td>
                            <td><?= esc($row->end_date) ?></td>
                            <td><span class="badge <?= ((int) $row->remaining_days < 0 ? 'text-bg-danger' : ((int) $row->remaining_days <= 7 ? 'text-bg-warning' : 'text-bg-light')) ?>"><?= (int) $row->remaining_days ?> ngày</span></td>
                            <td><?= number_format((float) $row->price, 0, ',', '.') ?>đ</td>
                            <td class="text-end">
                                <form method="post" action="/admin/memberships/renew/<?= (int) $row->id ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="filter_days" value="<?= (int) $days ?>">
                                    <input type="hidden" name="filter_status" value="<?= esc($statusFilter) ?>">
                                    <input type="hidden" name="filter_package_id" value="<?= (int) $packageFilter ?>">
                                    <input type="hidden" name="filter_q" value="<?= esc($search) ?>">
                                    <input type="hidden" name="filter_history_membership_id" value="<?= (int) $historyMembershipId ?>">
                                    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-repeat me-1"></i>Gia hạn</button>
                                </form>
                                <form method="post" action="/admin/memberships/reminder/<?= (int) $row->id ?>" class="d-inline ms-1">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="filter_days" value="<?= (int) $days ?>">
                                    <input type="hidden" name="filter_status" value="<?= esc($statusFilter) ?>">
                                    <input type="hidden" name="filter_package_id" value="<?= (int) $packageFilter ?>">
                                    <input type="hidden" name="filter_q" value="<?= esc($search) ?>">
                                    <input type="hidden" name="filter_history_membership_id" value="<?= (int) $historyMembershipId ?>">
                                    <input type="hidden" name="history_membership_id" value="<?= (int) $row->id ?>">
                                    <input type="hidden" name="channel" value="sms">
                                    <button class="btn btn-sm btn-outline-warning"><i class="bi bi-bell me-1"></i>Nhắc SMS</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</form>

<div class="card shadow-sm mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Lịch sử thao tác hội viên</span>
        <span>Hiển thị <?= count($reminderHistory) ?> bản ghi gần nhất</span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Thời gian</th>
                    <th>Khách</th>
                    <th>Hành động</th>
                    <th>Gói trước → Gói sau</th>
                    <th>Ghi chú</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reminderHistory)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-3">Chưa có lịch sử thao tác.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($reminderHistory as $row): ?>
                    <tr>
                        <td><?= esc($row->created_at ?? '-') ?></td>
                        <td>
                            <strong><?= esc($row->player_name ?? '-') ?></strong><br>
                            <small class="text-muted"><?= esc($row->player_code ?? '-') ?> | <?= esc($row->player_phone ?? '-') ?></small>
                        </td>
                        <td><span class="badge text-bg-light"><?= esc($row->action) ?></span></td>
                        <td>
                            <?= esc($row->package_before_name_vi ?? $row->package_before_name_en ?? '-') ?>
                            &rarr;
                            <?= esc($row->package_after_name_vi ?? $row->package_after_name_en ?? '-') ?>
                        </td>
                        <td><?= esc($row->notes ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.getElementById('selectAllRenewals')?.addEventListener('change', function () {
    const checked = this.checked;
    document.querySelectorAll('input[name="membership_ids[]"]').forEach(input => {
        input.checked = checked;
    });
});
</script>

<?= $this->endSection() ?>
