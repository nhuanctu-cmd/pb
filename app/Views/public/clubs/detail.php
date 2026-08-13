<?php
$locale = service('language')->getLocale();
$dataClub = $club['club'] ?? null;
$members = $club['members'] ?? [];
$history = $club['history'] ?? [];
$posts = $club['posts'] ?? [];
$tournaments = $club['tournaments'] ?? [];
$activeTab = $activeTab ?? ($club['active_tab'] ?? 'overview');
$tabs = $club['tabs'] ?? [
    'overview' => ['members' => count($members), 'history' => count($history), 'posts' => count($posts), 'tournaments' => count($tournaments)],
];
$formatDate = static function ($value): string {
    if (! $value) {
        return '-';
    }
    return date('d/m/Y', strtotime((string) $value));
};
$formatDateTime = static function ($value): string {
    if (! $value) {
        return '-';
    }
    return date('d/m/Y H:i', strtotime((string) $value));
};
?>
<!doctype html>
<html lang="<?= esc($locale) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= esc($pageTitle) ?></title>
    <meta name="description" content="<?= esc($metaDescription ?? 'Chi tiết câu lạc bộ') ?>">
    <link rel="stylesheet" href="<?= esc(asset_url('assets/css/public-portal.css')) ?>">
    <style>
        .club-detail {display:grid;grid-template-columns:1.2fr .8fr;gap:16px}
        .panel {background:#fff;border:1px solid #dfe9e6;padding:16px;border-radius:14px}
        .toolbar {display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap}
        .tabs {display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
        .tab-link {border-radius:999px;padding:8px 12px;border:1px solid #d7e4e2;text-decoration:none}
        .tab-link.active {background:#0c7f7f;color:#fff;border-color:#0c7f7f}
        .chip {display:inline-block;background:#edf6f5;border-radius:99px;padding:3px 10px;font-size:12px;margin:0 6px 6px 0}
        .row {display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:14px}
        table {width:100%;border-collapse:collapse}
        th,td {padding:8px;border-bottom:1px solid #edf3f1;text-align:left;font-size:14px}
        .muted {color:#7a8a8d}
        @media (max-width: 980px) { .club-detail {grid-template-columns:1fr;} }
    </style>
</head>
<body>
<div class="portal-container">
    <div class="toolbar" style="padding:14px 0">
        <a href="/clubs">← Quay về danh sách CLB</a>
        <a href="/admin" class="muted">Khu vực điều hành</a>
    </div>

    <section class="panel">
        <h1 style="margin:0 0 8px"><?= esc($dataClub->name_vi ?: $dataClub->name_en ?: 'Câu lạc bộ') ?></h1>
        <div class="muted"><?= esc($dataClub->status ?? 'active') ?> · Thành lập: <?= esc($formatDate($dataClub?->created_at)) ?></div>
        <div class="tabs">
            <a href="/clubs/<?= (int) ($dataClub->id ?? 0) ?>" class="tab-link <?= $activeTab === 'overview' ? 'active' : '' ?>">Tổng quan (<?= (int) ($tabs['overview']['members'] ?? 0) ?>)</a>
            <a href="/clubs/<?= (int) ($dataClub->id ?? 0) ?>/members" class="tab-link <?= $activeTab === 'members' ? 'active' : '' ?>">Thành viên (<?= (int) ($tabs['members']['total'] ?? 0) ?>)</a>
            <a href="/clubs/<?= (int) ($dataClub->id ?? 0) ?>/history" class="tab-link <?= $activeTab === 'history' ? 'active' : '' ?>">Lịch sử trận (<?= (int) ($tabs['history']['total'] ?? 0) ?>)</a>
            <a href="/clubs/<?= (int) ($dataClub->id ?? 0) ?>/posts" class="tab-link <?= $activeTab === 'posts' ? 'active' : '' ?>">Bài viết (<?= (int) ($tabs['posts']['total'] ?? 0) ?>)</a>
            <a href="/clubs/<?= (int) ($dataClub->id ?? 0) ?>/tournaments" class="tab-link <?= $activeTab === 'tournaments' ? 'active' : '' ?>">Giải đấu (<?= (int) ($tabs['tournaments']['total'] ?? 0) ?>)</a>
        </div>

        <?php if ($activeTab === 'overview'): ?>
            <div class="row" style="margin-top:12px">
                <div class="panel">
                    <strong>Thông tin CLB</strong>
                    <div class="muted">Mô tả:</div>
                    <p><?= nl2br(esc($dataClub->description_vi ?: $dataClub->description_en ?: 'Chưa cập nhật mô tả.')) ?></p>
                </div>
                <div class="panel">
                    <strong>Hệ thống dữ liệu</strong>
                    <p class="muted">Thành viên: <b><?= (int) ($tabs['overview']['members'] ?? 0) ?></b></p>
                    <p class="muted">Lịch sử trận: <b><?= (int) ($tabs['overview']['history'] ?? 0) ?></b></p>
                    <p class="muted">Bài viết: <b><?= (int) ($tabs['overview']['posts'] ?? 0) ?></b></p>
                    <p class="muted">Giải đấu: <b><?= (int) ($tabs['overview']['tournaments'] ?? 0) ?></b></p>
                    <a class="chip" href="/clubs/<?= (int) ($dataClub->id ?? 0) ?>/members">Xem tất cả thành viên</a>
                    <a class="chip" href="/clubs/<?= (int) ($dataClub->id ?? 0) ?>/history">Xem lịch sử thi đấu</a>
                    <a class="chip" href="/clubs/<?= (int) ($dataClub->id ?? 0) ?>/tournaments">Xem giải đấu</a>
                </div>
            </div>
        <?php elseif ($activeTab === 'members'): ?>
            <?php if ($members): ?>
                <table>
                    <thead>
                        <tr><th>VĐV</th><th>Mã</th><th>Vai trò</th><th>Tình trạng</th><th>Đã tham gia</th><th>Thao tác</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($members as $member): ?>
                            <tr>
                                <td><?= esc($member->full_name) ?></td>
                                <td><a href="/players/<?= (int) ($member->player_id ?? 0) ?>"><?= esc($member->player_code ?: $member->player_id) ?></a></td>
                                <td><?= esc($member->role) ?></td>
                                <td><?= esc($member->status ?? 'active') ?><?= (($member->is_primary ?? 0) ? ' · chính' : '') ?></td>
                                <td><?= esc($formatDate($member->joined_at ?? null)) ?></td>
                                <td><a href="/players/<?= (int) ($member->player_id ?? 0) ?>#matches">Xem lịch sử</a> · <a href="/players/<?= (int) ($member->player_id ?? 0) ?>">Hồ sơ</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?><div class="muted">Chưa có thành viên.</div><?php endif; ?>
        <?php elseif ($activeTab === 'history'): ?>
            <?php if ($history): ?>
                <table>
                    <thead><tr><th>Ngày</th><th>Kết quả</th><th>Giải đấu</th><th>Đối thủ/VP</th><th>Điểm số</th></tr></thead>
                    <tbody>
                        <?php foreach ($history as $row): ?>
                            <tr>
                                <td><?= esc($formatDate($row->match_date ?? null)) ?></td>
                                <td><?= esc($row->result ?? '-') ?></td>
                                <td><?= esc($row->tournament_name ?? '-') ?></td>
                                <td><a href="/players/<?= (int) ($row->player_id ?? 0) ?>"><?= esc($row->player_name ?? 'VĐV') ?></a></td>
                                <td><?= esc($row->score ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?><div class="muted">Chưa có lịch sử trận công khai.</div><?php endif; ?>
        <?php elseif ($activeTab === 'posts'): ?>
            <?php if ($posts): ?>
                <?php foreach ($posts as $post): ?>
                    <div class="panel" style="margin-bottom:10px">
                        <strong><?= esc($post->title) ?></strong>
                        <div class="muted">Loại: <?= esc($post->type) ?> · <?= esc($formatDateTime($post->created_at ?? null)) ?></div>
                        <p><a href="/articles/<?= (int) $post->id ?>">Xem chi tiết bài viết</a></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?><div class="muted">Chưa có bài viết công khai.</div><?php endif; ?>
        <?php else: ?>
            <?php if ($tournaments): ?>
                <table>
                    <thead><tr><th>Giải đấu</th><th>Trạng thái</th><th>Ngày đăng ký</th></tr></thead>
                    <tbody>
                        <?php foreach ($tournaments as $tournament): ?>
                            <tr>
                                <td><a href="/tournaments/<?= (int) $tournament->id ?>"><?= esc($tournament->tournament_name) ?></a></td>
                                <td><?= esc($tournament->status ?? '-') ?></td>
                                <td><?= esc($formatDate($tournament->registered_at ?? null)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?><div class="muted">Chưa có giải đấu tham gia.</div><?php endif; ?>
        <?php endif; ?>
    </section>

    <aside class="panel">
        <h3 style="margin-top:0">Xem nhanh</h3>
        <div class="muted">CLB ID: <?= esc((int) ($dataClub->id ?? 0)) ?></div>
        <div class="muted">Logo: <?= esc($dataClub->logo ?: '-') ?></div>
        <div class="muted">Owner player: <?= esc($dataClub->owner_player_id ?: '-') ?></div>
        <a class="chip" href="/clubs/<?= (int) ($dataClub->id ?? 0) ?>">Chế độ điều hành</a>
        <a class="chip" href="/tournaments?club_id=<?= (int) ($dataClub->id ?? 0) ?>">Giải đấu liên quan</a>
    </aside>
</div>
</body>
</html>
