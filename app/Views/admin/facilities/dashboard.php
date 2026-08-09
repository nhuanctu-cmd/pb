<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><i class="bi bi-cpu"></i> <?= esc($title) ?></h1>
        <div>
            <a href="<?= base_url('admin/courts/grid/' . $branch->id) ?>" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-grid-3x3"></i> <?= lang('CourtStatus.grid') ?>
            </a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><?= lang('Device.code') ?></th>
                            <th><?= lang('Device.name') ?></th>
                            <th><?= lang('Device.device_type') ?></th>
                            <th><?= lang('Device.model') ?></th>
                            <th><?= lang('Court.title') ?></th>
                            <th><?= lang('Device.status') ?></th>
                            <th><?= lang('Device.last_ping') ?></th>
                            <th><?= lang('Device.last_value') ?></th>
                            <th><?= lang('App.actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($devices as $device): ?>
                        <tr>
                            <td><code><?= esc($device->code) ?></code></td>
                            <td><?= esc($device->getName()) ?></td>
                            <td>
                                <i class="bi <?= $device->getDeviceIcon() ?>"></i>
                                <?= lang('Device.' . $device->device_type) ?>
                            </td>
                            <td><small class="text-muted"><?= esc($device->model ?? '') ?></small></td>
                            <td>
                                <?php if ($device->court_id): ?>
                                <small><?= esc(model('App\Models\CourtModel')->find($device->court_id)->code ?? '') ?></small>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $device->getStatusBadge() ?></td>
                            <td>
                                <?php if ($device->last_ping_at): ?>
                                <small><?= date('H:i:s', strtotime($device->last_ping_at)) ?></small>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($device->device_type === 'light' || $device->device_type === 'fan'): ?>
                                <span class="badge bg-<?= $device->last_value === 'on' ? 'success' : 'secondary' ?>">
                                    <?= $device->last_value ?? 'off' ?>
                                </span>
                                <a href="<?= base_url('admin/courts/toggle-device/' . $device->id) ?>"
                                   class="btn btn-sm btn-outline-warning ms-1">
                                    <i class="bi bi-arrow-left-right"></i>
                                </a>
                                <?php else: ?>
                                <?= esc($device->last_value ?? '-') ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?= base_url('api/v1/facilities/device/' . $device->id . '/logs') ?>"
                                   class="btn btn-sm btn-outline-info" target="_blank" title="<?= lang('Device.logs') ?>">
                                    <i class="bi bi-clock-history"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($devices)): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                <i class="bi bi-cpu fs-1 d-block mb-2"></i>
                                <?= lang('App.no_data') ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Device Statistics -->
    <?php
        $onlineCount = count(array_filter($devices, fn($d) => $d->status === 'online'));
        $offlineCount = count(array_filter($devices, fn($d) => $d->status === 'offline'));
        $errorCount = count(array_filter($devices, fn($d) => $d->status === 'error'));
        $totalCount = count($devices);
    ?>
    <?php if ($totalCount > 0): ?>
    <div class="row g-3 mt-3">
        <div class="col-md-3">
            <div class="card bg-success bg-opacity-10 border-0">
                <div class="card-body text-center">
                    <h5 class="text-success"><?= $onlineCount ?></h5>
                    <small><?= lang('Device.online') ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-secondary bg-opacity-10 border-0">
                <div class="card-body text-center">
                    <h5 class="text-secondary"><?= $offlineCount ?></h5>
                    <small><?= lang('Device.offline') ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger bg-opacity-10 border-0">
                <div class="card-body text-center">
                    <h5 class="text-danger"><?= $errorCount ?></h5>
                    <small><?= lang('Device.error') ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-primary bg-opacity-10 border-0">
                <div class="card-body text-center">
                    <h5 class="text-primary"><?= $totalCount ?></h5>
                    <small><?= lang('Device.title') ?></small>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
