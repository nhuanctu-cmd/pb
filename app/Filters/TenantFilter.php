<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class TenantFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $tenantId = session()->get('tenant_id');
        $isSuperAdmin = session()->get('is_superadmin');

        // Super admin can access all tenants
        if ($isSuperAdmin) {
            return;
        }

        // Check if user has a tenant context
        if (!$tenantId) {
            return redirect()->to('/admin/tenants/select')
                ->with('error', lang('Tenant.selectRequired'));
        }

        // Store tenant_id in request for controllers to use
        $request->tenant_id = $tenantId;
        $request->tenant_context = service('tenantDataPolicy')->context((int) $tenantId, (int) $tenantId, false);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No action needed
    }
}
