<!DOCTYPE html>
<html lang="vi">
<head><meta charset="UTF-8"></head>
<body style="font-family: Arial, sans-serif; color: #333; line-height: 1.6;">
    <div style="max-width: 560px; margin: 0 auto; padding: 24px; border: 1px solid #eee; border-radius: 12px;">
        <h2 style="margin-top: 0;">🏓 <?= lang('App.app_name') ?></h2>
        <p><?= lang('Auth.resetEmailGreeting') ?></p>
        <p><?= lang('Auth.resetEmailBody', [$ttl]) ?></p>
        <p style="text-align: center; margin: 32px 0;">
            <a href="<?= esc($resetUrl) ?>"
               style="background: #667eea; color: #fff; padding: 12px 28px; border-radius: 8px; text-decoration: none; font-weight: bold;">
                <?= lang('Auth.resetPassword') ?>
            </a>
        </p>
        <p style="word-break: break-all; color: #888; font-size: 13px;"><?= esc($resetUrl) ?></p>
        <hr style="border: none; border-top: 1px solid #eee;">
        <p style="color: #888; font-size: 13px;"><?= lang('Auth.resetEmailIgnore') ?></p>
    </div>
</body>
</html>
