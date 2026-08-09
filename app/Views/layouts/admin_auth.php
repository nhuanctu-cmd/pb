<!DOCTYPE html>
<html lang="<?= esc(session('locale') ?? 'vi') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($pageTitle ?? 'Đăng nhập') ?> | <?= esc(lang('App.app_name')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/app.css" rel="stylesheet">
    <link href="/assets/css/components.css" rel="stylesheet">
</head>
<body class="erp-body">
    <main class="min-vh-100 d-flex align-items-center justify-content-center p-4">
        <?= $this->renderSection('content') ?>
    </main>
</body>
</html>
