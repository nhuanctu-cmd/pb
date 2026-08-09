<?= $this->extend('layouts/admin_master') ?>

<?= $this->section('content') ?>
<div class="erp-action-bar">
    <div class="d-flex gap-2 flex-wrap">
        <a href="/admin/tournaments/edit/<?= $tournament->id ?>" class="erp-btn"><i class="bi bi-pencil"></i> Sửa</a>
        <a href="/admin/tournaments/registrations/<?= $tournament->id ?>" class="erp-btn"><i class="bi bi-people"></i> Đăng ký</a>
        <a href="/tournaments/<?= esc($tournament->slug_vi) ?>" target="_blank" class="erp-btn"><i class="bi bi-box-arrow-up-right"></i> Public</a>
    </div>
    <?= renderStatusBadge($tournament->status, 'tournament') ?>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="erp-card mb-3">
            <?php if ($tournament->banner): ?><img src="<?= esc($tournament->banner) ?>" alt="" class="w-100 mb-3" style="max-height:260px;object-fit:cover;border-radius:8px"><?php endif; ?>
            <h3><?= esc($tournament->name_vi) ?></h3>
            <p class="erp-muted"><?= esc($tournament->description_vi) ?></p>
            <div class="erp-info-list">
                <div class="erp-info-row"><span>Thời gian</span><strong><?= esc(format_date($tournament->start_date)) ?> - <?= esc(format_date($tournament->end_date)) ?></strong></div>
                <div class="erp-info-row"><span>Đăng ký</span><strong><?= esc(format_datetime($tournament->registration_start)) ?> - <?= esc(format_datetime($tournament->registration_end)) ?></strong></div>
                <div class="erp-info-row"><span>Phí mặc định</span><strong><?= format_money($tournament->registration_fee) ?></strong></div>
            </div>
        </div>
        <div class="erp-card">
            <h5>Luật thi đấu</h5>
            <div style="white-space:pre-wrap"><?= esc($rule->rule_content_vi ?? 'Chưa nhập luật.') ?></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="erp-card mb-3">
            <h5>Hạng mục</h5>
            <?php foreach ($categories as $category): ?>
                <div class="erp-info-row"><span><?= esc($category->name_vi) ?></span><strong><?= format_money($category->registration_fee) ?></strong></div>
            <?php endforeach; ?>
        </div>
        <div class="erp-card">
            <h5>Nhà tài trợ</h5>
            <?php foreach ($sponsors as $sponsor): ?>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <?php if ($sponsor->logo): ?><img src="<?= esc($sponsor->logo) ?>" alt="" style="width:42px;height:42px;object-fit:contain"><?php endif; ?>
                    <strong><?= esc($sponsor->sponsor_name) ?></strong>
                </div>
            <?php endforeach; ?>
            <?php if (empty($sponsors)): ?><div class="erp-muted">Chưa có nhà tài trợ.</div><?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
