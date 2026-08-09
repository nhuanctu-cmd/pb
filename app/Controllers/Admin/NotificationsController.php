<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\NotificationService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Notification center: danh sách + chuông thông báo in-app
 */
class NotificationsController extends BaseController
{
    protected NotificationService $notificationService;

    public function __construct()
    {
        $this->notificationService = new NotificationService();
    }

    public function index()
    {
        $userId = user_id();

        $this->viewData['pageTitle']       = lang('App.notifications');
        $this->viewData['pageDescription'] = lang('App.notifications_subtitle');
        $this->viewData['notifications']   = $userId
            ? $this->notificationService->getRecentByUser($userId, 100)
            : [];

        return $this->render('admin/notifications/index', $this->viewData);
    }

    /**
     * API: số thông báo chưa đọc (dùng cho chuông trên topbar)
     */
    public function unreadCount(): ResponseInterface
    {
        $userId = user_id();
        $count  = $userId ? $this->notificationService->getUnreadCount($userId) : 0;

        return $this->response->setJSON(['count' => $count]);
    }

    /**
     * API: danh sách thông báo chưa đọc gần nhất
     */
    public function unread(): ResponseInterface
    {
        $userId = user_id();
        $items  = $userId ? $this->notificationService->getUnreadByUser($userId, 20) : [];

        return $this->response->setJSON(['items' => $items]);
    }

    public function markRead(int $id): ResponseInterface
    {
        $userId = user_id();
        if (! $userId) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false]);
        }

        $success = $this->notificationService->markAsRead($id, $userId);

        return $this->response->setJSON([
            'success' => $success,
            'count'   => $this->notificationService->getUnreadCount($userId),
        ]);
    }

    public function markAllRead(): ResponseInterface
    {
        $userId = user_id();
        if (! $userId) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false]);
        }

        $success = $this->notificationService->markAllAsRead($userId);

        return $this->response->setJSON([
            'success' => $success,
            'count'   => 0,
        ]);
    }
}
