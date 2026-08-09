<!DOCTYPE html>
<html lang="<?= session('locale') ?? 'vi' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= lang('Auth.resetPasswordTitle') ?> | <?= lang('App.app_name') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .card-login {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 0.75rem;
            font-weight: 600;
        }
        .btn-login:hover { color: white; opacity: 0.95; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card card-login">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <h2 class="fw-bold">🏓</h2>
                            <h4 class="mt-2"><?= lang('Auth.resetPasswordTitle') ?></h4>
                            <p class="text-muted"><?= lang('Auth.resetPasswordSubtitle') ?></p>
                        </div>

                        <?= flash_message() ?>

                        <?php if (session()->has('errors')): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach (session('errors') as $error): ?>
                                        <li><?= esc($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form action="/reset-password" method="POST">
                            <input type="hidden" name="token" value="<?= esc($token) ?>">
                            <div class="mb-3">
                                <label class="form-label"><?= lang('Auth.newPassword') ?></label>
                                <input type="password" name="password" class="form-control form-control-lg"
                                       placeholder="<?= lang('Auth.newPassword') ?>" required autofocus>
                                <div class="form-text"><?= lang('Auth.passwordMinHint') ?></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><?= lang('Auth.confirmPassword') ?></label>
                                <input type="password" name="password_confirm" class="form-control form-control-lg"
                                       placeholder="<?= lang('Auth.confirmPassword') ?>" required>
                            </div>
                            <button type="submit" class="btn btn-login w-100 btn-lg"><?= lang('Auth.resetPassword') ?></button>
                        </form>

                        <div class="text-center mt-3">
                            <a href="/login"><?= lang('Auth.backToLogin') ?></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
