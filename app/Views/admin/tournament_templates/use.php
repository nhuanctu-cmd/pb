<?= $this->extend('layouts/admin_master') ?>
<?= $this->section('styles') ?><link rel="stylesheet" href="<?= esc(asset_url('assets/css/tournament-operations.css')) ?>"><?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
$defaultStart = (string) ($defaultStart ?? date('Y-m-d'));
$defaultEnd = (string) ($defaultEnd ?? $defaultStart);
$datePresets = $datePresets ?? [];
$sourceTournament = $sourceTournament ?? null;
$snapshot = $snapshot ?? [];
?>

<div class="erp-card mb-3">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <h2 class="h4 mb-1"><?= esc($template->name) ?></h2>
            <p class="text-muted mb-0"><?= esc($template->description ?? 'Tạo giải mới nhanh bằng template đã lưu.') ?></p>
            <?php if ($sourceTournament): ?>
                <p class="small text-success mb-0">
                    Nguồn: <a href="/admin/tournaments/show/<?= (int) $sourceTournament->id ?>">#<?= (int) $sourceTournament->id ?> <?= esc($sourceTournament->name_vi) ?></a>
                </p>
            <?php endif; ?>
        </div>
        <span class="badge text-bg-info">Không copy participants/results</span>
    </div>

    <div class="mt-3">
        <div class="small text-muted">
            Template có: <strong><?= count((array) ($snapshot['categories'] ?? [])) ?></strong> hạng mục, Sponsor: <strong><?= count((array) ($snapshot['sponsors'] ?? [])) ?></strong>
        </div>
    </div>
</div>

<div class="erp-card mb-3">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h3 class="h6 m-0">Ngày mẫu nhanh</h3>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary preset-date" data-target="start_date" data-value="<?= esc($datePresets['today'] ?? date('Y-m-d')) ?>">Hôm nay</button>
            <button type="button" class="btn btn-sm btn-outline-secondary preset-date" data-target="start_date" data-value="<?= esc($datePresets['tomorrow'] ?? date('Y-m-d', strtotime('+1 day'))) ?>">Ngày mai</button>
            <button type="button" class="btn btn-sm btn-outline-secondary preset-date" data-target="start_date" data-value="<?= esc($datePresets['nextMonday'] ?? date('Y-m-d', strtotime('next monday'))) ?>">Thứ 2 tuần tới</button>
            <button type="button" class="btn btn-sm btn-outline-secondary preset-date" data-target="start_date" data-value="<?= esc($datePresets['nextSaturday'] ?? date('Y-m-d', strtotime('next saturday'))) ?>">Thứ 7 tuần tới</button>
            <button type="button" class="btn btn-sm btn-outline-secondary preset-date" data-target="start_date" data-value="<?= esc($datePresets['nextWeek'] ?? date('Y-m-d', strtotime('+7 day'))) ?>">+7 ngày</button>
            <button type="button" class="btn btn-sm btn-outline-primary" id="autobuildDates">Đổ theo thời gian mẫu</button>
        </div>
    </div>
</div>

<div class="erp-card">
    <form method="post" action="<?= base_url('admin/tournament-templates/use/' . $template->id) ?>" class="row g-3">
        <?= csrf_field() ?>
        <div class="col-md-8">
            <label class="form-label">Tên giải mới</label>
            <input name="name_vi" class="form-control" value="<?= esc(old('name_vi', $snapshot['name_vi'] ?? '') ?: $suggestedName) ?>" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Tên tiếng Anh</label>
            <input name="name_en" class="form-control" value="<?= esc(old('name_en', $snapshot['name_en'] ?? '')) ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Ngày bắt đầu</label>
            <input id="start-date" type="date" name="start_date" class="form-control" value="<?= esc(old('start_date', $defaultStart)) ?>" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Ngày kết thúc</label>
            <input id="end-date" type="date" name="end_date" class="form-control" value="<?= esc(old('end_date', $defaultEnd)) ?>" required>
        </div>
        <div class="col-md-2">
            <label class="form-label">Trạng thái</label>
            <select name="status" class="form-select" id="templateStatus">
                <option value="draft" <?= old('status') === 'draft' ? 'selected' : '' ?>>Lưu nháp</option>
                <option value="open" <?= old('status') === 'open' || old('status') === '' ? 'selected' : '' ?>>Mở đăng ký ngay</option>
            </select>
        </div>
        <div class="col-md-6 d-flex align-items-end">
            <div class="form-check form-switch me-3">
                <input class="form-check-input" type="checkbox" name="open_registration_now" id="quickOpen" value="1" <?= old('open_registration_now') === '1' ? 'checked' : '' ?>>
                <label class="form-check-label" for="quickOpen">Mở đăng ký ngay khi tạo</label>
            </div>
            <small class="text-muted">Nếu bật, hệ thống tự tạo thời gian mở/đóng đăng ký.</small>
        </div>
        <div class="col-md-6">
            <label class="form-label">Mở đăng ký (tùy chọn)</label>
            <div class="input-group">
                <input type="datetime-local" name="registration_start" class="form-control" value="<?= esc(old('registration_start', '')) ?>">
                <input type="datetime-local" name="registration_end" class="form-control" value="<?= esc(old('registration_end', '')) ?>">
            </div>
            <small class="text-muted">Để trống nếu cần giữ mặc định template hoặc dùng "Mở đăng ký ngay".</small>
        </div>
        <div class="col-12 d-flex gap-2">
            <button class="erp-btn erp-btn-primary"><i class="bi bi-magic"></i> Tạo giải mới</button>
            <a class="erp-btn" href="<?= base_url('admin/tournament-templates') ?>">Huỷ</a>
        </div>
    </form>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    (function () {
        const startInput = document.getElementById('start-date');
        const endInput = document.getElementById('end-date');
        const quickOpen = document.getElementById('quickOpen');
        const status = document.getElementById('templateStatus');

        const formatDate = (value) => {
            if (!value) {
                return '';
            }
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) {
                return value;
            }
            const y = date.getFullYear();
            const m = String(date.getMonth() + 1).padStart(2, '0');
            const d = String(date.getDate()).padStart(2, '0');
            return `${y}-${m}-${d}`;
        };

        document.querySelectorAll('.preset-date').forEach((btn) => {
            btn.addEventListener('click', () => {
                const value = btn.dataset.value;
                if (!value || !startInput) return;
                startInput.value = value;
                if (endInput) {
                    endInput.value = value;
                }
            });
        });

        const autobuild = document.getElementById('autobuildDates');
        autobuild?.addEventListener('click', () => {
            if (!startInput || !endInput) return;
            const today = formatDate(new Date());
            startInput.value = today;
            const nextSunday = formatDate(new Date(Date.now() + 6 * 24 * 60 * 60 * 1000));
            endInput.value = nextSunday;
        });

        quickOpen?.addEventListener('change', function () {
            if (!status) return;
            status.value = this.checked ? 'open' : 'draft';
        });
    })();
</script>
<?= $this->endSection() ?>
