<?php

namespace App\Services;

use App\Models\PasswordResetTokenModel;
use App\Models\UserModel;

/**
 * Dịch vụ quên/đặt lại mật khẩu:
 * - Tạo token 1 lần, hết hạn sau 60 phút
 * - Gửi email (môi trường dev: ghi log link để test)
 * - Đặt lại mật khẩu: kiểm tra trùng lịch sử, ghi audit
 */
class PasswordResetService
{
    /** Thờị hạn token (phút) */
    public const TOKEN_TTL_MINUTES = 60;

    protected PasswordResetTokenModel $tokenModel;
    protected UserModel $userModel;
    protected AuthSecurityService $security;

    public function __construct()
    {
        $this->tokenModel = new PasswordResetTokenModel();
        $this->userModel  = new UserModel();
        $this->security   = new AuthSecurityService();
    }

    /**
     * Tạo token reset cho email. Luôn trả về true với controller
     * (không lộ email có tồn tại hay không — chống dò tài khoản),
     * nhưng chỉ thật sự tạo token khi user tồn tại.
     *
     * @return string|null token (null nếu email không tồn tại)
     */
    public function createToken(string $email, ?string $ip = null): ?string
    {
        $email = mb_strtolower(trim($email));
        $user  = $this->userModel->findByEmail($email);

        if (! $user) {
            return null;
        }

        // Vô hiệu mọi token cũ
        $this->tokenModel->invalidateAllForEmail($email);

        $token = bin2hex(random_bytes(32));

        $this->tokenModel->insert([
            'email'      => $email,
            'token'      => $token,
            'expires_at' => date('Y-m-d H:i:s', time() + self::TOKEN_TTL_MINUTES * 60),
            'created_ip' => $ip,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->sendResetEmail($email, $token);

        return $token;
    }

    /**
     * Xác minh token → trả về email nếu hợp lệ
     */
    public function verifyToken(string $token): ?string
    {
        $row = $this->tokenModel->findValidToken($token);
        return $row['email'] ?? null;
    }

    /**
     * Đặt lại mật khẩu bằng token.
     *
     * @return array{success: bool, error: string|null}
     */
    public function resetPassword(string $token, string $newPassword): array
    {
        $row = $this->tokenModel->findValidToken($token);

        if (! $row) {
            return ['success' => false, 'error' => lang('Auth.resetTokenInvalid')];
        }

        $user = $this->userModel->findByEmail($row['email']);

        if (! $user) {
            return ['success' => false, 'error' => lang('Auth.resetTokenInvalid')];
        }

        // Chống dùng lại mật khẩu hiện tại
        if (password_verify($newPassword, $user->password)) {
            return ['success' => false, 'error' => lang('Auth.passwordReused')];
        }

        // Chống dùng lại mật khẩu cũ
        if ($this->security->isPasswordReused((int) $user->id, $newPassword)) {
            return ['success' => false, 'error' => lang('Auth.passwordReused')];
        }

        $db = $this->userModel->db;
        $db->transStart();

        // Lưu hash hiện tại vào lịch sử trước khi đổi
        $this->security->savePasswordHistory((int) $user->id, $user->password);

        // Đổi mật khẩu (UserModel callback tự hash)
        $this->userModel->skipValidation(true);
        $updated = $this->userModel->update($user->id, ['password' => $newPassword]);
        $this->userModel->skipValidation(false);

        // Đánh dấu token đã dùng
        $this->tokenModel->update($row['id'], ['used_at' => date('Y-m-d H:i:s')]);

        $db->transComplete();

        if ($updated === false || $db->transStatus() === false) {
            return ['success' => false, 'error' => lang('Auth.resetFailed')];
        }

        return ['success' => true, 'error' => null];
    }

    /**
     * Gửi email reset. Môi trường development: ghi link vào log để test.
     */
    protected function sendResetEmail(string $email, string $token): void
    {
        $resetUrl = base_url("reset-password/{$token}");

        if (ENVIRONMENT !== 'production') {
            log_message('info', "[PasswordReset] Link đặt lại mật khẩu cho {$email}: {$resetUrl}");
        }

        $emailService = service('email');
        $emailService->setTo($email);
        $emailService->setSubject(lang('Auth.resetEmailSubject'));
        $emailService->setMessage(view('emails/reset_password', [
            'resetUrl' => $resetUrl,
            'ttl'      => self::TOKEN_TTL_MINUTES,
        ]));

        try {
            $emailService->send();
        } catch (\Throwable $e) {
            // Không chặn luồng nếu SMTP chưa cấu hình (dev)
            log_message('error', '[PasswordReset] Gửi email thất bại: ' . $e->getMessage());
        }
    }
}
