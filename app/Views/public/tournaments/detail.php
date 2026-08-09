<!DOCTYPE html>
<html lang="<?= esc($current_locale ?? 'vi') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body{background:#f7fafc;color:#132238}.hero{min-height:440px;background:#0f766e;color:white;display:flex;align-items:end}.hero.has-image{background-size:cover;background-position:center}.hero-shade{width:100%;background:linear-gradient(180deg,rgba(0,0,0,.08),rgba(0,0,0,.62));padding:120px 0 36px}.info-tile{border:1px solid #e2e8f0;border-radius:8px;background:white;padding:18px;height:100%}.sponsor-logo{width:72px;height:72px;object-fit:contain;border:1px solid #e5e7eb;border-radius:8px;background:white;padding:8px}
    </style>
</head>
<body>
<?php $shareUrl = current_url(); ?>
<?php $heroImage = $tournament->banner ?: asset_url('assets/img/tournament-fallback.png'); ?>
<header class="hero has-image" style="background-image:url('<?= esc($heroImage) ?>')">
    <div class="hero-shade">
        <div class="container">
            <a href="/tournaments" class="btn btn-light btn-sm mb-3"><i class="bi bi-arrow-left"></i></a>
            <h1 class="display-5 fw-bold"><?= esc($localized($tournament, 'name')) ?></h1>
            <p class="lead mb-3"><?= esc($localized($tournament, 'description')) ?></p>
            <div class="d-flex gap-2 flex-wrap">
                <?php if ($tournament->status === 'open'): ?><a href="/tournaments/<?= esc($tournament->slug_vi) ?>/register" class="btn btn-success btn-lg"><?= esc(lang('Tournament.register')) ?></a><?php endif; ?>
                <a class="btn btn-light" target="_blank" href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($shareUrl) ?>"><i class="bi bi-facebook"></i> Facebook</a>
                <a class="btn btn-light" target="_blank" href="https://zalo.me/share?u=<?= urlencode($shareUrl) ?>">Zalo</a>
            </div>
        </div>
    </div>
</header>
<main class="container py-4">
    <?= flash_message() ?>
    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="info-tile"><span class="text-muted"><?= esc(lang('Tournament.registration_open')) ?></span><h5><?= esc(format_datetime($tournament->registration_start)) ?></h5></div></div>
        <div class="col-md-4"><div class="info-tile"><span class="text-muted"><?= esc(lang('Tournament.registration_closed')) ?></span><h5><?= esc(format_datetime($tournament->registration_end)) ?></h5></div></div>
        <div class="col-md-4"><div class="info-tile"><span class="text-muted"><?= esc(lang('Tournament.fee')) ?></span><h5><?= format_money($tournament->registration_fee) ?></h5></div></div>
    </div>

    <section class="mb-4">
        <h2 class="h4"><?= esc(lang('Tournament.categories')) ?></h2>
        <div class="row g-3">
            <?php foreach ($categories as $category): ?>
                <div class="col-md-4"><div class="info-tile"><h3 class="h6"><?= esc($localized($category, 'name')) ?></h3><div class="text-muted"><?= esc($category->category_type) ?></div><strong><?= format_money($category->registration_fee) ?></strong></div></div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="mb-4">
        <h2 class="h4"><?= esc(lang('Tournament.rules')) ?></h2>
        <div class="info-tile" style="white-space:pre-wrap"><?= esc($localized($rule ?? (object) [], 'rule_content') ?: '-') ?></div>
    </section>

    <section>
        <h2 class="h4"><?= esc(lang('Tournament.sponsors')) ?></h2>
        <div class="d-flex gap-3 flex-wrap">
            <?php foreach ($sponsors as $sponsor): ?>
                <a href="<?= esc($sponsor->website ?: '#') ?>" class="text-decoration-none text-dark d-flex align-items-center gap-2">
                    <?php if ($sponsor->logo): ?><img class="sponsor-logo" src="<?= esc($sponsor->logo) ?>" alt="<?= esc($sponsor->sponsor_name) ?>"><?php endif; ?>
                    <strong><?= esc($sponsor->sponsor_name) ?></strong>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
</main>
</body>
</html>
