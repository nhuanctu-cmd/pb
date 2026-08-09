<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class ApiAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $token = $request->getHeaderLine('Authorization');

        // Check Bearer token
        if (!empty($token) && preg_match('/Bearer\s(\S+)/', $token, $matches)) {
            $token = $matches[1];
            $payload = $this->validateToken($token);

            if ($payload) {
                // Set user info for the request
                $request->api_user_id = $payload['user_id'] ?? null;
                $request->api_tenant_id = $payload['tenant_id'] ?? null;
                return;
            }
        }

        // Check API key
        $apiKey = $request->getHeaderLine('X-API-Key');
        if (!empty($apiKey) && \Config\Database::connect()->fieldExists('api_key', 'users')) {
            $userModel = new \App\Models\UserModel();
            $user = $userModel->where('api_key', $apiKey)->where('deleted_at', null)->first();
            if ($user) {
                $request->api_user_id = $user->id;
                $request->api_tenant_id = $user->tenant_id;
                return;
            }
        }

        return service('response')
            ->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED)
            ->setJSON([
                'status'  => 401,
                'message' => lang('App.unauthorized'),
                'errors'  => ['auth' => lang('App.invalid_api_token')],
            ]);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No action needed
    }

    private function validateToken(string $token): ?array
    {
        try {
            $payload = base64_decode($token, true);
            $data = json_decode($payload, true);

            if (!$data || !isset($data['user_id'], $data['sig'])) {
                return null;
            }

            $signature = (string) $data['sig'];
            unset($data['sig']);
            $expected = $this->sign($data);
            if (! hash_equals($expected, $signature)) {
                return null;
            }

            // Check expiration
            if (isset($data['exp']) && $data['exp'] < time()) {
                return null;
            }

            return $data;
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function generateToken(array $payload): string
    {
        $payload['iat'] = time();
        $payload['exp'] = time() + (86400 * 30); // 30 days
        $payload['sig'] = (new self())->sign($payload);
        return base64_encode(json_encode($payload, JSON_UNESCAPED_SLASHES));
    }

    private function sign(array $payload): string
    {
        unset($payload['sig']);
        $key = (string) config('Encryption')->key;
        if ($key === '') {
            throw new \RuntimeException('Encryption key is required for API tokens.');
        }

        return hash_hmac(
            'sha256',
            json_encode($payload, JSON_UNESCAPED_SLASHES),
            $key
        );
    }
}
