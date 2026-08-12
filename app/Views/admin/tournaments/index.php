<?= $this->extend('layouts/admin_master') ?>

<?= $this->section('content') ?>
<div class="erp-action-bar">
    <div class="d-flex gap-2 flex-wrap">
        <a href="/admin/tournaments/create" class="erp-btn erp-btn-primary"><i class="bi bi-plus-lg"></i> Tạo giải đấu</a>
        <a href="/admin/tournaments/scheduler" class="erp-btn"><i class="bi bi-diagram-3"></i> Xếp lịch</a>
        <a href="/admin/tournaments/bracket" class="erp-btn"><i class="bi bi-diagram-3"></i> Cây đấu</a>
        <a href="/admin/tournaments/control-room" class="erp-btn"><i class="bi bi-broadcast"></i> Control Room</a>
        <a href="/admin/tournament-templates" class="erp-btn"><i class="bi bi-copy"></i> Templates</a>
        <a href="<?= base_url('admin/tournaments/export?' . http_build_query($filters ?? [])) ?>" class="erp-btn"><i class="bi bi-file-earmark-spreadsheet"></i> Xuất CSV</a>
    </div>
    <a href="/tournaments" target="_blank" class="erp-btn"><i class="bi bi-box-arrow-up-right"></i> Trang public</a>
</div>

<form method="get" action="<?= base_url('admin/tournaments') ?>" class="erp-filter-bar">
    <div class="d-flex flex-wrap gap-2 align-items-center">
    <select name="status" class="erp-select" style="width:180px">
        <option value="">Tất cả trạng thái</option>
        <option value="draft" <?= ($filters['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Nháp</option>
        <option value="open" <?= ($filters['status'] ?? '') === 'open' ? 'selected' : '' ?>>Mở đăng ký</option>
        <option value="closed" <?= ($filters['status'] ?? '') === 'closed' ? 'selected' : '' ?>>Đóng đăng ký</option>
        <option value="running" <?= ($filters['status'] ?? '') === 'running' ? 'selected' : '' ?>>Đang diễn ra</option>
    </select>
    <input name="search" class="erp-control" style="width:220px" value="<?= esc($filters['search'] ?? '') ?>" placeholder="Tên giải / event">
    <input type="date" name="date_from" class="erp-control" value="<?= esc($filters['date_from'] ?? '') ?>" title="Từ ngày">
    <input type="date" name="date_to" class="erp-control" value="<?= esc($filters['date_to'] ?? '') ?>" title="Đến ngày">
    <select name="per_page" class="erp-select" style="width:90px"><option value="15" <?= (int) ($pagination['perPage'] ?? 15) === 15 ? 'selected' : '' ?>>15 dòng</option><option value="30" <?= (int) ($pagination['perPage'] ?? 15) === 30 ? 'selected' : '' ?>>30 dòng</option><option value="50" <?= (int) ($pagination['perPage'] ?? 15) === 50 ? 'selected' : '' ?>>50 dòng</option><option value="100" <?= (int) ($pagination['perPage'] ?? 15) === 100 ? 'selected' : '' ?>>100 dòng</option></select>
    </div>
    <div class="d-flex gap-2"><button class="erp-btn erp-btn-primary" type="submit"><i class="bi bi-funnel"></i> Lọc</button><a class="erp-btn erp-btn-ghost" href="<?= base_url('admin/tournaments') ?>">Xóa lọc</a></div>
</form>

<?php if (! empty($tournaments)): ?>
<div class="erp-table-wrap">
    <table class="erp-table">
        <thead><tr><th>Giải đấu</th><th>Chi nhánh</th><th>Thời gian</th><th>Đăng ký</th><th>Phí</th><th>Trạng thái</th><th class="col-actions">Action</th></tr></thead>
        <tbody>
        <?php foreach ($tournaments as $tournament): ?>
            <tr>
                <td><strong><?= esc($tournament->name_vi) ?></strong><div class="erp-muted">/tournaments/<?= esc($tournament->slug_vi) ?></div></td>
                <td><?= esc($tournament->branch_name ?? '-') ?></td>
                <td><?= esc(format_date($tournament->start_date)) ?> - <?= esc(format_date($tournament->end_date)) ?></td>
                <td><?= esc(format_datetime($tournament->registration_start)) ?><div class="erp-muted"><?= esc(format_datetime($tournament->registration_end)) ?></div></td>
                <td><strong><?= format_money($tournament->registration_fee) ?></strong></td>
                <td><?= renderStatusBadge($tournament->status, 'tournament') ?></td>
                <td class="col-actions">
                    <a class="erp-btn erp-btn-icon" href="/admin/tournaments/show/<?= $tournament->id ?>" title="Chi tiết"><i class="bi bi-eye"></i></a>
                    <a class="erp-btn erp-btn-icon" href="/admin/tournaments/edit/<?= $tournament->id ?>" title="Sửa"><i class="bi bi-pencil"></i></a>
                    <a class="erp-btn erp-btn-icon" href="/admin/tournaments/registrations/<?= $tournament->id ?>" title="Đăng ký"><i class="bi bi-people"></i></a>
                    <a class="erp-btn erp-btn-icon" href="/admin/tournaments/scheduler" title="Xếp lịch"><i class="bi bi-calendar3"></i></a>
                    <a class="erp-btn erp-btn-icon" href="/admin/tournaments/bracket?tournament_id=<?= $tournament->id ?>" title="Cây đấu"><i class="bi bi-diagram-3"></i></a>
                    <a class="erp-btn erp-btn-icon" href="/admin/print-center?tournament_id=<?= $tournament->id ?>" title="Print Center"><i class="bi bi-printer"></i></a>
                    <?php if ($tournament->status !== 'open'): ?>
                        <form method="post" action="/admin/tournaments/open/<?= $tournament->id ?>" class="d-inline"><?= csrf_field() ?><button class="erp-btn erp-btn-icon" title="Mở đăng ký"><i class="bi bi-unlock"></i></button></form>
                    <?php else: ?>
                        <form method="post" action="/admin/tournaments/close/<?= $tournament->id ?>" class="d-inline"><?= csrf_field() ?><button class="erp-btn erp-btn-icon" title="Đóng đăng ký"><i class="bi bi-lock"></i></button></form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<div class="erp-pagination"><span class="erp-muted">Tổng <?= (int) ($pagination['total'] ?? count($tournaments)) ?> giải · Trang <?= (int) ($pagination['page'] ?? 1) ?>/<?= (int) ($pagination['pages'] ?? 1) ?></span><div class="btn-group btn-group-sm"><?php $page = (int) ($pagination['page'] ?? 1); $pages = (int) ($pagination['pages'] ?? 1); for ($p = max(1, $page - 2); $p <= min($pages, $page + 2); $p++): ?><a class="btn btn-outline-secondary <?= $p === $page ? 'active' : '' ?>" href="<?= base_url('admin/tournaments?' . http_build_query(array_merge($filters ?? [], ['page' => $p, 'per_page' => $pagination['perPage'] ?? 15]))) ?>"><?= $p ?></a><?php endfor; ?></div></div>
<?php else: ?>
<div class="erp-card erp-empty">
    <div class="erp-empty-icon"><i class="bi bi-trophy"></i></div>
    <h3>Chưa có giải đấu</h3>
    <p>Tạo event đầu tiên, thêm hạng mục, luật và mở đăng ký online.</p>
    <a href="/admin/tournaments/create" class="erp-btn erp-btn-primary">Tạo giải đấu</a>
</div>
<?php endif; ?>
<?= $this->endSection() ?>
