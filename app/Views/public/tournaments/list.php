<!DOCTYPE html>
<html lang="<?= esc($current_locale ?? 'vi') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body{background:#f6f8fb;color:#14213d}.hero{background:#0f766e;color:white;padding:48px 0 36px}.tournament-card{border:1px solid #e5e7eb;border-radius:8px;background:white;overflow:hidden;height:100%}.tournament-card img{height:190px;width:100%;object-fit:cover}.badge-soft{background:#dcfce7;color:#166534}.card-body{padding:20px}
    </style>
</head>
<body>
<header class="hero">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center gap-3">
            <div>
                <h1 class="display-6 fw-bold mb-2"><?= esc(lang('Tournament.public_title')) ?></h1>
                <p class="mb-0 opacity-75">Pickleball events, categories and online registration.</p>
            </div>
            <div class="btn-group">
                <a class="btn btn-light btn-sm" href="/locale/switch/vi">VI</a>
                <a class="btn btn-outline-light btn-sm" href="/locale/switch/en">EN</a>
            </div>
        </div>
    </div>
</header>
<main class="container py-4">
    <div class="row g-3">
        <?php foreach ($tournaments as $tournament): ?>
            <div class="col-md-6 col-xl-4">
                <article class="tournament-card">
                    <img src="<?= esc($tournament->banner ?: asset_url('assets/img/tournament-fallback.png')) ?>" alt="<?= esc($tournament->name_vi) ?>">
                    <div class="card-body">
                        <span class="badge badge-soft mb-2"><?= esc($tournament->status) ?></span>
                        <h2 class="h5"><?= esc(($current_locale ?? 'vi') === 'en' && $tournament->name_en ? $tournament->name_en : $tournament->name_vi) ?></h2>
                        <p class="text-muted small mb-3"><?= esc(format_date($tournament->start_date)) ?> - <?= esc(format_date($tournament->end_date)) ?></p>
                        <a href="/tournaments/<?= esc($tournament->slug_vi) ?>" class="btn btn-success w-100"><?= esc(lang('Tournament.register')) ?></a>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>
        <?php if (empty($tournaments)): ?>
            <div class="col-12"><div class="alert alert-light border">No tournaments available.</div></div>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
