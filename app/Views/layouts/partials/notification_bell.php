<?php
/**
 * Chuông thông báo in-app gắn trên admin topbar
 */
$userId = user_id();
$unreadCount = 0;
$unreadItems = [];

if ($userId) {
    $service = new \App\Services\NotificationService();
    $unreadCount = $service->getUnreadCount($userId);
    $unreadItems = $service->getUnreadByUser($userId, 10);
}
?>
<div class="dropdown">
    <button class="btn btn-link text-dark position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-bell fs-5"></i>
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger <?= $unreadCount ? '' : 'd-none' ?>" id="notificationCount">
            <?= $unreadCount ? ($unreadCount > 99 ? '99+' : $unreadCount) : '' ?>
        </span>
    </button>
    <div class="dropdown-menu dropdown-menu-end shadow" style="width: 360px; max-height: 420px; overflow-y: auto;">
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
            <strong><?= lang('App.notifications') ?></strong>
            <a href="<?= base_url('admin/notifications') ?>" class="small"><?= lang('App.view_all') ?></a>
        </div>
        <div id="notificationDropdownItems">
            <?php if (empty($unreadItems)): ?>
                <div class="text-center text-muted py-4 small"><?= lang('App.notifications_empty') ?></div>
            <?php else: ?>
                <?php foreach ($unreadItems as $item): ?>
                    <a href="#" class="dropdown-item py-2 notification-item" data-id="<?= $item->id ?>">
                        <div class="fw-bold small"><?= esc($item->title) ?></div>
                        <div class="small text-truncate"><?= esc($item->message) ?></div>
                        <div class="text-muted" style="font-size: 0.75rem;"><?= format_datetime($item->created_at) ?></div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="border-top px-3 py-2 text-center">
            <button type="button" class="btn btn-sm btn-link" id="markAllReadDropdown"><?= lang('App.notifications_mark_all_read') ?></button>
        </div>
    </div>
</div>
