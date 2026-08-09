<?php

namespace Tests\Unit;

use App\Services\AuthSecurityService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * TC M1 — AuthSecurityService: chống brute-force + lịch sử mật khẩu
 */
class AuthSecurityServiceTest extends CIUnitTestCase
{
    // Không dùng DatabaseTestTrait vì test DB là snapshot cố định (pickball_test),
    // không phải DB migrate từ đầu. Refresh sẽ drop toàn bộ bảng.

    protected AuthSecurityService $service;
    private string $testEmail = 'bruteforce-test@example.com';

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AuthSecurityService();
        $this->service->clearAttempts($this->testEmail);
    }

    protected function tearDown(): void
    {
        $this->service->clearAttempts($this->testEmail);
        parent::tearDown();
    }

    /** Dưới 5 lần sai → không bị khóa */
    public function testNotLockedBelowMaxAttempts(): void
    {
        for ($i = 0; $i < 4; $i++) {
            $this->service->recordAttempt($this->testEmail, '127.0.0.1', 'phpunit', false);
        }

        $this->assertSame(0, $this->service->lockedSecondsRemaining($this->testEmail, '127.0.0.1'));
        $this->assertSame(1, $this->service->remainingAttempts($this->testEmail, '127.0.0.1'));
    }

    /** 5 lần sai liên tiếp → bị khóa > 0 giây */
    public function testLockedAfterMaxAttempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->service->recordAttempt($this->testEmail, '127.0.0.1', 'phpunit', false);
        }

        $locked = $this->service->lockedSecondsRemaining($this->testEmail, '127.0.0.1');
        $this->assertGreaterThan(0, $locked);
        $this->assertLessThanOrEqual(15 * 60, $locked);
        $this->assertSame(0, $this->service->remainingAttempts($this->testEmail, '127.0.0.1'));
    }

    /** Xóa lần sai sau khi đăng nhập thành công → hết khóa */
    public function testClearAttemptsUnlocks(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->service->recordAttempt($this->testEmail, '127.0.0.1', 'phpunit', false);
        }
        $this->assertGreaterThan(0, $this->service->lockedSecondsRemaining($this->testEmail, '127.0.0.1'));

        $this->service->clearAttempts($this->testEmail);

        $this->assertSame(0, $this->service->lockedSecondsRemaining($this->testEmail, '127.0.0.1'));
        $this->assertSame(5, $this->service->remainingAttempts($this->testEmail, '127.0.0.1'));
    }

    /** Lần sai cũ ngoài cửa sổ 15 phút không tính */
    public function testOldAttemptsOutsideWindowAreIgnored(): void
    {
        $db = \Config\Database::connect();
        $old = date('Y-m-d H:i:s', time() - 3600); // 1 giờ trước

        for ($i = 0; $i < 5; $i++) {
            $db->table('login_attempts')->insert([
                'email'        => $this->testEmail,
                'ip_address'   => '127.0.0.1',
                'user_agent'   => 'phpunit',
                'success'      => 0,
                'attempted_at' => $old,
            ]);
        }

        $this->assertSame(0, $this->service->lockedSecondsRemaining($this->testEmail, '127.0.0.1'));

        $db->table('login_attempts')->where('email', $this->testEmail)->delete();
    }

    /** Chống dùng lại mật khẩu cũ */
    public function testPasswordReuseDetection(): void
    {
        $userId = 99001;
        $hashA  = password_hash('mat-khau-A', PASSWORD_DEFAULT);
        $hashB  = password_hash('mat-khau-B', PASSWORD_DEFAULT);

        $this->service->savePasswordHistory($userId, $hashA);
        $this->service->savePasswordHistory($userId, $hashB);

        $this->assertTrue($this->service->isPasswordReused($userId, 'mat-khau-A'));
        $this->assertTrue($this->service->isPasswordReused($userId, 'mat-khau-B'));
        $this->assertFalse($this->service->isPasswordReused($userId, 'mat-khau-moi-hoan-toan'));

        \Config\Database::connect()->table('password_histories')->where('user_id', $userId)->delete();
    }

    /** Theo dõi & xóa phiên đăng nhập */
    public function testSessionTracking(): void
    {
        $sid = 'phpunit-session-' . uniqid();

        $this->service->trackSession(1, $sid, '127.0.0.1', 'phpunit');
        $row = \Config\Database::connect()->table('user_sessions')->where('session_id', $sid)->get()->getRowArray();
        $this->assertNotNull($row);
        $this->assertSame(1, (int) $row['user_id']);

        $this->service->untrackSession($sid);
        $count = \Config\Database::connect()->table('user_sessions')->where('session_id', $sid)->countAllResults();
        $this->assertSame(0, $count);
    }
}
