<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Testcase luồng đăng nhập web (session-based)
 *
 * Dữ liệu test: database pickball_test (bản sao pickball_db)
 * Tài khoản chuẩn: admin@pickleball.com / admin123
 */
class AuthFeatureTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    /** TC01: Trang đăng nhập hiển thị đúng, có hộp tài khoản demo */
    public function testLoginPageLoads(): void
    {
        $result = $this->call('GET', '/login');

        $result->assertOK();
        $result->assertSee(lang('Auth.loginTitle', [], 'vi'));
        $result->assertSee('admin@pickleball.com'); // hộp tài khoản demo (dev)
    }

    /** TC02: Trang chủ redirect về đăng nhập */
    public function testHomeRedirectsToLogin(): void
    {
        $result = $this->call('GET', '/');

        $result->assertRedirect();
    }

    /** TC03: Đăng nhập thất bại khi sai mật khẩu */
    public function testLoginFailsWithWrongPassword(): void
    {
        $result = $this->call('POST', '/login', [
            'email'    => 'admin@pickleball.com',
            'password' => 'sai-mat-khau',
        ]);

        $result->assertRedirect();
        $result->assertSessionHas('error');
        $result->assertSessionMissing('isLoggedIn');
    }

    /** TC04: Đăng nhập thất bại khi email không tồn tại */
    public function testLoginFailsWithUnknownEmail(): void
    {
        $result = $this->call('POST', '/login', [
            'email'    => 'khong-ton-tai@example.com',
            'password' => 'admin123',
        ]);

        $result->assertRedirect();
        $result->assertSessionHas('error');
    }

    /** TC05: Validate lỗi khi bỏ trống form */
    public function testLoginValidationErrors(): void
    {
        $result = $this->call('POST', '/login', [
            'email'    => 'khong-phai-email',
            'password' => '123',
        ]);

        $result->assertRedirect();
        $result->assertSessionHas('errors');
    }

    /** TC06: Đăng nhập thành công → vào dashboard, session đầy đủ */
    public function testLoginSuccess(): void
    {
        $result = $this->call('POST', '/login', [
            'email'    => 'admin@pickleball.com',
            'password' => 'admin123',
        ]);

        $result->assertRedirect();
        $result->assertSessionHas('isLoggedIn', true);
        $result->assertSessionHas('userId');
        $result->assertSessionHas('tenant_id');
    }

    /** TC07: (Chuyển sang unit test — xem tests/unit/AuthFilterTest.php) */

    /** TC08: Đã đăng nhập xem được dashboard */
    public function testAdminDashboardAfterLogin(): void
    {
        $result = $this->withSession([
                'isLoggedIn'    => true,
                'userId'        => 1,
                'user_id'       => 1,
                'username'      => 'admin',
                'is_superadmin' => true,
                'tenant_id'     => 1,
                'branch_id'     => 1,
                'locale'        => 'vi',
            ])
            ->call('GET', '/admin/dashboard');

        $result->assertOK();
    }

    /** TC09: Đăng xuất xóa session và về trang login */
    public function testLogout(): void
    {
        $result = $this->withSession(['isLoggedIn' => true, 'userId' => 1, 'user_id' => 1])
            ->call('GET', '/logout');

        $result->assertRedirect();
        $this->assertStringContainsString('/login', (string) $result->getRedirectUrl());
    }

    /** TC10: Chuyển đổi ngôn ngữ VI/EN */
    public function testSwitchLocale(): void
    {
        $result = $this->call('GET', '/locale/switch/vi');
        $result->assertRedirect();
        $result->assertSessionHas('locale', 'vi');

        $result = $this->call('GET', '/locale/switch/en');
        $result->assertSessionHas('locale', 'en');
    }
}
