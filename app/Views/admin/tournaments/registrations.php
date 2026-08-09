<?= $this->extend('layouts/admin_master') ?>

<?= $this->section('content') ?>
<div class="erp-action-bar">
    <a href="/admin/tournaments/show/<?= $tournament->id ?>" class="erp-btn"><i class="bi bi-arrow-left"></i> Chi tiết giải</a>
    <a href="/tournaments/<?= esc($tournament->slug_vi) ?>/register" target="_blank" class="erp-btn erp-btn-primary"><i class="bi bi-box-arrow-up-right"></i> Form public</a>
</div>

<div class="erp-table-wrap">
    <table class="erp-table">
        <thead><tr><th>Liên hệ</th><th>Hạng mục</th><th>Invoice</th><th>Thanh toán</th><th>Duyệt</th><th>Ghi chú</th><th class="col-actions">Action</th></tr></thead>
        <tbody>
        <?php foreach ($registrations as $registration): ?>
            <tr>
                <td><strong><?= esc($registration->contact_name) ?></strong><div class="erp-muted"><?= esc($registration->contact_phone) ?></div></td>
                <td><?= esc($registration->category_name ?? '-') ?></td>
                <td><?= esc($registration->invoice_code ?? '-') ?><div class="erp-muted"><?= format_money($registration->invoice_amount) ?></div></td>
                <td><?= renderStatusBadge($registration->payment_status, 'payment') ?></td>
                <td><?= renderStatusBadge($registration->approval_status) ?></td>
                <td><?= esc($registration->note) ?></td>
                <td class="col-actions">
                    <?php if ($registration->approval_status === 'pending'): ?>
                        <form method="post" action="/admin/tournaments/registrations/<?= $registration->id ?>/approve" class="d-inline"><?= csrf_field() ?><button class="erp-btn erp-btn-icon" title="Duyệt"><i class="bi bi-check-lg"></i></button></form>
                        <form method="post" action="/admin/tournaments/registrations/<?= $registration->id ?>/reject" class="d-inline"><?= csrf_field() ?><button class="erp-btn erp-btn-icon" title="Từ chối"><i class="bi bi-x-lg"></i></button></form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($registrations)): ?>
            <tr><td colspan="7" class="text-center erp-muted">Chưa có đăng ký.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?= $this->endSection() ?>
