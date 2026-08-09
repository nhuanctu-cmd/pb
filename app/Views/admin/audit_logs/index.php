<?= $this->extend('layouts/admin_master') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <?= flash_message() ?>

    <div class="card mb-3">
        <div class="card-body">
            <form method="get" class="row g-2 align-items-end">
                <input type="hidden" name="group" value="<?= esc($filters['group'] ?? '') ?>">
                <div class="col-md-2">
                    <label class="form-label"><?= lang('App.module') ?></label>
                    <select class="form-select" name="module">
                        <option value=""><?= lang('App.all') ?></option>
                        <?php foreach ($modules as $m): ?>
                            <option value="<?= $m ?>" <?= ($filters['module'] ?? '') === $m ? 'selected' : '' ?>><?= esc($m) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?= lang('App.actions') ?></label>
                    <select class="form-select" name="action">
                        <option value=""><?= lang('App.all') ?></option>
                        <?php foreach ($actions as $a): ?>
                            <option value="<?= $a ?>" <?= ($filters['action'] ?? '') === $a ? 'selected' : '' ?>><?= esc($a) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?= lang('App.user') ?></label>
                    <select class="form-select" name="user_id">
                        <option value=""><?= lang('App.all') ?></option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= $u->id ?>" <?= (int)($filters['user_id'] ?? 0) === $u->id ? 'selected' : '' ?>><?= esc($u->first_name . ' ' . $u->last_name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?= lang('App.from') ?></label>
                    <input type="date" class="form-control" name="from" value="<?= esc($filters['from'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?= lang('App.to') ?></label>
                    <input type="date" class="form-control" name="to" value="<?= esc($filters['to'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> <?= lang('App.filter') ?></button>
                    <a href="<?= base_url('admin/audit-logs') ?>" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th><?= lang('App.created_at') ?></th>
                        <th><?= lang('App.actions') ?></th>
                        <th><?= lang('App.module') ?></th>
                        <th><?= lang('App.user') ?></th>
                        <th><?= lang('App.description') ?></th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4"><?= lang('App.no_data') ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?= format_datetime($log->created_at) ?></td>
                                <td><span class="badge bg-secondary"><?= esc($log->action) ?></span></td>
                                <td><?= esc($log->module) ?></td>
                                <td><?= esc($log->user_id ? '#' . $log->user_id : '-') ?></td>
                                <td><?= esc($log->description ?? '') ?></td>
                                <td><small class="text-muted"><?= esc($log->ip_address ?? '-') ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($pager): ?>
            <div class="card-footer"><?= $pager->links() ?></div>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
