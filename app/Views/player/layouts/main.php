<!DOCTYPE html>
<html lang="<?= esc(session('locale') ?? 'vi') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->renderSection('title') ?> | <?= esc(lang('App.app_name')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/assets/css/mobile.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg bg-body-tertiary border-bottom sticky-top">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="/player">Pickleball</a>
        <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#playerNav"><span class="navbar-toggler-icon"></span></button>
        <div id="playerNav" class="collapse navbar-collapse">
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="/player/bookings"><i class="bi bi-calendar-check"></i> Booking</a>
                <a class="nav-link" href="/player/teams"><i class="bi bi-people"></i> Team</a>
                <a class="nav-link" href="/player/matches"><i class="bi bi-controller"></i> Tìm kèo</a>
                <a class="nav-link" href="/player/profile"><i class="bi bi-person"></i> Hồ sơ</a>
            </div>
        </div>
    </div>
</nav>
<?php if (session('success')): ?><div class="container pt-3"><div class="alert alert-success"><?= esc(session('success')) ?></div></div><?php endif; ?>
<?php if (session('error')): ?><div class="container pt-3"><div class="alert alert-danger"><?= esc(session('error')) ?></div></div><?php endif; ?>
<?= $this->renderSection('content') ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
