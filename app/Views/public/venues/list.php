<?php
$locale = service('language')->getLocale();
?>
<!doctype html>
<html lang="<?= esc($locale) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= esc($pageTitle) ?></title>
    <meta name="description" content="<?= esc($metaDescription ?? 'Danh sách cơ sở') ?>">
    <link rel="stylesheet" href="<?= esc(asset_url('assets/css/public-portal.css')) ?>">
    <style>
        .venue-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:15px}
        .venue-card{background:#fff;border:1px solid #dde7e5;padding:14px;border-radius:14px}
        .venue-card h2{margin:0 0 8px}
        .venue-meta{color:#6f8487;font-size:13px;display:grid;gap:5px}
        .venue-stats{display:flex;gap:10px;flex-wrap:wrap;margin-top:10px}
        .venue-stats .chip{display:inline-block;background:#edf6f4;color:#0f5a63;border-radius:99px;padding:4px 10px;font-size:12px}
        .toolbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;gap:12px;flex-wrap:wrap}
        .toolbar form{display:flex;gap:8px;flex-wrap:wrap}
        .toolbar input,.toolbar select{padding:8px 10px;border-radius:8px;border:1px solid #cfd8d5;background:#fff}
    </style>
</head>
<body>
<header class="portal-topbar">
    <div class="portal-nav portal-container">
        <a class="tp-brand" href="/"><span class="tp-mark">NP</span><span><strong>NATIONAL</strong><small> PICKLEBALL</small></span></a>
        <nav><a href="/ranking">BXH</a><a href="/players">VĐV</a><a href="/clubs">CLB</a><a class="is-active" href="/venues">Sân</a></nav>
    </div>
</header>
<main class="portal-container">
    <div class="toolbar">
        <h1>Danh sách cơ sở</h1>
        <form method="get" action="/venues">
            <input type="text" name="q" value="<?= esc($filters['q'] ?? '') ?>" placeholder="Tìm theo tên / địa chỉ">
            <select name="status">
                <option value="">Tất cả trạng thái</option>
                <option value="active" <?= (($filters['status'] ?? '') === 'active') ? 'selected' : '' ?>>Đang hoạt động</option>
                <option value="inactive" <?= (($filters['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Tạm dừng</option>
            </select>
            <button class="btn btn-sm btn-success" type="submit">Lọc</button>
        </form>
    </div>
    <section class="venue-grid">
        <?php if (! empty($venues)): foreach ($venues as $venue): ?>
            <article class="venue-card">
                <h2><a href="/venues/<?= (int) $venue->id ?>"><?= esc($venue->name_vi ?: $venue->code) ?></a></h2>
                <div class="venue-meta">
                    <span>Mã: <?= esc($venue->code) ?></span>
                    <span><?= esc($venue->address ?: 'Chưa cập nhật địa chỉ') ?></span>
                    <span><?= esc($venue->status) ?></span>
                    <span><?= esc(($venue->branch_count ?? 0)) ?> chi nhánh · <?= esc(($venue->court_count ?? 0)) ?> sân · <?= esc(($venue->club_count ?? 0)) ?> CLB liên kết</span>
                </div>
                <div class="venue-stats">
                    <a class="chip" href="/venues/<?= (int) $venue->id ?>">Xem chi tiết</a>
                    <a class="chip" href="/clubs">Xem CLB</a>
                </div>
            </article>
        <?php endforeach; else: ?>
            <article class="venue-card">Chưa có cơ sở nào phù hợp bộ lọc.</article>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
