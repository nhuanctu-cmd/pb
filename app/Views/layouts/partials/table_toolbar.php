<div class="erp-table-toolbar">
    <div class="d-flex gap-2 align-items-center">
        <button class="erp-btn erp-btn-ghost" data-filter-toggle="#advancedFilters"><i class="bi bi-sliders"></i> Bộ lọc nâng cao</button>
        <?= $left ?? '' ?>
    </div>
    <div class="d-flex gap-2">
        <?= $right ?? '<button class="erp-btn"><i class="bi bi-download"></i> Xuất Excel</button><button class="erp-btn"><i class="bi bi-layout-three-columns"></i> Cột</button>' ?>
    </div>
</div>
