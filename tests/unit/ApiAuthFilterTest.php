<?php

namespace Tests\Unit;

use App\Filters\ApiAuthFilter;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\URI;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * TC15/TC16: ApiAuthFilter — xác thực API bằng Bearer token
 */
class ApiAuthFilterTest extends CIUnitTestCase
{
    private function makeRequest(): IncomingRequest
    {
        return new IncomingRequest(
            new \Config\App(),
            new URI('http://example.com/api/v1/tenants'),
            'php://input',
            new UserAgent()
        );
    }

    /** TC15: Không có token → 401 JSON */
    public function testRejectsRequestWithoutToken(): void
    {
        $filter = new ApiAuthFilter();
        $result = $filter->before($this->makeRequest());

        $this->assertSame(401, $result->getStatusCode());

        $body = json_decode($result->getBody(), true);
        $this->assertSame(401, $body['status']);
    }

    /** TC16: Token hợp lệ → đi tiếp + gán api_user_id/api_tenant_id */
    public function testAcceptsValidToken(): void
    {
        $token   = ApiAuthFilter::generateToken(['user_id' => 1, 'tenant_id' => 2, 'branch_id' => 3]);
        $request = $this->makeRequest();
        $request->setHeader('Authorization', 'Bearer ' . $token);

        $filter = new ApiAuthFilter();
        $result = $filter->before($request);

        $this->assertNull($result);
        $this->assertSame(1, $request->api_user_id);
        $this->assertSame(2, $request->api_tenant_id);
    }

    /** Token hết hạn → 401 */
    public function testRejectsExpiredToken(): void
    {
        $payload = ['user_id' => 1, 'tenant_id' => 1, 'iat' => time() - 90000, 'exp' => time() - 3600];
        $token   = base64_encode(json_encode($payload));

        $request = $this->makeRequest();
        $request->setHeader('Authorization', 'Bearer ' . $token);

        $filter = new ApiAuthFilter();
        $result = $filter->before($request);

        $this->assertSame(401, $result->getStatusCode());
    }

    /** Token rác (không phải base64 JSON) → 401 */
    public function testRejectsGarbageToken(): void
    {
        $request = $this->makeRequest();
        $request->setHeader('Authorization', 'Bearer token-bi-sao-chep-bua-bai');

        $filter = new ApiAuthFilter();
        $result = $filter->before($request);

        $this->assertSame(401, $result->getStatusCode());
    }

    public function testRejectsTamperedSignedToken(): void
    {
        $token = ApiAuthFilter::generateToken(['user_id' => 1, 'tenant_id' => 1]);
        $payload = json_decode(base64_decode($token), true);
        $payload['tenant_id'] = 999;

        $request = $this->makeRequest();
        $request->setHeader('Authorization', 'Bearer ' . base64_encode(json_encode($payload)));

        $result = (new ApiAuthFilter())->before($request);

        $this->assertSame(401, $result->getStatusCode());
    }

    /** generateToken tạo token đọc ngược được, exp 30 ngày */
    public function testGenerateTokenRoundtrip(): void
    {
        $token   = ApiAuthFilter::generateToken(['user_id' => 9, 'tenant_id' => 1, 'branch_id' => null]);
        $payload = json_decode(base64_decode($token), true);

        $this->assertSame(9, $payload['user_id']);
        $this->assertArrayHasKey('iat', $payload);
        $this->assertArrayHasKey('exp', $payload);
        $this->assertGreaterThan(time(), $payload['exp']);
        $this->assertLessThanOrEqual(time() + 86400 * 30, $payload['exp']);
    }
}
