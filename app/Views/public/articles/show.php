<?php
$locale = service('language')->getLocale();
$post = $post ?? (object) [];
$date = $post->created_at ? date('d/m/Y', strtotime((string) $post->created_at)) : '—';
?>
<!doctype html>
<html lang="<?= esc($locale) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= esc($pageTitle) ?></title>
    <meta name="description" content="<?= esc($metaDescription) ?>">
    <link rel="stylesheet" href="<?= esc(asset_url('assets/css/public-portal.css')) ?>">
    <link rel="stylesheet" href="<?= esc(asset_url('assets/css/athlete-public.css')) ?>">
    <link rel="stylesheet" href="<?= esc(asset_url('assets/css/article-public.css')) ?>">
</head>
<body class="athlete-body">
<header class="athlete-header"><div class="athlete-container nav-inner">
    <a class="athlete-brand" href="/"><span class="brand-badge">NP</span><span><b>NATIONAL</b><small>PICKLEBALL RANKING</small></span></a>
    <nav><a href="/ranking">BXH</a><a href="/players">VĐV</a><a href="/tournaments">Giải đấu</a><a href="/live">Live</a></nav>
    <div class="nav-right"><a href="/locale/switch/<?= $locale === 'vi' ? 'en' : 'vi' ?>"><?= $locale === 'vi' ? 'EN' : 'VI' ?></a><a class="nav-cta" href="/login">Khu vực của tôi ↗</a></div>
</div></header>
<main class="athlete-container article-page">
    <a class="back-link" href="<?= $post->player_code ? '/players/' . esc($post->player_code) . '#articles' : '/players' ?>">← Quay lại hồ sơ vận động viên</a>
    <article class="article-detail">
        <span class="eyebrow"><?= esc(strtoupper((string) ($post->type ?: 'ARTICLE'))) ?> · <?= esc($date) ?></span>
        <h1><?= esc($post->title) ?></h1>
        <?php if ($post->player_name): ?><p class="article-author">Bài viết của <strong><?= esc($post->player_name) ?></strong></p><?php endif; ?>
        <div class="article-body"><?= nl2br(esc((string) $post->body)) ?></div>
    </article>
</main>
<footer class="athlete-footer"><div class="athlete-container"><a class="athlete-brand" href="/"><span class="brand-badge">NP</span><span><b>NATIONAL</b><small>PICKLEBALL RANKING</small></span></a><p>National athlete identity · verified public stories.</p></div></footer>
</body></html>
