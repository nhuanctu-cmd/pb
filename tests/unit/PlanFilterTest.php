<?php

namespace Tests\Unit;

use App\Filters\PlanFilter;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\URI;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * TC — PlanFilter (SaaS feature gating):
 * tenant free bị chặn POS/tournament, tenant pro được dùng, super admin vượt qua.
 */
class PlanFilterTest extends CIUnitTestCase
{
    private function makeRequest(string $path = '/admin/pos'): IncomingRequest
    {
        return new IncomingRequest(
            new \Config\App(),
            new URI('http://example.com' . $path),
            'php://input',
            new UserAgent()
        );
    }

    private function loginAs(bool $isSuperAdmin, ?int $tenantId): void
    {
        $session = service('session');
        $session->set([
            'isLoggedIn'    => true,
            'userId'        => 1,
            'is_superadmin' => $isSuperAdmin,
            'tenant_id'     => $tenantId,
        ]);
    }

    protected function tearDown(): void
    {
        service('session')->destroy();
        parent::tearDown();
    }

    /** Super admin luôn vượt qua mọi feature gate */
    public function testSuperAdminBypasses(): void
    {
        $this->loginAs(true, null);

        $filter = new PlanFilter();
        $this->assertNull($filter->before($this->makeRequest(), ['pos']));
        $this->assertNull($filter->before($this->makeRequest(), ['tournament']));
    }

    /** Tenant gói Pro (id=1): được dùng POS + tournament */
    public function testProTenantAllowed(): void
    {
        $this->loginAs(false, 1);

        $filter = new PlanFilter();
        $this->assertNull($filter->before($this->makeRequest(), ['pos']));
        $this->assertNull($filter->before($this->makeRequest(), ['tournament']));
    }

    /** Tenant gói Free (id=2): bị chặn POS → redirect /admin/plans */
    public function testFreeTenantBlockedFromPos(): void
    {
        $this->loginAs(false, 2);

        $filter = new PlanFilter();
        $result = $filter->before($this->makeRequest(), ['pos']);

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertStringContainsString('/admin/plans', $result->getHeaderLine('Location'));
    }

    /** Tenant Free bị chặn tournament nhưng vẫn dùng được booking/court (tính năng lõi) */
    public function testFreeTenantBlockedTournamentButCoreAllowed(): void
    {
        $this->loginAs(false, 2);

        $filter = new PlanFilter();

        $blocked = $filter->before($this->makeRequest(), ['tournament']);
        $this->assertInstanceOf(RedirectResponse::class, $blocked);

        $this->assertNull($filter->before($this->makeRequest(), ['booking']));
        $this->assertNull($filter->before($this->makeRequest(), ['court']));
    }

    /** AJAX request bị chặn → JSON 402 thay vì redirect */
    public function testAjaxBlockedReturns402Json(): void
    {
        $this->loginAs(false, 2);

        $request = $this->makeRequest();
        $request->setHeader('X-Requested-With', 'XMLHttpRequest');

        $filter = new PlanFilter();
        $result = $filter->before($request, ['pos']);

        $this->assertSame(402, $result->getStatusCode());
        $body = json_decode($result->getBody(), true);
        $this->assertSame(402, $body['status']);
        $this->assertArrayHasKey('upgrade_url', $body);
    }

    /** Chưa có tenant context → filter bỏ qua (để TenantFilter xử lý) */
    public function testNoTenantContextPassesThrough(): void
    {
        $this->loginAs(false, null);

        $filter = new PlanFilter();
        $this->assertNull($filter->before($this->makeRequest(), ['pos']));
    }
}
