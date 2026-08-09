<?php

namespace App\Services;

use App\Models\LoginAttemptModel;
use App\Models\PasswordHistoryModel;
use App\Models\UserSessionModel;

/**
 * Dịch vụ bảo mật xác thực:
 * - Chống brute-force: khóa sau N lần sai trong khoảng thờị gian nhất định
 * - Lịch sử mật khẩu: chống dùng lại mật khẩu cũ
 * - Theo dõi phiên đăng nhập
 */
class AuthSecurityService
{
    /** Số lần sai tối đa trước khi bị khóa tạm */
    public const MAX_ATTEMPTS = 5;

    /** Khoảng thờị gian (phút) tính giới hạn đăng nhập */
    public const LOCK_WINDOW_MINUTES = 15;

    /** Số mật khẩu cũ không được dùng lại */
    public const PASSWORD_HISTORY_LIMIT = 3;

    protected LoginAttemptModel $attemptModel;
    protected PasswordHistoryModel $historyModel;
    protected UserSessionModel $sessionModel;

    public function __construct()
    {
        $this->attemptModel  = new LoginAttemptModel();
        $this->historyModel  = new PasswordHistoryModel();
        $this->sessionModel  = new UserSessionModel();
    }

    /**
     * Ghi lại 1 lần đăng nhập (thành công hoặc thất bại)
     */
    public function recordAttempt(string $email, ?string $ip, ?string $userAgent, bool $success): void
    {
        $this->attemptModel->insert([
            'email'        => mb_strtolower(trim($email)),
            'ip_address'   => $ip,
            'user_agent'   => $userAgent ? mb_substr($userAgent, 0, 255) : null,
            'success'      => $success ? 1 : 0,
            'attempted_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Số lần đăng nhập sai còn lại trước khi bị khóa
     */
    public function remainingAttempts(string $email, ?string $ip = null): int
    {
        $failed = $this->countRecentFailures($email, $ip);
        return max(0, self::MAX_ATTEMPTS - $failed);
    }

    /**
     * Tài khoản/IP đang bị khóa tạm?
     * Trả về số giây còn lại của khóa (0 = không bị khóa)
     */
    public function lockedSecondsRemaining(string $email, ?string $ip = null): int
    {
        $since = date('Y-m-d H:i:s', time() - self::LOCK_WINDOW_MINUTES * 60);

        $lastFail = $this->attemptModel
            ->where('email', mb_strtolower(trim($email)))
            ->where('success', 0)
            ->where('attempted_at >=', $since)
            ->orderBy('attempted_at', 'DESC')
            ->findAll(self::MAX_ATTEMPTS);

        if (count($lastFail) < self::MAX_ATTEMPTS) {
            return 0;
        }

        // Lần sai thứ N gần nhất → tính thờị điểm mở khóa
        $oldestRelevant = strtotime(end($lastFail)['attempted_at']);
        $unlockAt       = $oldestRelevant + self::LOCK_WINDOW_MINUTES * 60;

        return max(0, $unlockAt - time());
    }

    /**
     * Xóa toàn bộ lần đăng nhập sai (gọi sau khi đăng nhập thành công)
     */
    public function clearAttempts(string $email): void
    {
        $this->attemptModel
            ->where('email', mb_strtolower(trim($email)))
            ->where('success', 0)
            ->delete();
    }

    /**
     * Lưu hash mật khẩu vào lịch sử
     */
    public function savePasswordHistory(int $userId, string $passwordHash): void
    {
        $this->historyModel->insert([
            'user_id'    => $userId,
            'password'   => $passwordHash,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Kiểm tra mật khẩu mới có trùng N mật khẩu gần nhất không
     */
    public function isPasswordReused(int $userId, string $plainPassword): bool
    {
        foreach ($this->historyModel->getRecentHashes($userId, self::PASSWORD_HISTORY_LIMIT) as $oldHash) {
            if (password_verify($plainPassword, $oldHash)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Đăng ký phiên đăng nhập
     */
    public function trackSession(int $userId, string $sessionId, ?string $ip, ?string $userAgent): void
    {
        $this->sessionModel->track($userId, $sessionId, $ip, $userAgent);
    }

    /**
     * Xóa phiên khi đăng xuất
     */
    public function untrackSession(string $sessionId): void
    {
        $this->sessionModel->removeBySessionId($sessionId);
    }

    /**
     * Đếm số lần sai gần đây trong cửa sổ khóa
     */
    protected function countRecentFailures(string $email, ?string $ip = null): int
    {
        $since = date('Y-m-d H:i:s', time() - self::LOCK_WINDOW_MINUTES * 60);

        return $this->attemptModel
            ->where('email', mb_strtolower(trim($email)))
            ->where('success', 0)
            ->where('attempted_at >=', $since)
            ->countAllResults();
    }
}
