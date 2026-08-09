<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Testcase API xác thực (token-based) — /api/v1
 */
class ApiAuthFeatureTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private array $jsonHeader = ['Content-Type' => 'application/json'];

    /** TC11: API login bằng JSON body trả về token */
    public function testApiLoginWithJsonBody(): void
    {
        $result = $this->withBody(json_encode([
                'email'    => 'admin@pickleball.com',
                'password' => 'admin123',
            ]))
            ->withHeaders($this->jsonHeader)
            ->call('POST', '/api/v1/auth/login');

        $result->assertOK();
        $result->assertJSONFragment(['status' => 200]);

        $body = json_decode($result->response()->getBody(), true);
        $this->assertArrayHasKey('token', $body['data']);
        $this->assertNotEmpty($body['data']['token']);
        $this->assertArrayNotHasKey('password', $body['data']['user']);
    }

    /** TC12: API login bằng form-urlencoded vẫn hoạt động */
    public function testApiLoginWithFormBody(): void
    {
        $result = $this->call('POST', '/api/v1/auth/login', [
            'email'    => 'admin@pickleball.com',
            'password' => 'admin123',
        ]);

        $result->assertOK();
        $result->assertJSONFragment(['status' => 200]);
    }

    /** TC13: API login sai mật khẩu → 401 tiếng Việt */
    public function testApiLoginWrongPassword(): void
    {
        $result = $this->withBody(json_encode([
                'email'    => 'admin@pickleball.com',
                'password' => 'sai-mat-khau',
            ]))
            ->withHeaders($this->jsonHeader)
            ->call('POST', '/api/v1/auth/login');

        $result->assertStatus(401);
        $result->assertJSONFragment(['status' => 401]);
    }

    /** TC14: API login thiếu dữ liệu → lỗi validation */
    public function testApiLoginValidation(): void
    {
        $result = $this->withBody(json_encode(['email' => 'khong-hop-le']))
            ->withHeaders($this->jsonHeader)
            ->call('POST', '/api/v1/auth/login');

        $result->assertStatus(422);
    }

    /** TC15/TC16: Kiểm tra ApiAuthFilter — xem tests/unit/ApiAuthFilterTest.php */

    /** TC17: API public available-slots không cần auth */
    public function testPublicAvailableSlots(): void
    {
        $result = $this->call('GET', '/api/v1/booking/available-slots', [
            'branch_id' => 1,
            'date'      => date('Y-m-d', strtotime('+1 day')),
        ]);

        $result->assertOK();
    }
}
