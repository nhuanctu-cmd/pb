<?php

namespace Tests\Unit;

use App\Services\NotificationService;
use App\Models\NotificationModel;
use App\Models\NotificationTemplateModel;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * TC — Notification Engine: tạo in-app notification, đếm chưa đọc, đánh dấu đã đọc,
 * render template, queue email.
 */
class NotificationServiceTest extends CIUnitTestCase
{
    protected NotificationService $service;
    protected int $userId = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new NotificationService();
        \Config\Database::connect()->table('notifications')->truncate();
    }

    protected function tearDown(): void
    {
        \Config\Database::connect()->table('notifications')->truncate();
        parent::tearDown();
    }

    public function testNotifyUserCreatesInAppNotification(): void
    {
        $id = $this->service->notifyUser($this->userId, 'welcome_user', [
            'full_name' => 'Nguyễn Văn A',
            'site_name' => 'Pickleball Pro',
        ]);

        $this->assertNotNull($id);
        $notification = (new NotificationModel())->find($id);
        $this->assertNotNull($notification);
        $this->assertSame($this->userId, (int) $notification->user_id);
        $this->assertSame('welcome_user', $notification->template_code);
        $this->assertSame(0, (int) $notification->is_read);
        $this->assertStringContainsString('Nguyễn Văn A', $notification->title);
    }

    public function testUnreadCountAndMarkRead(): void
    {
        $this->service->notifyUser($this->userId, 'welcome_user', ['full_name' => 'A', 'site_name' => 'S']);
        $this->service->notifyUser($this->userId, 'booking_reminder', ['booking_code' => 'BK001', 'court_name' => 'Sân 1', 'start_time' => '18:00']);

        $this->assertSame(2, $this->service->getUnreadCount($this->userId));

        $items = $this->service->getUnreadByUser($this->userId, 10);
        $this->assertCount(2, $items);

        $this->service->markAsRead($items[0]->id, $this->userId);

        $this->assertSame(1, $this->service->getUnreadCount($this->userId));
    }

    public function testMarkAllAsRead(): void
    {
        $this->service->notifyUser($this->userId, 'welcome_user', ['full_name' => 'A', 'site_name' => 'S']);
        $this->service->notifyUser($this->userId, 'welcome_user', ['full_name' => 'B', 'site_name' => 'S']);

        $this->assertTrue($this->service->markAllAsRead($this->userId));
        $this->assertSame(0, $this->service->getUnreadCount($this->userId));
    }

    public function testRenderTemplateReplacesVariables(): void
    {
        $result = $this->service->render('Xin chào {{full_name}}, mã đặt sân {{booking_code}}', [
            'full_name'    => 'An',
            'booking_code' => 'BK999',
        ]);

        $this->assertSame('Xin chào An, mã đặt sân BK999', $result);
    }

    public function testQueueEmailCreatesJob(): void
    {
        \Config\Database::connect()->table('jobs')->truncate();

        $jobId = $this->service->queueEmail('test@example.com', 'password_reset', [
            'full_name' => 'Test',
            'reset_url' => 'http://localhost/reset/abc',
            'expires'   => '30',
        ]);

        $this->assertGreaterThan(0, $jobId);
        $job = (new \App\Models\JobModel())->find($jobId);
        $this->assertNotNull($job);
        $this->assertSame('email', $job->queue);
        $payload = json_decode($job->payload, true);
        $this->assertSame('test@example.com', $payload['to']);
        $this->assertSame('password_reset', $payload['templateCode']);

        \Config\Database::connect()->table('jobs')->truncate();
    }

    public function testTemplateFallbackToEmailChannel(): void
    {
        // Xóa template in_app welcome_user để ép fallback email
        \Config\Database::connect()->table('notification_templates')
            ->where('code', 'welcome_user')
            ->where('channel', 'in_app')
            ->delete();

        $id = $this->service->notifyUser($this->userId, 'welcome_user', ['full_name' => 'A', 'site_name' => 'S']);
        $notification = (new NotificationModel())->find($id);

        $this->assertStringContainsString('A', $notification->title);
    }
}
