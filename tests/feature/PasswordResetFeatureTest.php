<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * TC M1 — Luồng quên/đặt lại mật khẩu (end-to-end trên pickball_test)
 * Tài khoản dùng để test: owner@pickleballpro.com
 */
class PasswordResetFeatureTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private string $email = 'owner@pickleballpro.com';

    /** Đảm bảo trạng thái sạch trước mỗi test (idempotent, chạy lại được) */
    protected function setUp(): void
    {
        parent::setUp();

        $db   = \Config\Database::connect();
        $user = $db->table('users')->where('email', $this->email)->get()->getRowArray();

        if ($user) {
            // Khôi phục mật khẩu gốc + dọn token/lịch sử còn sót từ lần chạy trước
            $db->table('users')->where('email', $this->email)
                ->update(['password' => password_hash('password', PASSWORD_DEFAULT)]);
            $db->table('password_reset_tokens')->where('email', $this->email)->delete();
            $db->table('password_histories')->where('user_id', $user['id'])->delete();
            $db->table('login_attempts')->where('email', $this->email)->delete();
        }
    }

    /** Trang quên mật khẩu hiển thị */
    public function testForgotPasswordPageLoads(): void
    {
        $result = $this->call('GET', '/forgot-password');
        $result->assertOK();
    }

    /** Gửi email hợp lệ → báo thành công + tạo token (dev có dev_reset_url) */
    public function testForgotPasswordCreatesToken(): void
    {
        $result = $this->call('POST', '/forgot-password', ['email' => $this->email]);

        $result->assertRedirect();
        $result->assertSessionHas('success');
        $result->assertSessionHas('dev_reset_url');

        $token = \Config\Database::connect()
            ->table('password_reset_tokens')
            ->where('email', $this->email)
            ->where('used_at', null)
            ->orderBy('id', 'DESC')
            ->get()->getRowArray();

        $this->assertNotNull($token, 'Phải tạo token trong DB');
        $this->assertGreaterThan(time(), strtotime($token['expires_at']));
    }

    /** Email không tồn tại vẫn báo thành công (chống dò tài khoản), không tạo token */
    public function testForgotPasswordUnknownEmailStillSucceeds(): void
    {
        $result = $this->call('POST', '/forgot-password', ['email' => 'khong-co-that@example.com']);

        $result->assertRedirect();
        $result->assertSessionHas('success');
        $result->assertSessionMissing('dev_reset_url');
    }

    /** Token rác → form reset redirect báo lỗi */
    public function testResetPageRejectsInvalidToken(): void
    {
        $result = $this->call('GET', '/reset-password/token-rac-khong-ton-tai');

        $result->assertRedirect();
        $result->assertSessionHas('error');
    }

    /** Luồng đầy đủ: tạo token → mở form → đổi MK → login bằng MK mới */
    public function testFullResetFlow(): void
    {
        $db = \Config\Database::connect();
        $userBefore = $db->table('users')->where('email', $this->email)->get()->getRowArray();
        $oldHash    = $userBefore['password'];

        // 1. Tạo token
        $forgot = $this->call('POST', '/forgot-password', ['email' => $this->email]);
        $forgot->assertSessionHas('dev_reset_url');
        $devUrl  = session('dev_reset_url') ?? $forgot->response()->getHeaderLine('Location');
        $token   = \Config\Database::connect()
            ->table('password_reset_tokens')->where('email', $this->email)->where('used_at', null)
            ->orderBy('id', 'DESC')->get()->getRow('token');
        $this->assertNotEmpty($token);

        // 2. Mở form reset bằng token
        $page = $this->call('GET', "/reset-password/{$token}");
        $page->assertOK();

        // 3. Đổi mật khẩu
        $reset = $this->call('POST', '/reset-password', [
            'token'            => $token,
            'password'         => 'matkhaumoi123',
            'password_confirm' => 'matkhaumoi123',
        ]);
        $reset->assertRedirect();
        $reset->assertSessionHas('success');

        // 4. Hash đã thay đổi + token đã dùng + lịch sử đã lưu
        $userAfter = $db->table('users')->where('email', $this->email)->get()->getRowArray();
        $this->assertNotSame($oldHash, $userAfter['password']);
        $this->assertTrue(password_verify('matkhaumoi123', $userAfter['password']));

        $tokenRow = $db->table('password_reset_tokens')->where('token', $token)->get()->getRowArray();
        $this->assertNotNull($tokenRow['used_at'], 'Token phải bị đánh dấu đã dùng');

        $historyCount = $db->table('password_histories')->where('user_id', $userBefore['id'])->countAllResults();
        $this->assertGreaterThan(0, $historyCount, 'Phải lưu hash cũ vào lịch sử');

        // 5. Token đã dùng → không dùng lại được
        $reuse = $this->call('POST', '/reset-password', [
            'token'            => $token,
            'password'         => 'matkhaukhac456',
            'password_confirm' => 'matkhaukhac456',
        ]);
        $reuse->assertSessionHas('error');

        // 6. Không được đặt lại mật khẩu trùng mật khẩu vừa dùng
        $forgot2 = $this->call('POST', '/forgot-password', ['email' => $this->email]);
        $token2  = $db->table('password_reset_tokens')->where('email', $this->email)->where('used_at', null)
            ->orderBy('id', 'DESC')->get()->getRow('token');
        $reuseOld = $this->call('POST', '/reset-password', [
            'token'            => $token2,
            'password'         => 'matkhaumoi123', // trùng MK vừa đặt ở bước 3
            'password_confirm' => 'matkhaumoi123',
        ]);
        $reuseOld->assertSessionHas('error');

        // 7. Đăng nhập bằng mật khẩu mới thành công
        $login = $this->call('POST', '/login', [
            'email'    => $this->email,
            'password' => 'matkhaumoi123',
        ]);
        $login->assertSessionHas('isLoggedIn', true);

        // Dọn dẹp: khôi phục mật khẩu gốc 'password' cho các test khác
        $db->table('users')->where('email', $this->email)->update(['password' => password_hash('password', PASSWORD_DEFAULT)]);
        $db->table('password_reset_tokens')->where('email', $this->email)->delete();
        $db->table('password_histories')->where('user_id', $userBefore['id'])->delete();
    }
}
