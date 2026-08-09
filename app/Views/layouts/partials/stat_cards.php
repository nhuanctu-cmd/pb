<?php $stats = $stats ?? []; ?>
<div class="erp-stat-grid">
    <?php foreach ($stats as $stat): ?>
        <div class="erp-stat-card">
            <div class="erp-stat-top">
                <span><?= esc($stat['label'] ?? '') ?></span>
                <i class="bi <?= esc($stat['icon'] ?? 'bi-activity') ?>"></i>
            </div>
            <div class="erp-stat-value"><?= esc($stat['value'] ?? '0') ?></div>
            <div class="erp-stat-trend <?= ($stat['trendType'] ?? 'success') === 'danger' ? 'text-danger' : 'text-success' ?>">
                <?= esc($stat['trend'] ?? '') ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
