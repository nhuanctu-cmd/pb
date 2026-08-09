<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3"><?= $pageTitle ?? lang('App.membership_packages') ?></h1>
    <div>
        <a href="/admin/memberships" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> <?= lang('App.back') ?></a>
        <a href="/admin/memberships/create-package" class="btn btn-primary"><i class="bi bi-plus"></i> <?= lang('App.create') ?></a>
    </div>
</div>

<div class="row">
    <?php if (empty($packages)): ?>
    <div class="col-12">
        <div class="card">
            <div class="card-body text-center text-muted"><?= lang('App.no_data') ?></div>
        </div>
    </div>
    <?php else: ?>
    <?php foreach ($packages as $pkg): ?>
    <div class="col-md-4 mb-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <h5 class="card-title"><?= esc($pkg->getName()) ?></h5>
                    <?= $pkg->getStatusBadge() ?>
                </div>
                <h2 class="text-primary my-3"><?= $pkg->getPriceFormatted() ?></h2>
                <ul class="list-unstyled">
                    <li><i class="bi bi-clock"></i> <?= $pkg->duration_days ?> <?= lang('App.days') ?></li>
                    <?php if ($pkg->discount_percent > 0): ?>
                    <li><i class="bi bi-tag"></i> <?= lang('App.discount') ?>: <?= $pkg->discount_percent ?>%</li>
                    <?php endif; ?>
                    <?php if ($pkg->booking_priority > 0): ?>
                    <li><i class="bi bi-star"></i> <?= lang('App.booking_priority') ?>: <?= $pkg->booking_priority ?></li>
                    <?php endif; ?>
                </ul>
                <div class="d-grid gap-2">
                    <a href="/admin/memberships/edit-package/<?= $pkg->id ?>" class="btn btn-warning"><i class="bi bi-pencil"></i> <?= lang('App.edit') ?></a>
                    <a href="/admin/memberships/delete-package/<?= $pkg->id ?>" class="btn btn-danger" onclick="return confirm('<?= lang('App.confirm_delete') ?>')"><i class="bi bi-trash"></i> <?= lang('App.delete') ?></a>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
