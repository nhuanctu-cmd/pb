<?php
$locale = service('language')->getLocale();
?>
<!doctype html>
<html lang="<?= esc($locale) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= esc($pageTitle) ?></title>
    <meta name="description" content="<?= esc($metaDescription ?? 'Danh bạ câu lạc bộ') ?>">
    <link rel="stylesheet" href="<?= esc(asset_url('assets/css/public-portal.css')) ?>">
    <style>
        .club-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px}
        .club-card{background:#fff;border:1px solid #dde9e7;padding:14px;border-radius:14px}
        .club-card h2{margin:0 0 8px}
        .chip{display:inline-block;background:#edf6f4;border-radius:99px;padding:4px 10px;font-size:12px;margin:0 6px 6px 0}
        .toolbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;gap:12px;flex-wrap:wrap}
        .toolbar form{display:flex;gap:8px;flex-wrap:wrap}
        .toolbar input,.toolbar select{padding:8px 10px;border-radius:8px;border:1px solid #cfd8d5;background:#fff}
    </style>
</head>
<body>
<header class="portal-topbar">
    <div class="portal-container">
        <a class="tp-brand" href="/"><span class="tp-mark">NP</span><span><strong>NATIONAL</strong><small> PICKLEBALL</small></span></a>
        <nav><a href="/ranking">BXH</a><a href="/players">VĐV</a><a class="is-active" href="/clubs">CLB</a><a href="/venues">SÂN</a></nav>
    </div>
</header>
<main class="portal-container">
    <div class="toolbar">
        <h1>Danh bạ câu lạc bộ</h1>
        <form method="get" action="/clubs">
            <input type="text" name="q" value="<?= esc($filters['q'] ?? '') ?>" placeholder="Tìm tên CLB">
            <select name="status">
                <option value="">Tất cả</option>
                <option value="active" <?= (($filters['status'] ?? '') === 'active') ? 'selected' : '' ?>>Đang hoạt động</option>
                <option value="inactive" <?= (($filters['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Tạm ngừng</option>
            </select>
            <button class="btn btn-sm btn-success" type="submit">Lọc</button>
        </form>
    </div>
    <section class="club-grid">
        <?php if (! empty($clubs)): foreach ($clubs as $club): ?>
            <article class="club-card">
                <h2><a href="/clubs/<?= (int) $club->id ?>"><?= esc($club->name_vi ?: $club->name_en) ?></a></h2>
                <div>Trạng thái: <?= esc($club->status) ?></div>
                <div>Thành viên: <?= esc((string) ($club->member_count ?? 0)) ?></div>
                <div style="margin-top:8px">
                    <a class="chip" href="/clubs/<?= (int) $club->id ?>">Xem hồ sơ CLB</a>
                    <?php if (! empty($filters['show_memberships'] ?? false)): ?>
                        <span class="chip">Có lịch sử thi đấu</span>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; else: ?>
            <article class="club-card">Chưa có CLB nào phù hợp bộ lọc.</article>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
