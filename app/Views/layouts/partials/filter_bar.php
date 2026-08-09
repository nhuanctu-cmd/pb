<div class="erp-filter-bar" id="<?= esc($id ?? 'filterBar') ?>">
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <?= $left ?? '' ?>
    </div>
    <div class="d-flex gap-2">
        <?= $right ?? '<button class="erp-btn"><i class="bi bi-funnel"></i> Lọc</button><button class="erp-btn erp-btn-ghost">Xóa lọc</button>' ?>
    </div>
</div>
