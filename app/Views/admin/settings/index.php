<?= $this->extend('layouts/admin_master') ?>

<?= $this->section('styles') ?>
<style>
    .settings-shell { display:grid; grid-template-columns:240px minmax(0,1fr); gap:18px; align-items:start; }
    .settings-nav, .settings-panel { background:#fff; border:1px solid var(--erp-border); border-radius:12px; box-shadow:var(--erp-shadow-sm); }
    .settings-nav { position:sticky; top:84px; overflow:hidden; }
    .settings-nav-head { padding:17px 18px; border-bottom:1px solid var(--erp-border); }
    .settings-nav-head strong { display:block; font-size:14px; }
    .settings-nav-head small { color:var(--erp-muted); }
    .settings-nav-list { padding:8px; }
    .settings-nav-link { display:flex; align-items:center; gap:10px; min-height:42px; padding:9px 11px; border-radius:8px; color:var(--erp-text); text-decoration:none; font-weight:650; font-size:13px; }
    .settings-nav-link:hover { background:#eef4ff; color:var(--erp-primary); }
    .settings-nav-link.active { background:#e8f0ff; color:var(--erp-primary); box-shadow:inset 3px 0 #1769e0; }
    .settings-nav-link i { width:20px; text-align:center; color:var(--erp-muted); }
    .settings-nav-link.active i { color:var(--erp-primary); }
    .settings-toolbar { display:flex; justify-content:space-between; align-items:center; gap:14px; padding:17px 20px; border-bottom:1px solid var(--erp-border); }
    .settings-toolbar h2 { margin:0; font-size:19px; font-weight:800; }
    .settings-toolbar p { margin:4px 0 0; color:var(--erp-muted); font-size:12px; }
    .settings-search { width:min(280px,40%); position:relative; }
    .settings-search i { position:absolute; left:11px; top:10px; color:var(--erp-muted); }
    .settings-search input { width:100%; height:36px; padding:0 12px 0 33px; border:1px solid var(--erp-border); border-radius:7px; }
    .settings-body { padding:18px 20px 76px; }
    .settings-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:13px; }
    .setting-card { padding:15px; border:1px solid var(--erp-border); border-radius:10px; background:#fbfcfe; transition:border-color .15s,box-shadow .15s; }
    .setting-card:hover { border-color:#9bbcf2; box-shadow:0 5px 16px rgba(23,105,224,.08); }
    .setting-card.is-hidden { display:none; }
    .setting-label { display:flex; align-items:flex-start; justify-content:space-between; gap:8px; margin-bottom:8px; }
    .setting-label strong { font-size:13px; line-height:1.35; }
    .setting-key { margin-top:7px; color:var(--erp-muted); font:11px ui-monospace,SFMono-Regular,Consolas,monospace; }
    .setting-card textarea { resize:vertical; min-height:84px; }
    .setting-card .form-control, .setting-card .form-select { background:#fff; border-color:#cbd7e8; }
    .settings-footer { position:sticky; bottom:0; display:flex; justify-content:space-between; align-items:center; gap:12px; padding:12px 20px; background:rgba(255,255,255,.96); border-top:1px solid var(--erp-border); }
    .settings-footer small { color:var(--erp-muted); }
    .settings-empty { grid-column:1/-1; padding:45px 20px; text-align:center; color:var(--erp-muted); }
    @media(max-width:900px){ .settings-shell{grid-template-columns:1fr}.settings-nav{position:static}.settings-nav-list{display:flex;gap:6px;overflow:auto}.settings-nav-link{white-space:nowrap}.settings-grid{grid-template-columns:1fr}.settings-toolbar{align-items:flex-start;flex-direction:column}.settings-search{width:100%} }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?= flash_message() ?>

<?php
$groupIcons = [
    'general' => 'bi-sliders2',
    'booking' => 'bi-calendar-check',
    'payment' => 'bi-credit-card',
    'notifications' => 'bi-bell',
    'business' => 'bi-briefcase',
];
?>
<div class="settings-shell">
    <aside class="settings-nav">
        <div class="settings-nav-head">
            <strong><i class="bi bi-gear me-1"></i> Cấu hình hệ thống</strong>
            <small>Thiết lập theo từng module</small>
        </div>
        <nav class="settings-nav-list" aria-label="Nhóm cấu hình">
            <?php foreach ($groups as $key => $label): ?>
                <a href="<?= base_url('admin/settings?group=' . rawurlencode($key)) ?>" class="settings-nav-link <?= $currentGroup === $key ? 'active' : '' ?>">
                    <i class="bi <?= esc($groupIcons[$key] ?? 'bi-folder2') ?>"></i>
                    <span><?= esc($label) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    </aside>

    <section class="settings-panel">
        <div class="settings-toolbar">
            <div>
                <h2><?= esc($groups[$currentGroup] ?? $currentGroup) ?></h2>
                <p><span id="settingsVisibleCount"><?= count($settings) ?></span> cấu hình trong nhóm này</p>
            </div>
            <label class="settings-search" aria-label="Tìm cấu hình">
                <i class="bi bi-search"></i>
                <input type="search" id="settingsSearch" placeholder="Tìm theo tên hoặc mã..." autocomplete="off">
            </label>
        </div>

        <form method="post" action="<?= base_url('admin/settings/update') ?>" id="settingsForm">
            <?= csrf_field() ?>
            <input type="hidden" name="group" value="<?= esc($currentGroup) ?>">
            <div class="settings-body">
                <div class="settings-grid" id="settingsGrid">
                    <?php if (empty($settings)): ?>
                        <div class="settings-empty"><i class="bi bi-inbox fs-2 d-block mb-2"></i><?= lang('App.no_data') ?></div>
                    <?php else: ?>
                        <?php foreach ($settings as $key => $setting): ?>
                            <?php $label = lang('App.setting_' . $key); ?>
                            <article class="setting-card" data-setting-search="<?= esc(mb_strtolower($key . ' ' . $label), 'attr') ?>">
                                <div class="setting-label">
                                    <strong><?= esc($label !== 'App.setting_' . $key ? $label : ucwords(str_replace(['_', '-'], ' ', $key))) ?></strong>
                                    <?php if (! empty($setting['is_override'])): ?>
                                        <span class="badge text-bg-info">Ghi đè tenant</span>
                                    <?php else: ?>
                                        <span class="badge text-bg-light border">Mặc định</span>
                                    <?php endif; ?>
                                </div>
                                <?php if (($setting['type'] ?? 'text') === 'textarea'): ?>
                                    <textarea class="form-control" name="settings[<?= esc($key) ?>]" rows="3"><?= esc($setting['value'] ?? '') ?></textarea>
                                <?php elseif (($setting['type'] ?? 'text') === 'boolean'): ?>
                                    <select class="form-select" name="settings[<?= esc($key) ?>]"><option value="1" <?= (string) ($setting['value'] ?? '') === '1' ? 'selected' : '' ?>>Bật</option><option value="0" <?= (string) ($setting['value'] ?? '') === '1' ? '' : 'selected' ?>>Tắt</option></select>
                                <?php elseif (($setting['type'] ?? 'text') === 'number'): ?>
                                    <input type="number" class="form-control" name="settings[<?= esc($key) ?>]" value="<?= esc($setting['value'] ?? '') ?>">
                                <?php else: ?>
                                    <input type="text" class="form-control" name="settings[<?= esc($key) ?>]" value="<?= esc($setting['value'] ?? '') ?>">
                                <?php endif; ?>
                                <div class="setting-key"><?= esc($key) ?></div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="settings-footer">
                <small><i class="bi bi-shield-check me-1"></i> Thay đổi được lưu theo tenant đang chọn</small>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i> Lưu cấu hình</button>
            </div>
        </form>
    </section>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    var search = document.getElementById('settingsSearch');
    var cards = Array.prototype.slice.call(document.querySelectorAll('.setting-card'));
    var count = document.getElementById('settingsVisibleCount');
    if (!search) return;
    search.addEventListener('input', function () {
        var query = search.value.trim().toLowerCase();
        var visible = 0;
        cards.forEach(function (card) {
            var match = !query || (card.getAttribute('data-setting-search') || '').indexOf(query) !== -1;
            card.classList.toggle('is-hidden', !match);
            if (match) visible++;
        });
        if (count) count.textContent = visible;
    });
})();
</script>
<?= $this->endSection() ?>
