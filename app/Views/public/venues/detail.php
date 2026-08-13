<?php
$locale = service('language')->getLocale();
$venue = $venue['venue'] ?? null;
$branches = $venue['branches'] ?? [];
$assignedClubs = $venue['clubs'] ?? [];
$courtGrid = $venue['court_grid'] ?? [];
$courtSchedule = $venue['court_schedule'] ?? [];
$activeTab = $activeTab ?? 'overview';
?>
<!doctype html>
<html lang="<?= esc($locale) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= esc($pageTitle) ?></title>
    <meta name="description" content="<?= esc($metaDescription ?? 'Chi tiết cơ sở') ?>">
    <link rel="stylesheet" href="<?= esc(asset_url('assets/css/public-portal.css')) ?>">
    <style>
        .detail-layout{display:grid;grid-template-columns:1.2fr .8fr;gap:16px}
        .panel{background:#fff;border:1px solid #dfe9e6;padding:16px;border-radius:14px}
        .title{display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap}
        .title h1{margin:0}
        .metric{background:#f4faf8;border-left:4px solid #17a2a2;padding:10px;border-radius:10px}
        table{width:100%;border-collapse:collapse}
        th,td{padding:8px 10px;border-bottom:1px solid #edf3f1;text-align:left;font-size:14px}
        .sub{color:#6d7f82;font-size:13px}
        .muted{color:#7a8f93}
        .tabs{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}
        .tab-link{display:inline-block;padding:7px 10px;border-radius:999px;border:1px solid #dce7e4;text-decoration:none}
        .tab-link.is-active{background:#0f9b9b;color:#fff;border-color:#0f9b9b}
        @media(max-width:900px){.detail-layout{grid-template-columns:1fr}}
    </style>
</head>
<body>
<div class="portal-container">
    <div class="title" style="padding:14px 0 12px">
        <a href="/venues">← Quay về danh sách cơ sở</a>
        <h1 style="margin:0"><?= esc($venue->name_vi ?: 'Chi tiết cơ sở') ?></h1>
    </div>

    <div class="detail-layout">
        <section class="panel">
            <h3>Thông tin cơ sở</h3>
            <div class="tabs">
                <a class="tab-link <?= $activeTab === 'overview' ? 'is-active' : '' ?>" href="/venues/<?= (int) $venue->id ?>">Tổng quan</a>
                <a class="tab-link <?= $activeTab === 'courts' ? 'is-active' : '' ?>" href="/venues/<?= (int) $venue->id ?>/courts">Danh sách sân</a>
                <a class="tab-link <?= $activeTab === 'schedule' ? 'is-active' : '' ?>" href="/venues/<?= (int) $venue->id ?>/schedule">Lịch đặt sân</a>
                <a class="tab-link <?= $activeTab === 'members' ? 'is-active' : '' ?>" href="/venues/<?= (int) $venue->id ?>/members">Thành viên liên kết</a>
                <a class="tab-link <?= $activeTab === 'history' ? 'is-active' : '' ?>" href="/venues/<?= (int) $venue->id ?>/history">Lịch sử gần đây</a>
            </div>

            <?php if (in_array($activeTab, ['overview', 'members'], true)): ?>
                <div class="sub">Mã cơ sở: <?= esc($venue->code) ?></div>
                <div class="sub">Địa chỉ: <?= esc($venue->address ?: '-') ?></div>
                <div class="sub">Tình trạng: <?= esc($venue->status) ?></div>
                <div class="metric" style="margin-top:10px">
                    <strong>Chi nhánh:</strong> <?= count($branches) ?> • <strong>Sân:</strong> <?= array_sum(array_map(fn($b) => (int) ($b->court_count ?? 0), $branches)) ?>
                </div>
                <div class="metric" style="margin-top:10px">
                    <strong>Liên kết CLB:</strong> <?= count($assignedClubs) ?> CLB
                </div>
            <?php elseif ($activeTab === 'courts'): ?>
                <div class="sub">Mạng sân theo chi nhánh:</div>
                <?php if ($courtGrid): ?>
                    <?php foreach ($courtGrid as $branchCourts): ?>
                        <?php foreach ($branchCourts as $court): ?>
                            <div class="metric" style="margin-bottom:8px">
                                <strong><?= esc($court->code) ?></strong> · Trạng thái: <?= esc($court->status) ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="muted">Chưa có dữ liệu sân.</div>
                <?php endif; ?>
            <?php elseif ($activeTab === 'schedule'): ?>
                <?php if ($courtSchedule): ?>
                    <table>
                        <thead><tr><th>Ngày</th><th>Khung giờ</th><th>Sân</th><th>Chi nhánh</th><th>VĐV</th><th>Trạng thái</th><th>Check-in</th></tr></thead>
                        <tbody>
                            <?php foreach ($courtSchedule as $row): ?>
                                <tr>
                                    <td><?= esc(date('d/m/Y', strtotime((string) $row->booking_date))) ?></td>
                                    <td><?= esc($row->start_time) ?> - <?= esc($row->end_time) ?></td>
                                    <td><?= esc($row->court_code ?: '-') ?></td>
                                    <td><?= esc($row->branch_name ?: '-') ?></td>
                                    <td><a href="/players/<?= (int) ($row->player_id ?? 0) ?>"><?= esc($row->player_name ?: '-') ?></a></td>
                                    <td><?= esc($row->status) ?></td>
                                    <td><?= esc($row->checked_in_at ? 'Đã check-in' : 'Chưa') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="sub">Không có lịch đặt sân gần đây.</div>
                <?php endif; ?>
            <?php else: ?>
                <div class="sub">Lịch sử hoạt động gần đây</div>
                <div class="sub">Chưa có data lịch sử mở rộng.</div>
            <?php endif; ?>

            <?php if ($activeTab === 'overview'): ?>
                <h4 style="margin-top:16px">Chi nhánh</h4>
                <?php if ($branches): ?>
                    <table>
                        <thead><tr><th>Tên</th><th>Loại</th><th>Trạng thái</th><th>Sân</th><th>Đang hoạt động</th><th>Đang chơi</th></tr></thead>
                        <tbody>
                            <?php foreach ($branches as $branch): ?>
                                <tr>
                                    <td><?= esc($branch->name ?: $branch->code) ?></td>
                                    <td><?= esc($branch->branch_type ?: '-') ?></td>
                                    <td><?= esc($branch->status) ?></td>
                                    <td><?= (int) ($branch->court_count ?? 0) ?></td>
                                    <td><?= (int) ($branch->court_active ?? 0) ?></td>
                                    <td><?= (int) ($branch->booking_in_progress ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="sub">Chưa có chi nhánh được cấu hình.</div>
                <?php endif; ?>
            <?php endif; ?>
        </section>
        <aside class="panel">
            <h3>CLB liên kết</h3>
            <?php if ($assignedClubs): foreach ($assignedClubs as $club): ?>
                <div style="padding:8px 0;border-bottom:1px solid #edf3f1">
                    <div><strong><?= esc($club->name_vi ?: $club->name_en) ?></strong></div>
                    <div class="sub">Vai trò: <?= ((int) ($club->is_primary ?? 0)) ? 'Cơ sở chính' : 'Liên kết' ?> • Chia sẻ: <?= esc($club->revenue_share ?? '0') ?>%</div>
                    <a href="/clubs/<?= (int) $club->club_id ?>">Xem CLB</a>
                </div>
            <?php endforeach; else: ?>
                <div class="sub">Cơ sở này chưa gắn CLB.</div>
            <?php endif; ?>
        </aside>
    </div>
</div>
</body>
</html>
