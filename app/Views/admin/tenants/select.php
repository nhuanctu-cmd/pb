<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="row g-4">
    <?php if (! empty($tenants)): ?>
        <?php foreach ($tenants as $tenant): ?>
            <?php $isCurrent = (int) ($current_tenant_id ?? 0) === (int) ($tenant->id ?? 0); ?>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card h-100 <?= $isCurrent ? 'border-primary' : '' ?>">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                            <div>
                                <div class="text-muted small"><?= esc(lang('Tenant.code')) ?></div>
                                <h5 class="card-title mb-0"><?= esc($tenant->code ?? '') ?></h5>
                            </div>
                            <?php if ($isCurrent): ?>
                                <span class="badge bg-primary"><?= esc(lang('Tenant.current')) ?></span>
                            <?php endif; ?>
                        </div>

                        <h4 class="mb-2"><?= esc($tenant->name ?? '') ?></h4>
                        <?php if (! empty($tenant->address)): ?>
                            <p class="text-muted mb-3">
                                <i class="bi bi-geo-alt me-1"></i><?= esc($tenant->address) ?>
                            </p>
                        <?php endif; ?>

                        <a href="<?= site_url('admin/tenants/set-session/' . (int) $tenant->id) ?>" class="btn <?= $isCurrent ? 'btn-outline-primary' : 'btn-primary' ?> mt-auto">
                            <i class="bi bi-box-arrow-in-right me-1"></i>
                            <?= $isCurrent ? esc(lang('Tenant.continue')) : esc(lang('Tenant.select')) ?>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center text-muted py-5">
                    <i class="bi bi-building display-5 d-block mb-3"></i>
                    <?= esc(lang('App.no_data')) ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
