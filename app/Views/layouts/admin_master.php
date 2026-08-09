<!DOCTYPE html>
<html lang="<?= esc($current_locale ?? session('locale') ?? 'vi') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($pageTitle ?? 'Admin') ?> | <?= esc(lang('App.app_name')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/assets/css/app.css" rel="stylesheet">
    <link href="/assets/css/admin-layout.css" rel="stylesheet">
    <link href="/assets/css/components.css" rel="stylesheet">
    <link href="/assets/css/erp-table.css" rel="stylesheet">
    <link href="/assets/css/forms.css" rel="stylesheet">
    <link href="/assets/css/dashboard.css" rel="stylesheet">
    <link href="/assets/css/mobile.css" rel="stylesheet">
    <?= $this->renderSection('styles') ?>
</head>
<body class="erp-body">
    <div class="erp-page">
        <?= view('layouts/partials/sidebar', get_defined_vars()) ?>
        <?= view('layouts/partials/topbar', get_defined_vars()) ?>

        <main class="erp-content">
            <div class="erp-content-inner">
                <?= view('layouts/partials/flash_toast') ?>
                <?= view('layouts/partials/page_header', [
                    'title' => $pageTitle ?? '',
                    'description' => $pageDescription ?? '',
                    'actions' => $pageActions ?? '',
                ]) ?>
                <?= $this->renderSection('content') ?>
            </div>
        </main>

        <?= view('layouts/partials/right_drawer') ?>
        <?= view('layouts/partials/footer') ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/app.js"></script>
    <script src="/assets/js/admin-ui.js"></script>
    <?= $this->renderSection('scripts') ?>
</body>
</html>
