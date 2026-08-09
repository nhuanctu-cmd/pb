<?php

namespace App\Services;

use App\Models\NotificationModel;
use App\Models\NotificationTemplateModel;
use App\Models\JobModel;

/**
 * Notification Engine: in-app notification + email queue + templates vi/en
 */
class NotificationService
{
    protected NotificationModel $notificationModel;
    protected NotificationTemplateModel $templateModel;
    protected JobModel $jobModel;

    public function __construct()
    {
        $this->notificationModel = new NotificationModel();
        $this->templateModel     = new NotificationTemplateModel();
        $this->jobModel          = new JobModel();
    }

    /**
     * Tạo thông báo in-app cho user
     */
    public function notifyUser(
        int $userId,
        string $templateCode,
        array $variables = [],
        ?int $tenantId = null,
        ?string $actionUrl = null
    ): ?int {
        $template = $this->templateModel->getByCode($templateCode, 'in_app', session('locale') ?? 'vi');

        // Fallback sang email template nếu không có in_app
        if (! $template) {
            $template = $this->templateModel->getByCode($templateCode, 'email', session('locale') ?? 'vi');
        }

        // Keep notifications useful even when a tenant has not seeded the
        // requested template yet. This is safe during migrations and partial deployments.
        if ($template) {
            $title   = $this->render($template->subject, $variables);
            $message = $this->render($template->body, $variables);
        } else {
            $label   = ucwords(str_replace(['_', '-'], ' ', $templateCode));
            $name    = trim((string) ($variables['full_name'] ?? $variables['customer_name'] ?? ''));
            $title   = $name !== '' ? $label . ': ' . $name : $label;
            $message = $this->render($label, $variables);
        }

        return $this->notificationModel->insert([
            'tenant_id'     => $tenantId ?? session('tenant_id'),
            'user_id'       => $userId,
            'template_code' => $templateCode,
            'title'         => $title,
            'message'       => $message,
            'channel'       => 'in_app',
            'data'          => ! empty($variables) ? json_encode($variables) : null,
            'action_url'    => $actionUrl,
            'created_by'    => session('userId'),
        ]);
    }

    /**
     * Đưa email vào hàng đợi (job queue) để gửi nền
     */
    public function queueEmail(string $to, string $templateCode, array $variables = [], ?string $locale = null): int
    {
        return $this->jobModel->push('email', [
            'type'         => 'email',
            'to'           => $to,
            'templateCode' => $templateCode,
            'variables'    => $variables,
            'locale'       => $locale ?? (session('locale') ?? 'vi'),
            'tenantId'     => session('tenant_id'),
        ]);
    }

    /**
     * Gửi email đồng bộ (dùng trong controller khi cần phản hồi ngay)
     */
    public function sendEmailNow(string $to, string $templateCode, array $variables = [], ?string $locale = null): bool
    {
        $template = $this->templateModel->getByCode($templateCode, 'email', $locale ?? (session('locale') ?? 'vi'));
        if (! $template) {
            log_message('warning', "NotificationService: missing email template {$templateCode}");
            return false;
        }

        $subject = $this->render($template->subject, $variables);
        $body    = $this->render($template->body, $variables);

        $email = \Config\Services::email();
        $email->setTo($to);
        $email->setSubject($subject);
        $email->setMessage($body);
        $email->setMailType('html');

        return $email->send();
    }

    /**
     * Thay thế các biến {{name}} trong template
     */
    public function render(string $template, array $variables): string
    {
        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', function ($matches) use ($variables) {
            return $variables[$matches[1]] ?? $matches[0];
        }, $template);
    }

    public function getUnreadCount(int $userId): int
    {
        return $this->notificationModel->countUnreadByUser($userId);
    }

    public function getUnreadByUser(int $userId, int $limit = 20): array
    {
        return $this->notificationModel->getUnreadByUser($userId, $limit);
    }

    public function getRecentByUser(int $userId, int $limit = 50): array
    {
        return $this->notificationModel->getRecentByUser($userId, $limit);
    }

    public function markAsRead(int $notificationId, int $userId): bool
    {
        return $this->notificationModel->markAsRead($notificationId, $userId);
    }

    public function markAllAsRead(int $userId): bool
    {
        return $this->notificationModel->markAllAsRead($userId);
    }
}
