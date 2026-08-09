<!DOCTYPE html>
<html lang="<?= esc($current_locale ?? 'vi') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>body{background:#f7fafc}.register-shell{max-width:760px}.panel{border:1px solid #e2e8f0;border-radius:8px;background:white;padding:24px}</style>
</head>
<body>
<main class="container py-4 register-shell">
    <a href="/tournaments/<?= esc($tournament->slug_vi) ?>" class="btn btn-outline-secondary btn-sm mb-3"><i class="bi bi-arrow-left"></i></a>
    <div class="panel">
        <h1 class="h3 mb-1"><?= esc(lang('Tournament.register')) ?></h1>
        <p class="text-muted"><?= esc($localized($tournament, 'name')) ?></p>
        <?= flash_message() ?>
        <form method="post" action="/tournaments/<?= esc($tournament->slug_vi) ?>/register" class="row g-3">
            <?= csrf_field() ?>
            <div class="col-12">
                <label class="form-label"><?= esc(lang('Tournament.category')) ?></label>
                <select name="category_id" class="form-select" required>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category->id ?>"><?= esc($localized($category, 'name')) ?> - <?= format_money($category->registration_fee) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6"><label class="form-label"><?= esc(lang('Tournament.contact_name')) ?></label><input name="contact_name" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label"><?= esc(lang('Tournament.contact_phone')) ?></label><input name="contact_phone" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label"><?= esc(lang('Tournament.contact_email')) ?></label><input type="email" name="contact_email" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Team ID</label><input name="team_id" class="form-control" placeholder="Nếu đăng ký theo đội"></div>
            <div class="col-12"><label class="form-label"><?= esc(lang('Tournament.note')) ?></label><textarea name="note" class="form-control" rows="3"></textarea></div>
            <div class="col-12"><button class="btn btn-success btn-lg w-100"><?= esc(lang('Tournament.submit')) ?></button></div>
        </form>
    </div>
</main>
</body>
</html>
