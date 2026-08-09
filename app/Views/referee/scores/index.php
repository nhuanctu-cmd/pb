<?= $this->extend('layouts/admin_master') ?>

<?= $this->section('content') ?>
<div class="erp-mobile-list">
    <?php foreach ($matches ?? [] as $match): ?>
        <article class="erp-mobile-card">
            <div class="d-flex justify-content-between gap-2 mb-2">
                <div>
                    <strong>Trận #<?= (int) $match->id ?></strong>
                    <div class="erp-muted">BO<?= esc($match->best_of ?? $match->number_of_sets ?? 1) ?></div>
                </div>
                <?= renderStatusBadge($match->status ?? 'scheduled', 'general') ?>
            </div>
            <div class="erp-info-list">
                <div class="erp-info-row"><span>Đội A</span><strong><?= esc($match->team_a_name ?? ('Team #' . ($match->team_a_id ?? '-'))) ?></strong></div>
                <div class="erp-info-row"><span>Đội B</span><strong><?= esc($match->team_b_name ?? ('Team #' . ($match->team_b_id ?? '-'))) ?></strong></div>
            </div>
            <div class="d-flex gap-2 mt-3">
                <form method="post" action="/referee/scores/<?= (int) $match->id ?>/start"><?= csrf_field() ?><button class="erp-btn" type="submit"><i class="bi bi-play-fill"></i> Bắt đầu</button></form>
                <a class="erp-btn erp-btn-primary" href="/referee/scores/<?= (int) $match->id ?>"><i class="bi bi-pencil-square"></i> Nhập điểm</a>
            </div>
        </article>
    <?php endforeach; ?>
</div>
<?php if (empty($matches)): ?>
    <div class="erp-card erp-empty">
        <div class="erp-empty-icon"><i class="bi bi-clipboard-check"></i></div>
        <h3>Không có trận được phân công</h3>
        <p>Danh sách sẽ hiện khi trận có referee_id của bạn hoặc khi dữ liệu tournament được tạo.</p>
    </div>
<?php endif; ?>
<?= $this->endSection() ?>
