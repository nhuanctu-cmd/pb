<?= $this->extend('admin/layouts/main') ?>
<?= $this->section('content') ?>

<?php
$today = $today ?? date('Y-m-d');
$flow  = $flow ?? [];
$coreFlow = $coreFlow ?? [];
?>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
    <div>
        <h1 class="h3 mb-1">Runbook vận hành thương mại</h1>
        <div class="text-muted">Luồng test 1-click cho 15 module sản xuất (Venue Control, Front Desk, Front Office, Booking, API chuẩn, bảo mật...)</div>
    </div>
    <div class="d-flex gap-2">
        <a href="/admin/dashboard?date=<?= esc($today) ?>" class="btn btn-outline-secondary">Dashboard</a>
        <button id="runbookOneClickBtn" class="btn btn-success" type="button">
            <i class="bi bi-play-fill me-1"></i> Chạy 1-click toàn bộ
        </button>
        <button id="runbookCoreBtn" class="btn btn-primary" type="button">
            <i class="bi bi-speedometer2 me-1"></i> Chạy 1-click 5 module Core
        </button>
    </div>
</div>

<div class="alert alert-secondary">
    <strong>Gợi ý dùng:</strong> click 1 nút để mở nhanh các trang deep-link. Nếu trình duyệt chặn popup, script sẽ hiển thị cảnh báo và bạn có thể vào từng bước thủ công.
</div>

<div id="runbookFlow" class="row g-3">
    <?php foreach ($flow as $index => $module): ?>
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="fw-semibold"><?= esc($module['title'] ?? ('Bước ' . ((int) $index + 1))) ?></div>
                        <span class="badge text-bg-light"><?= count($module['steps'] ?? []) ?> route sâu</span>
                    </div>
                    <div class="text-muted small"><?= esc($module['description'] ?? '') ?></div>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <?php if (empty($module['steps'])): ?>
                            <div class="list-group-item">Chưa có đường deep-link cho bước này.</div>
                        <?php else: ?>
                            <?php foreach ($module['steps'] as $step): ?>
                                <a
                                    class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                                    href="<?= esc($step['url']) ?>"
                                    data-runbook-step
                                >
                                    <span><i class="bi bi-link-45deg me-2"></i><?= esc($step['label']) ?></span>
                                    <i class="bi bi-arrow-up-right text-muted"></i>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if (! empty($coreFlow)): ?>
    <div class="row g-3 mt-1">
        <div class="col-12">
            <div class="card shadow-sm border-warning-subtle">
                <div class="card-header"><strong>Core 5 Module hàng ngày</strong></div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($coreFlow as $step): ?>
                            <a class="btn btn-outline-primary btn-sm" href="<?= esc($step['url']) ?>" data-runbook-core><?= esc($step['label']) ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<div id="runbookOneClickState" class="alert alert-warning d-none mt-3">
    ⚠️ Trình duyệt của bạn chặn mở tab mới khi click hàng loạt. Hãy nhấn từng link dưới đây để chạy tiếp theo.
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    (function () {
        const openTargets = () => Array.from(document.querySelectorAll('[data-runbook-step]')).map((el) => el.getAttribute('href')).filter(Boolean);
        const openCoreTargets = () => Array.from(document.querySelectorAll('[data-runbook-core]')).map((el) => el.getAttribute('href')).filter(Boolean);
        const state = document.getElementById('runbookOneClickState');
        const btn = document.getElementById('runbookOneClickBtn');
        const coreBtn = document.getElementById('runbookCoreBtn');

        btn?.addEventListener('click', () => {
            const targets = openTargets();
            let blocked = 0;

            targets.forEach((url, idx) => {
                const win = window.open(url, `_runbook_${idx}`);
                if (!win) {
                    blocked += 1;
                }
            });

            if (blocked > 0 && state) {
                state.classList.remove('d-none');
            }
        });

        coreBtn?.addEventListener('click', () => {
            const targets = openCoreTargets();
            let blocked = 0;
            targets.forEach((url) => {
                const win = window.open(url, `_runbook_core_${url.replace(/[^a-z0-9]/gi, '_')}`);
                if (!win) {
                    blocked += 1;
                }
            });
            if (blocked > 0 && state) {
                state.classList.remove('d-none');
            }
        });
    })();
</script>
<?= $this->endSection() ?>
