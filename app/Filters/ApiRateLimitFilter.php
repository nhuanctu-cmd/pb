<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class ApiRateLimitFilter implements FilterInterface
{
    private const LIMIT = 120;
    private const WINDOW = 60;

    public function before(RequestInterface $request, $arguments = null)
    {
        $window = intdiv(time(), self::WINDOW);
        $identity = $request->getIPAddress() . '|' . $request->getHeaderLine('Authorization') . '|' . $request->getHeaderLine('X-API-Key');
        $key = 'api_rate_' . hash('sha256', $identity) . '_' . $window;
        $cache = service('cache');
        $count = (int) ($cache->get($key) ?? 0) + 1;
        $cache->save($key, $count, self::WINDOW + 1);
        if ($count <= self::LIMIT) return null;

        return service('response')
            ->setStatusCode(ResponseInterface::HTTP_TOO_MANY_REQUESTS)
            ->setHeader('Retry-After', (string) (self::WINDOW - (time() % self::WINDOW)))
            ->setJSON(['status' => 429, 'message' => 'API rate limit exceeded.', 'errors' => ['rate_limit' => 'Tối đa 120 requests/phút.']]);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
