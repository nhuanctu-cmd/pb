<?= $this->extend('layouts/admin_master') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <?= flash_message() ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0"><?= lang('App.notifications') ?></h5>
        <button type="button" class="btn btn-sm btn-outline-primary" id="markAllRead">
            <?= lang('App.notifications_mark_all_read') ?>
        </button>
    </div>

    <div class="list-group" id="notificationList">
        <?php if (empty($notifications)): ?>
            <div class="list-group-item text-center text-muted py-4"><?= lang('App.notifications_empty') ?></div>
        <?php else: ?>
            <?php foreach ($notifications as $n): ?>
                <a href="#" class="list-group-item list-group-item-action notification-item <?= $n->is_read ? '' : 'active' ?>"
                   data-id="<?= $n->id ?>">
                    <div class="d-flex w-100 justify-content-between">
                        <h6 class="mb-1"><?= esc($n->title) ?></h6>
                        <small><?= format_datetime($n->created_at) ?></small>
                    </div>
                    <p class="mb-1"><?= esc($n->message) ?></p>
                    <small class="text-muted"><?= esc($n->channel) ?></small>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.querySelectorAll('.notification-item').forEach((item) => {
    item.addEventListener('click', function (e) {
        e.preventDefault();
        const id = this.dataset.id;
        fetch(`<?= base_url('admin/notifications/mark-read') ?>/${id}`, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(() => { this.classList.remove('active'); });
    });
});

document.getElementById('markAllRead')?.addEventListener('click', function () {
    fetch('<?= base_url('admin/notifications/mark-all-read') ?>', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(() => document.querySelectorAll('.notification-item.active').forEach(i => i.classList.remove('active')));
});
</script>
<?= $this->endSection() ?>
