<!DOCTYPE html>
<?php $currentLocale = service('language')->getLocale(); ?>
<html lang="<?= esc($currentLocale) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->renderSection('title') ?> | <?= esc(lang('App.app_name')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= esc(asset_url('assets/css/mobile.css')) ?>" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg bg-body-tertiary border-bottom sticky-top">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="/player">Pickleball</a>
        <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#playerNav"><span class="navbar-toggler-icon"></span></button>
        <div id="playerNav" class="collapse navbar-collapse">
            <div class="navbar-nav ms-auto">
                <a class="nav-link fw-semibold text-success" href="/player/bookings/create"><i class="bi bi-plus-circle"></i> <?= esc(lang('App.player_book_court')) ?></a>
                <a class="nav-link" href="/player/bookings"><i class="bi bi-calendar-check"></i> <?= esc(lang('App.player_booking')) ?></a>
                <a class="nav-link" href="/player/teams"><i class="bi bi-people"></i> <?= esc(lang('App.player_team')) ?></a>
                <a class="nav-link" href="/player/matches"><i class="bi bi-controller"></i> <?= esc(lang('App.player_find_match')) ?></a>
                <a class="nav-link" href="/player/open-play"><i class="bi bi-people"></i> <?= esc(lang('App.player_open_play')) ?></a>
                <a class="nav-link" href="/player/coaching"><i class="bi bi-person-video3"></i> <?= esc(lang('App.player_coach')) ?></a>
                <a class="nav-link" href="/player/competitions"><i class="bi bi-trophy"></i> <?= esc(lang('App.player_league')) ?></a>
                <a class="nav-link" href="/player/growth"><i class="bi bi-megaphone"></i> <?= esc(lang('App.player_offers')) ?></a>
                <a class="nav-link" href="/player/social"><i class="bi bi-heart"></i> <?= esc(lang('App.player_following')) ?></a>
                <a class="nav-link" href="/player/community"><i class="bi bi-chat-square-text"></i> <?= esc(lang('App.player_community')) ?></a>
                <a class="nav-link" href="/player/livestream"><i class="bi bi-broadcast-pin"></i> <?= esc(lang('App.player_livestream')) ?></a>
                <a class="nav-link" href="/player/profile"><i class="bi bi-person"></i> <?= esc(lang('App.player_profile')) ?></a>
                <a class="nav-link" href="<?= esc(site_url('locale/switch/' . ($currentLocale === 'vi' ? 'en' : 'vi'))) ?>"><i class="bi bi-translate"></i> <?= $currentLocale === 'vi' ? 'EN' : 'VI' ?></a>
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
