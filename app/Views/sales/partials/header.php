<?php
$active = $active ?? 'solutions';
$pageTitle = $pageTitle ?? lang('Sales.brand');
$locale = $salesLocale ?? service('language')->getLocale();
$otherLocale = $locale === 'vi' ? 'en' : 'vi';
$targetLanguage = $otherLocale === 'vi' ? lang('Sales.vietnamese') : lang('Sales.english');
?>
<!doctype html>
<html lang="<?= esc($locale) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($pageTitle) ?> · <?= esc(lang('Sales.brand')) ?></title>
    <meta name="description" content="<?= esc(lang('Sales.solutions_lede')) ?>">
    <link rel="stylesheet" href="<?= esc(asset_url('assets/css/sales.css')) ?>">
</head>
<body class="sales-body">
<a class="sales-skip" href="#main-content">Skip to content</a>
<header class="sales-header">
    <div class="sales-container sales-nav">
        <a class="sales-brand" href="<?= esc(site_url('solutions')) ?>" aria-label="<?= esc(lang('Sales.brand')) ?>">
            <span class="sales-brand-mark">P</span>
            <span><strong>PICKLEBALL</strong><small>NETWORK</small></span>
        </a>
        <button class="sales-menu-toggle" type="button" aria-expanded="false" aria-controls="sales-menu" data-sales-menu-toggle>Menu</button>
        <nav id="sales-menu" class="sales-menu" aria-label="Main navigation">
            <a class="<?= $active === 'solutions' ? 'is-active' : '' ?>" href="<?= esc(site_url('solutions')) ?>"><?= esc(lang('Sales.nav_solutions')) ?></a>
            <a class="<?= $active === 'pricing' ? 'is-active' : '' ?>" href="<?= esc(site_url('pricing')) ?>"><?= esc(lang('Sales.nav_pricing')) ?></a>
            <a class="<?= $active === 'api' ? 'is-active' : '' ?>" href="<?= esc(site_url('developers')) ?>"><?= esc(lang('Sales.nav_api')) ?></a>
            <a href="<?= esc(site_url('ranking')) ?>"><?= esc(lang('Sales.nav_ranking')) ?></a>
        </nav>
        <div class="sales-actions">
            <a class="sales-language" href="<?= esc(site_url('locale/switch/' . $otherLocale)) ?>" title="<?= esc(lang('Sales.language_switch')) ?>">
                <span><?= $locale === 'vi' ? 'VI' : 'EN' ?></span><small><?= esc($targetLanguage) ?> ↗</small>
            </a>
            <a class="sales-login" href="<?= esc(site_url('login')) ?>"><?= esc(lang('Sales.nav_login')) ?></a>
            <a class="sales-button sales-button-small" href="<?= esc(site_url('demo')) ?>"><?= esc(lang('Sales.nav_trial')) ?></a>
        </div>
    </div>
</header>
<main id="main-content">
