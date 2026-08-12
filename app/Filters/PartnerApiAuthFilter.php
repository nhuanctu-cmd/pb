<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class PartnerApiAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $key = service('partnerApiService')->authenticate($request->getHeaderLine('X-Partner-Key'));
        $scope = (string) ($arguments[0] ?? '');
        if (! $key || ($scope !== '' && ! service('partnerApiService')->hasScope($key, $scope))) {
            return service('response')->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED)->setJSON([
                'status' => 401, 'message' => 'Partner API key không hợp lệ hoặc thiếu scope.',
            ]);
        }
        $request->partner_api_key_id = (int) $key->id;
        $request->partner_tenant_id = (int) $key->tenant_id;
        $request->partner_scopes = json_decode((string) $key->scopes, true) ?: [];
        $request->tenant_context = service('tenantDataPolicy')->context((int) $key->tenant_id, (int) $key->tenant_id, false);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
