<?php

use App\Models\UserModel;
use App\Models\UserRoleModel;
use App\Models\RoleModel;
use App\Services\AuditLogService;

if (!function_exists('user_id')) {
    function user_id(): ?int
    {
        return session()->get('userId') ?? null;
    }
}

if (!function_exists('user')) {
    function user(): ?object
    {
        $userId = user_id();
        if (!$userId) return null;

        $userModel = new UserModel();
        return $userModel->find($userId);
    }
}

if (!function_exists('has_role')) {
    function has_role(string $slug): bool
    {
        $userId = user_id();
        if (!$userId) return false;

        $userRoleModel = new UserRoleModel();
        $roles = $userRoleModel->select('roles.*')
                               ->join('roles', 'roles.id = user_roles.role_id')
                               ->where('user_roles.user_id', $userId)
                               ->where('user_roles.deleted_at', null)
                               ->findAll();

        foreach ($roles as $role) {
            if ($role->slug === $slug) return true;
        }
        return false;
    }
}

if (!function_exists('is_superadmin')) {
    function is_superadmin(): bool
    {
        return session()->get('is_superadmin') ?? false;
    }
}

if (!function_exists('can')) {
    /**
     * Kiểm tra quyền của user hiện tại (RBAC).
     * Super admin luôn có mọi quyền. Hỗ trợ wildcard 'module.*'.
     */
    function can(string $permission): bool
    {
        if (is_superadmin()) {
            return true;
        }

        $userId = user_id();
        if (! $userId) {
            return false;
        }

        $permissionService = new \App\Services\PermissionService();

        if ($permissionService->hasPermission((int) $userId, $permission)) {
            return true;
        }

        // Kiểm tra wildcard: 'bookings.create' khớp grant 'bookings.*'
        if (str_contains($permission, '.')) {
            $module = explode('.', $permission)[0];
            return $permissionService->hasPermission((int) $userId, $module . '.*');
        }

        return false;
    }
}

if (!function_exists('canAny')) {
    /**
     * Có ít nhất 1 quyền trong danh sách
     */
    function canAny(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (can($permission)) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('current_tenant_id')) {
    function current_tenant_id(): ?int
    {
        return session()->get('tenant_id') ?? null;
    }
}

if (!function_exists('current_branch_id')) {
    function current_branch_id(): ?int
    {
        return session()->get('branch_id') ?? null;
    }
}

if (!function_exists('log_audit')) {
    function log_audit(array $data): void
    {
        try {
            $auditService = new AuditLogService();

            $action = 'unknown';
            $module = 'system';
            $tableName = $data['table'] ?? null;
            $recordId = $data['id'] ?? $data['result'] ?? null;
            $oldValues = $data['data'] ?? null;

            if (isset($data['result'])) {
                $action = 'delete';
                $module = $tableName ?? 'system';
            } elseif (isset($data['data']) && isset($data['id'])) {
                $action = 'update';
                $module = $tableName ?? 'system';
            } elseif (isset($data['data']) && !isset($data['id'])) {
                $action = 'create';
                $module = $tableName ?? 'system';
                $recordId = $data['result'] ?? $data['id'] ?? null;
            }

            $auditService->log($action, $module, $tableName, is_numeric($recordId) ? (int)$recordId : null, $oldValues, $data['data'] ?? null);
        } catch (\Exception $e) {
            log_message('error', 'Audit log failed: ' . $e->getMessage());
        }
    }
}

if (!function_exists('format_datetime')) {
    function format_datetime($datetime, string $format = 'd/m/Y H:i'): string
    {
        if (empty($datetime)) return '';
        if (is_string($datetime)) {
            $datetime = new \DateTime($datetime);
        }
        return $datetime->format($format);
    }
}

if (!function_exists('format_date')) {
    function format_date($date, string $format = 'd/m/Y'): string
    {
        if (empty($date)) return '';
        if (is_string($date)) {
            $date = new \DateTime($date);
        }
        return $date->format($format);
    }
}

if (!function_exists('generate_slug')) {
    function generate_slug(string $string): string
    {
        $string = preg_replace('/[^a-zA-Z0-9\s-]/', '', $string);
        $string = strtolower(trim($string));
        $string = preg_replace('/[\s-]+/', '-', $string);
        $string = preg_replace('/^-+/', '', $string);
        $string = preg_replace('/-+$/', '', $string);
        return $string;
    }
}

if (!function_exists('generate_code')) {
    function generate_code(string $prefix = '', int $length = 8): string
    {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $prefix . $code;
    }
}

if (!function_exists('is_active_route')) {
    function is_active_route(string $segment, string $className = 'active'): string
    {
        $uri = service('uri');
        $firstSegment = $uri->getSegment(1);
        $secondSegment = $uri->getSegment(2);

        if (strpos($segment, '/') !== false) {
            $parts = explode('/', $segment);
            if ($firstSegment === ($parts[0] ?? '') && $secondSegment === ($parts[1] ?? '')) {
                return $className;
            }
        }

        if ($firstSegment === $segment) {
            return $className;
        }

        return '';
    }
}

if (!function_exists('flash_message')) {
    function flash_message(): ?string
    {
        $session = session();
        $types = ['success', 'error', 'warning', 'info'];
        foreach ($types as $type) {
            if ($session->has($type)) {
                return '<div class="alert alert-' . ($type === 'error' ? 'danger' : $type) . ' alert-dismissible fade show" role="alert">'
                     . esc($session->get($type))
                     . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
                     . '</div>';
            }
        }
        return null;
    }
}

if (!function_exists('renderStatusBadge')) {
    function renderStatusBadge(?string $status, string $type = 'general'): string
    {
        $status = $status ?: 'neutral';
        $maps = [
            'booking' => [
                'pending' => ['warning', 'Chờ xác nhận'],
                'reserved' => ['info', 'Đã giữ chỗ'],
                'paid' => ['success', 'Đã thanh toán'],
                'checked_in' => ['success', 'Đã check-in'],
                'completed' => ['neutral', 'Hoàn thành'],
                'cancelled' => ['danger', 'Đã hủy'],
                'refunded' => ['neutral', 'Đã hoàn tiền'],
                'no_show' => ['danger', 'Không đến'],
            ],
            'payment' => [
                'unpaid' => ['warning', 'Chưa thanh toán'],
                'partial' => ['info', 'Thanh toán một phần'],
                'paid' => ['success', 'Đã thanh toán'],
                'refunded' => ['neutral', 'Hoàn tiền'],
            ],
            'court' => [
                'available' => ['success', 'Trống'],
                'occupied' => ['info', 'Đang dùng'],
                'maintenance' => ['warning', 'Bảo trì'],
                'inactive' => ['neutral', 'Ngưng dùng'],
            ],
            'membership' => [
                'active' => ['success', 'Đang hiệu lực'],
                'expired' => ['warning', 'Hết hạn'],
                'cancelled' => ['danger', 'Đã hủy'],
            ],
            'tournament' => [
                'draft' => ['neutral', 'Nháp'],
                'open' => ['success', 'Mở đăng ký'],
                'closed' => ['dark', 'Đóng đăng ký'],
                'running' => ['info', 'Đang diễn ra'],
                'completed' => ['dark', 'Hoàn thành'],
                'cancelled' => ['danger', 'Đã hủy'],
            ],
            'general' => [
                'active' => ['success', 'Hoạt động'],
                'inactive' => ['neutral', 'Không hoạt động'],
                'success' => ['success', 'Thành công'],
                'warning' => ['warning', 'Cảnh báo'],
                'danger' => ['danger', 'Nguy hiểm'],
                'info' => ['info', 'Thông tin'],
                'neutral' => ['neutral', 'Trung tính'],
                'dark' => ['dark', 'Đóng'],
            ],
        ];

        [$tone, $label] = $maps[$type][$status] ?? $maps['general'][$status] ?? ['neutral', ucfirst(str_replace('_', ' ', $status))];
        return '<span class="erp-status erp-status-' . esc($tone) . '">' . esc($label) . '</span>';
    }
}

if (!function_exists('format_money')) {
    function format_money(float|int|string|null $amount, string $currency = 'đ'): string
    {
        return number_format((float) ($amount ?? 0), 0, ',', '.') . $currency;
    }
}

if (!function_exists('asset_url')) {
    /**
     * Build a public asset URL that also works when the app is installed in
     * a subdirectory, such as /BP/pickball-system/public.
     */
    function asset_url(string $path = ''): string
    {
        $scriptName = str_replace(['\\', '\\/'], '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        $scriptName = preg_replace('#/+#', '/', $scriptName) ?: '';
        $basePath = str_replace('\\', '/', dirname($scriptName));
        $basePath = $basePath === '.' || $basePath === '/' ? '' : rtrim($basePath, '/');

        return ($basePath ?: '') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('renderTimelineItem')) {
    function renderTimelineItem(string $title, ?string $time = null, string $tone = 'info', ?string $description = null): string
    {
        $html = '<div class="erp-timeline-item">';
        $html .= '<div class="erp-timeline-dot erp-timeline-dot-' . esc($tone) . '"></div>';
        $html .= '<div><strong>' . esc($title) . '</strong>';
        if ($time) {
            $html .= '<div class="erp-muted">' . esc($time) . '</div>';
        }
        if ($description) {
            $html .= '<div>' . esc($description) . '</div>';
        }
        $html .= '</div></div>';
        return $html;
    }
}
