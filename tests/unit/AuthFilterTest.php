<?php

namespace Tests\Unit;

use App\Filters\AuthFilter;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\URI;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * TC07: AuthFilter — bảo vệ khu vực admin/player bằng session
 */
class AuthFilterTest extends CIUnitTestCase
{
    private function makeRequest(string $path = '/admin/dashboard'): IncomingRequest
    {
        return new IncomingRequest(
            new \Config\App(),
            new URI('http://example.com' . $path),
            'php://input',
            new UserAgent()
        );
    }

    /** Chưa đăng nhập → redirect về /login */
    public function testRedirectsGuestsToLogin(): void
    {
        $session = service('session');
        $session->remove('isLoggedIn');
        $session->remove('userId');

        $filter = new AuthFilter();
        $result = $filter->before($this->makeRequest());

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertStringContainsString('/login', $result->getHeaderLine('Location'));
    }

    /** Đã đăng nhập → được đi tiếp (trả về null) */
    public function testAllowsLoggedInUser(): void
    {
        $session = service('session');
        $session->set('isLoggedIn', true);
        $session->set('userId', 1);

        $filter = new AuthFilter();
        $result = $filter->before($this->makeRequest());

        $this->assertNull($result);

        $session->destroy();
    }

    /** Session mất userId (hết hạn) → hủy session + redirect login */
    public function testExpiredSessionIsDestroyed(): void
    {
        $session = service('session');
        $session->set('isLoggedIn', true);
        $session->remove('userId');

        $filter = new AuthFilter();
        $result = $filter->before($this->makeRequest());

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertStringContainsString('/login', $result->getHeaderLine('Location'));

        $session->destroy();
    }
}
