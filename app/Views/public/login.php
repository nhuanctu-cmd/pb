<!DOCTYPE html>
<html lang="<?= session('locale') ?? 'en' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= lang('Auth.loginTitle') ?> | <?= lang('App.app_name') ?></title>
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
        .card-login .card-header {
            background: transparent;
            border-bottom: none;
            padding-bottom: 0;
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 0.75rem;
            font-weight: 600;
        }
        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 0.5rem 1rem rgba(102, 126, 234, 0.4);
            color: white;
        }
        .language-switch {
            position: absolute;
            top: 1rem;
            right: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="position-relative">
                    <div class="language-switch">
                        <div class="dropdown">
                            <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <?= session('locale') === 'vi' ? '🇻🇳 VI' : '🇺🇸 EN' ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="/locale/switch/en">🇺🇸 English</a></li>
                                <li><a class="dropdown-item" href="/locale/switch/vi">🇻🇳 Tiếng Việt</a></li>
                            </ul>
                        </div>
                    </div>

                    <div class="card card-login">
                        <div class="card-header text-center pt-4">
                            <h2 class="fw-bold">🏓</h2>
                            <h4 class="mt-2"><?= lang('App.app_name') ?></h4>
                            <p class="text-muted"><?= lang('Auth.loginSubtitle') ?></p>
                        </div>
                        <div class="card-body p-4">
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

                            <form action="/login" method="POST">
                                <div class="mb-3">
                                    <label class="form-label"><?= lang('Auth.email') ?></label>
                                    <input type="email" name="email" class="form-control form-control-lg" placeholder="email@example.com" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label"><?= lang('Auth.password') ?></label>
                                    <input type="password" name="password" class="form-control form-control-lg" placeholder="••••••••" required>
                                </div>
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" name="remember" id="remember">
                                    <label class="form-check-label" for="remember"><?= lang('Auth.rememberMe') ?></label>
                                </div>
                                <button type="submit" class="btn btn-login w-100 btn-lg"><?= lang('Auth.login') ?></button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
