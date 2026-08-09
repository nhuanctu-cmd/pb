<?= $this->extend('layouts/admin_master') ?>

<?= $this->section('content') ?>
<div class="container-fluid">

    <?= flash_message() ?>

    <!-- Gói hiện tại -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-box-seam"></i> <?= lang('App.plans_current') ?>
                </div>
                <div class="card-body">
                    <?php if ($currentPlan): ?>
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <h4 class="mb-1"><?= esc($currentPlan['plan_name_vi']) ?></h4>
                                <span class="badge bg-<?= $currentPlan['status'] === 'active' ? 'success' : 'warning' ?>">
                                    <?= esc(lang('App.plan_status_' . $currentPlan['status'])) ?>
                                </span>
                                <?php if (! empty($currentPlan['trial_ends_at'])): ?>
                                    <div class="text-muted small mt-1">
                                        <?= lang('App.plans_trial_until') ?>: <?= format_date($currentPlan['trial_ends_at']) ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (! empty($currentPlan['ends_at'])): ?>
                                    <div class="text-muted small mt-1">
                                        <?= lang('App.plans_valid_until') ?>: <?= format_date($currentPlan['ends_at']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-8">
                                <div class="row text-center">
                                    <?php foreach ($limits as $resource => $limit): ?>
                                        <div class="col-3">
                                            <div class="border rounded p-2">
                                                <div class="small text-muted"><?= lang('App.plans_limit_' . $resource) ?></div>
                                                <strong><?= $limit['used'] ?><?= $limit['max'] === -1 ? ' / ∞' : ' / ' . $limit['max'] ?></strong>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-3">
                            <i class="bi bi-inbox fs-1 text-muted"></i>
                            <p class="text-muted mb-0"><?= lang('App.plans_no_plan') ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Các gói dịch vụ -->
    <h5 class="mb-3"><?= lang('App.plans_available') ?></h5>
    <div class="row">
        <?php foreach ($plans as $plan): ?>
            <?php
            $features  = json_decode($plan['features'] ?? '[]', true) ?: [];
            $isCurrent = $currentPlan && (int) $currentPlan['plan_id'] === (int) $plan['id'];
            ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100 <?= $isCurrent ? 'border-success shadow' : '' ?>">
                    <?php if ($isCurrent): ?>
                        <div class="card-header bg-success text-white text-center">
                            <?= lang('App.plans_current_badge') ?>
                        </div>
                    <?php endif; ?>
                    <div class="card-body d-flex flex-column">
                        <h4 class="text-center"><?= esc($plan['name_vi']) ?></h4>
                        <div class="text-center mb-3">
                            <?php if ((float) $plan['price_monthly'] > 0): ?>
                                <span class="fs-4 fw-bold text-primary"><?= format_money($plan['price_monthly']) ?></span>
                                <span class="text-muted">/<?= lang('App.plans_per_month') ?></span>
                                <div class="small text-muted"><?= format_money($plan['price_yearly']) ?>/<?= lang('App.plans_per_year') ?></div>
                            <?php else: ?>
                                <span class="fs-4 fw-bold text-success"><?= lang('App.plans_free') ?></span>
                            <?php endif; ?>
                        </div>
                        <p class="text-muted small"><?= esc($plan['description_vi']) ?></p>
                        <ul class="list-unstyled small flex-grow-1">
                            <li><i class="bi bi-diagram-3 text-primary"></i> <?= lang('App.plans_limit_branches') ?>: <strong><?= $plan['max_branches'] == -1 ? '∞' : $plan['max_branches'] ?></strong></li>
                            <li><i class="bi bi-grid-3x3-gap text-primary"></i> <?= lang('App.plans_limit_courts') ?>: <strong><?= $plan['max_courts'] == -1 ? '∞' : $plan['max_courts'] ?></strong></li>
                            <li><i class="bi bi-people text-primary"></i> <?= lang('App.plans_limit_players') ?>: <strong><?= $plan['max_players'] == -1 ? '∞' : $plan['max_players'] ?></strong></li>
                            <li><i class="bi bi-person-badge text-primary"></i> <?= lang('App.plans_limit_staff') ?>: <strong><?= $plan['max_staff'] == -1 ? '∞' : $plan['max_staff'] ?></strong></li>
                            <li class="mt-2">
                                <i class="bi bi-stars text-warning"></i>
                                <?php if (in_array('*', $features, true)): ?>
                                    <strong><?= lang('App.plans_all_features') ?></strong>
                                <?php else: ?>
                                    <?= esc(implode(', ', array_map(fn ($f) => lang('App.feature_' . $f), $features))) ?>
                                <?php endif; ?>
                            </li>
                        </ul>
                        <?php if (! $isCurrent): ?>
                            <button type="button" class="btn btn-primary w-100 mt-2 btn-subscribe"
                                    data-plan-id="<?= $plan['id'] ?>" data-plan-name="<?= esc($plan['name_vi']) ?>">
                                <i class="bi bi-rocket-takeoff"></i> <?= lang('App.plans_subscribe') ?>
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-outline-secondary w-100 mt-2" disabled>
                                <?= lang('App.plans_using') ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.querySelectorAll('.btn-subscribe').forEach((button) => {
    button.addEventListener('click', function () {
        const planName = this.dataset.planName;
        if (!confirm(<?= json_encode(lang('App.plans_confirm_subscribe')) ?> + ' "' + planName + '"?')) {
            return;
        }

        this.disabled = true;
        fetch(`<?= base_url('admin/plans/subscribe') ?>/${this.dataset.planId}`, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then((response) => response.json())
            .then((data) => {
                alert(data.message);
                if (data.success) { location.reload(); }
                this.disabled = false;
            })
            .catch(() => { alert('Error'); this.disabled = false; });
    });
});
</script>
<?= $this->endSection() ?>
