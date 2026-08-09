<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class PermissionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $userId = session()->get('userId');
        $isSuperAdmin = session()->get('is_superadmin');

        // Super admin has all permissions
        if ($isSuperAdmin) {
            return;
        }

        if (!$userId) {
            return redirect()->to('/login')
                ->with('error', lang('Auth.loginRequired'));
        }

        // If permission slug is specified in filter arguments
        if (!empty($arguments)) {
            $permissionService = service('permissionService');
            $hasPermission = false;

            foreach ($arguments as $permission) {
                if ($permissionService->hasPermission($userId, $permission)) {
                    $hasPermission = true;
                    break;
                }
            }

            if (!$hasPermission) {
                if ($request->isAJAX()) {
                    return service('response')
                        ->setStatusCode(ResponseInterface::HTTP_FORBIDDEN)
                        ->setJSON(['message' => lang('Auth.forbidden')]);
                }
                return redirect()->back()
                    ->with('error', lang('Auth.forbidden'));
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No action needed
    }
}
