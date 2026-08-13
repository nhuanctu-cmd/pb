<?= $this->extend('layouts/admin_master') ?>

<?= $this->section('styles') ?><link rel="stylesheet" href="<?= esc(asset_url('assets/css/tournament-operations.css')) ?>"><style>
.print-hero { background: linear-gradient(135deg,#0f766e,#155e75); color: #fff; border-radius: 20px; padding: 28px; position: relative; overflow: hidden; }
.print-hero:after { content: '✦'; position: absolute; right: 32px; top: 8px; font-size: 7rem; opacity: .14; }
.print-stat { border: 1px solid #e5e7eb; border-radius: 14px; background: #fff; padding: 16px; height: 100%; }
.print-stat .value { font-size: 1.65rem; font-weight: 700; }
.print-option { border: 1px solid #e5e7eb; border-radius: 14px; padding: 18px; height: 100%; background: #fff; transition: .18s; display: flex; flex-direction: column; }
.print-option:hover { border-color:#0d9488; box-shadow:0 8px 22px rgba(15,118,110,.12); transform: translateY(-2px); }
.print-option i { font-size: 1.55rem; color: #0d9488; }
.print-option p { min-height: 44px; }
.print-section-title { font-size: .75rem; letter-spacing: .08em; text-transform: uppercase; color: #64748b; font-weight: 700; }
.print-table tbody tr.selected { background: #ecfdf5; }
.print-pagination .page-link { border:0; margin: 0 2px; border-radius: 8px; }
.print-pagination .active .page-link { background: #0f766e; color: #fff; }
.print-filter .col-sm-6, .print-filter .col-sm-4, .print-filter .col-sm-3 { margin-bottom: 10px; }
</style><?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php
$docBase = ['tournament_id' => (int) $tournamentId];
$docBase = array_merge($docBase, [
    'category_id' => (string) ($printOptions['category_id'] ?? ''),
    'court_id' => (string) ($printOptions['court_id'] ?? ''),
    'date_from' => (string) ($printOptions['date_from'] ?? ''),
    'date_to' => (string) ($printOptions['date_to'] ?? ''),
    'status_match' => (string) ($printOptions['status'] ?? ''),
    'checkin_status' => (string) ($printOptions['checkin_status'] ?? ''),
]);
?>

<section class="print-hero mb-4">
    <div class="position-relative" style="z-index:1">
        <div class="small text-uppercase opacity-75 mb-2">Tournament operations</div>
        <h1 class="h3 mb-2">Print Center</h1>
        <p class="mb-0 opacity-75">Một nơi in toàn tập cho vận hành: badge, bảng tên, lịch, phiếu trận, bracket, chứng nhận và kiểm soát check-in.</p>
    </div>
</section>

<form method="get" class="erp-card mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-lg-5">
            <label class="form-label">Tìm giải đấu</label>
            <input name="search" value="<?= esc($tournaments['search']) ?>" class="form-control" placeholder="Tên giải, tên tiếng Anh hoặc slug">
        </div>
        <div class="col-lg-3">
            <label class="form-label">Trạng thái</label>
            <select name="status" class="form-select">
                <option value="">Tất cả trạng thái</option>
                <?php foreach (['draft'=>'Nháp','open'=>'Mở đăng ký','closed'=>'Đã đóng','running'=>'Đang diễn ra','completed'=>'Đã hoàn tất','cancelled'=>'Đã hủy'] as $value => $label): ?>
                    <option value="<?= $value ?>" <?= $tournaments['status'] === $value ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-lg-2">
            <label class="form-label">Số dòng</label>
            <select name="per_page" class="form-select">
                <option value="12" <?= (int) $tournaments['perPage'] === 12 ? 'selected' : '' ?>>12</option>
                <option value="24" <?= (int) $tournaments['perPage'] === 24 ? 'selected' : '' ?>>24</option>
                <option value="48" <?= (int) $tournaments['perPage'] === 48 ? 'selected' : '' ?>>48</option>
            </select>
        </div>
        <div class="col-lg-2 d-flex gap-2">
            <button class="erp-btn erp-btn-primary flex-grow-1"><i class="bi bi-search"></i> Lọc</button>
            <a href="<?= base_url('admin/print-center') ?>" class="erp-btn">Xóa</a>
        </div>
    </div>
</form>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3"><div class="print-stat"><div class="text-muted small">Tổng giải</div><div class="value"><?= (int)$tournaments['total'] ?></div></div></div>
    <div class="col-6 col-xl-3"><div class="print-stat"><div class="text-muted small">Trang hiện tại</div><div class="value"><?= (int)$tournaments['page'] ?>/<?= (int)$tournaments['pages'] ?></div></div></div>
    <div class="col-6 col-xl-3"><div class="print-stat"><div class="text-muted small">Giải đang chọn</div><div class="value"><?= $overview ? '#' . (int)$overview['tournament']->id : '—' ?></div></div></div>
    <div class="col-6 col-xl-3"><div class="print-stat"><div class="text-muted small">Tài liệu có thể in</div><div class="value"><?= count($printCatalog) ?></div></div></div>
</div>

<div class="erp-card mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <div class="print-section-title">Danh sách giải đấu</div>
            <div class="text-muted small">Hiển thị <?= count($tournaments['items']) ?> / <?= (int)$tournaments['total'] ?> giải</div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table erp-table align-middle mb-0">
            <thead><tr><th>Giải đấu</th><th>Thời gian</th><th>Trạng thái</th><th>Chi nhánh</th><th class="text-end">Thao tác</th></tr></thead>
            <tbody>
            <?php foreach ($tournaments['items'] as $item): ?>
                <tr class="<?= $tournamentId === (int)$item->id ? 'selected' : '' ?>">
                    <td>
                        <div class="fw-semibold"><?= esc($item->name_vi) ?></div>
                        <div class="text-muted small"><?= esc($item->slug_vi ?: '-') ?></div>
                    </td>
                    <td><?= esc($item->start_date ?: '-') ?><?= $item->end_date && $item->end_date !== $item->start_date ? ' → ' . esc($item->end_date) : '' ?></td>
                    <td><span class="badge text-bg-light"><?= esc($item->status ?: '-') ?></span></td>
                    <td><?= esc($item->branch_name ?: '-') ?></td>
                    <td class="text-end"><a class="erp-btn erp-btn-sm <?= $tournamentId === (int)$item->id ? 'erp-btn-primary' : '' ?>" href="<?= base_url('admin/print-center?' . http_build_query(['tournament_id' => (int)$item->id, 'search' => $tournaments['search'], 'status' => $tournaments['status'], 'page' => $tournaments['page']])) ?>"><i class="bi bi-check2-circle"></i> Chọn giải</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$tournaments['items']): ?>
                <tr><td colspan="5" class="text-center text-muted py-5">Không tìm thấy giải đấu phù hợp.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($tournaments['pages'] > 1): ?>
        <nav class="mt-3 print-pagination">
            <ul class="pagination pagination-sm justify-content-end mb-0">
                <?php for ($p = 1; $p <= $tournaments['pages']; $p++): ?>
                    <li class="page-item <?= $p === (int)$tournaments['page'] ? 'active' : '' ?>"><a class="page-link" href="<?= base_url('admin/print-center?' . http_build_query(['page' => $p, 'search' => $tournaments['search'], 'status' => $tournaments['status'], 'tournament_id' => $tournamentId])) ?>"><?= $p ?></a></li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<?php if ($overview): ?>
    <div class="erp-card mb-4">
        <div class="d-flex justify-content-between align-items-start gap-3">
            <div>
                <div class="print-section-title">Đang chuẩn bị in</div>
                <h2 class="h5 mb-1"><?= esc($overview['tournament']->name_vi) ?></h2>
                <div class="text-muted small"><?= esc($overview['tournament']->start_date ?: '-') ?> · <?= esc($overview['tournament']->status ?: '-') ?></div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="<?= base_url('admin/tournaments/show/' . (int)$overview['tournament']->id) ?>" class="erp-btn">Mở chi tiết</a>
                <a href="/admin/tournaments/registrations/<?= (int)$overview['tournament']->id ?>" class="erp-btn">Đăng ký</a>
                <a href="/admin/tournaments/bracket?tournament_id=<?= (int)$overview['tournament']->id ?>" class="erp-btn">Cây đấu</a>
                <a href="/admin/tournaments/control-room?tournament_id=<?= (int)$overview['tournament']->id ?>" class="erp-btn">Control Room</a>
            </div>
        </div>
        <div class="row g-3 mt-2">
            <div class="col-6 col-md-3"><div class="print-stat"><div class="text-muted small">Hạng mục</div><strong><?= (int)$overview['categories'] ?></strong></div></div>
            <div class="col-6 col-md-3"><div class="print-stat"><div class="text-muted small">Đăng ký</div><strong><?= (int)$overview['registrations'] ?></strong></div></div>
            <div class="col-6 col-md-3"><div class="print-stat"><div class="text-muted small">Trận đấu</div><strong><?= (int)$overview['matches'] ?></strong></div></div>
            <div class="col-6 col-md-3"><div class="print-stat"><div class="text-muted small">Đã check-in</div><strong><?= (int)$overview['checked_in'] ?>/<?= (int)$overview['registrations'] ?></strong></div></div>
        </div>
    </div>

    <div class="erp-card mb-4">
        <div class="print-section-title">Bộ lọc in sâu</div>
        <form method="get" class="row print-filter mt-2 g-2 align-items-end">
            <input type="hidden" name="tournament_id" value="<?= (int)$tournamentId ?>">
            <input type="hidden" name="search" value="<?= esc($tournaments['search']) ?>">
            <input type="hidden" name="status" value="<?= esc($tournaments['status']) ?>">
            <input type="hidden" name="per_page" value="<?= (int)$tournaments['perPage'] ?>">
            <input type="hidden" name="page" value="<?= (int)$tournaments['page'] ?>">
            <div class="col-sm-3">
                <label class="form-label">Hạng mục</label>
                <select name="category_id" class="form-select">
                    <option value="">Tất cả hạng mục</option>
                    <?php foreach ($categoryOptions as $category): ?>
                        <option value="<?= (int)$category->id ?>" <?= (string)($printOptions['category_id'] ?? '') === (string)$category->id ? 'selected' : '' ?>><?= esc($category->name_vi ?: $category->name_en ?: ('#' . (int)$category->id)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-3">
                <label class="form-label">Sân</label>
                <select name="court_id" class="form-select">
                    <option value="">Tất cả sân</option>
                    <?php foreach ($courtOptions as $court): ?>
                        <option value="<?= (int)$court['id'] ?>" <?= (string)($printOptions['court_id'] ?? '') === (string)$court['id'] ? 'selected' : '' ?>><?= esc($court['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-2">
                <label class="form-label">Ngày từ</label>
                <input type="date" name="date_from" value="<?= esc((string)($printOptions['date_from'] ?? '')) ?>" class="form-control">
            </div>
            <div class="col-sm-2">
                <label class="form-label">Ngày đến</label>
                <input type="date" name="date_to" value="<?= esc((string)($printOptions['date_to'] ?? '')) ?>" class="form-control">
            </div>
            <div class="col-sm-3">
                <label class="form-label">Tình trạng trận</label>
                <select name="status_match" class="form-select">
                    <option value="">Tất cả tình trạng</option>
                    <?php foreach (['scheduled'=>'scheduled','pending'=>'pending','called'=>'called','on_court'=>'on_court','running'=>'running','in_progress'=>'in_progress','completed'=>'completed','walkover'=>'walkover','cancelled'=>'cancelled'] as $label => $value): ?>
                        <option value="<?= $value ?>" <?= ((string)($printOptions['status'] ?? '') === $value) ? 'selected' : '' ?>><?= esc($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-3">
                <label class="form-label">Tình trạng check-in</label>
                <select name="checkin_status" class="form-select">
                    <option value="">Tất cả check-in</option>
                    <?php foreach (['pending' => 'pending', 'checked_in' => 'checked_in', 'absent' => 'absent'] as $label => $value): ?>
                        <option value="<?= $value ?>" <?= ((string)($printOptions['checkin_status'] ?? '') === $value) ? 'selected' : '' ?>><?= esc($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-2">
                <button class="erp-btn erp-btn-primary w-100"><i class="bi bi-filter-square"></i> Áp dụng</button>
            </div>
            <div class="col-sm-2">
                <a href="<?= base_url('admin/print-center?' . http_build_query(['tournament_id' => $tournamentId])) ?>" class="erp-btn w-100">Xóa bộ lọc</a>
            </div>
        </form>
    </div>

    <div class="d-flex justify-content-between align-items-end mb-3">
        <div>
            <div class="print-section-title">Bộ tài liệu</div>
            <h2 class="h5 mb-0">Chọn mẫu in</h2>
            <div class="text-muted small">Phạm vi: <?= esc($printScopeTitle ?? 'Toàn bộ dữ liệu') ?></div>
        </div>
        <a class="erp-btn erp-btn-primary" href="<?= base_url('admin/print-center/print?' . http_build_query(array_merge($docBase, ['document' => 'all']))) ?>" target="_blank"><i class="bi bi-printer"></i> In gộp toàn bộ</a>
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3">
        <?php foreach ($printCatalog as $key => $document): ?>
            <div class="col">
                <div class="print-option">
                    <div class="d-flex justify-content-between">
                        <i class="bi <?= esc($document['icon']) ?>"></i>
                        <span class="badge text-bg-light"><?= esc($document['group']) ?></span>
                    </div>
                    <h3 class="h6 mt-3 mb-1"><?= esc($document['label']) ?></h3>
                    <p class="text-muted small mb-3"><?= esc($document['description']) ?></p>
                    <a class="erp-btn erp-btn-primary w-100 mt-auto" target="_blank" href="<?= base_url('admin/print-center/print?' . http_build_query(array_merge($docBase, ['document' => $key]))) ?>">
                        <i class="bi bi-printer"></i> Xem trước & in
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="erp-card mt-4">
        <strong>Quy trình in</strong>
        <span class="text-muted ms-2">Lọc phạm vi → chọn mẫu → xem trước → Ctrl/Cmd + P để in hoặc lưu PDF.</span>
    </div>
<?php else: ?>
    <div class="erp-card">
        <strong>Gợi ý nhanh</strong>
        <p class="text-muted mb-0">Vui lòng chọn giải đấu trước để mở bộ in tập trung.</p>
    </div>
<?php endif; ?>

<?= $this->endSection() ?>
