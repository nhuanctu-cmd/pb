<?= $this->extend('layouts/admin_master') ?>
<?= $this->section('content') ?>
<div class="container-fluid py-3">
    <?= flash_message() ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-1">Rating Governance</h1>
            <p class="text-muted mb-0">Canonical profiles · claims · integrity · import review</p>
        </div>
        <span class="badge text-bg-dark">Tenant #<?= (int) $tenantId ?></span>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header">Canonical leaderboard</div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Player</th>
                        <th>Discipline</th>
                        <th>Rating</th>
                        <th>Skill</th>
                        <th>Reliability</th>
                        <th>Matches</th>
                        <th>Status</th>
                        <th>Manual adjustment</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($profiles as $profile): ?>
                        <tr>
                            <td>
                                <strong><?= esc($profile->full_name ?? '—') ?></strong>
                                <small class="d-block text-muted"><?= esc($profile->player_code ?? '') ?></small>
                            </td>
                            <td><?= esc($profile->discipline ?? '—') ?></td>
                            <td><?= $profile->rating_value !== null ? number_format((float) $profile->rating_value, 3) : '—' ?></td>
                            <td><?= esc($profile->skill_band ?? 'NR') ?></td>
                            <td><?= number_format((float) $profile->reliability_score, 0) ?>%</td>
                            <td><?= (int) $profile->rated_match_count ?></td>
                            <td><span class="badge text-bg-secondary"><?= esc($profile->status) ?></span></td>
                            <td>
                                <form method="post" action="<?= base_url('admin/rating/adjust') ?>" class="d-flex gap-1">
                                    <input type="hidden" name="player_id" value="<?= (int) $profile->player_id ?>">
                                    <input type="hidden" name="discipline" value="<?= esc($profile->discipline) ?>">
                                    <input name="rating" type="number" min="2" max="5.999" step="0.001" class="form-control form-control-sm" style="width:90px" placeholder="Rating">
                                    <input name="reason" required class="form-control form-control-sm" placeholder="Reason">
                                    <button class="btn btn-sm btn-outline-danger">Adjust</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-6">
            <div class="card shadow-sm h-100">
                <div class="card-header">Pending skill claims</div>
                <div class="list-group list-group-flush">
                    <?php foreach ($claims as $claim): ?>
                        <div class="list-group-item d-flex justify-content-between gap-2">
                            <span>
                                <strong><?= esc($claim->full_name ?? '—') ?></strong>
                                <small class="d-block text-muted"><?= esc($claim->source_type) ?> · <?= esc($claim->discipline) ?> · <?= $claim->claimed_rating !== null ? number_format((float) $claim->claimed_rating, 3) : '—' ?></small>
                            </span>
                            <span class="d-flex gap-1">
                                <form method="post" action="<?= base_url('admin/rating/claims/' . $claim->id . '/verify') ?>">
                                    <input type="hidden" name="status" value="verified">
                                    <button class="btn btn-sm btn-success">Verify</button>
                                </form>
                                <form method="post" action="<?= base_url('admin/rating/claims/' . $claim->id . '/verify') ?>">
                                    <input type="hidden" name="status" value="rejected">
                                    <button class="btn btn-sm btn-outline-secondary">Reject</button>
                                </form>
                            </span>
                        </div>
                    <?php endforeach; if (! $claims): ?>
                        <div class="p-3 text-muted">Không có claim cần review.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card shadow-sm h-100">
                <div class="card-header">Open integrity flags</div>
                <div class="list-group list-group-flush">
                    <?php foreach ($flags as $flag): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between gap-2 mb-2">
                                <span>
                                    <strong><?= esc($flag->code) ?></strong>
                                    <small class="d-block text-muted"><?= esc($flag->full_name ?? 'Match #' . $flag->match_id) ?> · Risk <?= number_format((float) $flag->risk_score, 0) ?></small>
                                </span>
                                <span class="badge text-bg-warning">Review required</span>
                            </div>
                            <form method="post" action="<?= base_url('admin/rating/flags/' . $flag->id . '/resolve') ?>" class="d-flex gap-1">
                                <input name="reason" required class="form-control form-control-sm" placeholder="Lý do xử lý">
                                <select name="status" class="form-select form-select-sm" style="max-width:120px">
                                    <option value="approved">Approve</option>
                                    <option value="rejected">Reject</option>
                                    <option value="blocked">Block</option>
                                </select>
                                <button class="btn btn-sm btn-dark">Save</button>
                            </form>
                        </div>
                    <?php endforeach; if (! $flags): ?>
                        <div class="p-3 text-muted">Không có integrity flag mở.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mt-4">
        <div class="card-header">Import jobs</div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Source</th>
                        <th>Source name</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Governance action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($imports as $job): ?>
                        <tr>
                            <td>#<?= (int) $job->id ?></td>
                            <td><?= esc($job->source_type) ?></td>
                            <td><?= esc($job->source_name ?: '-') ?></td>
                            <td><span class="badge text-bg-dark"><?= esc($job->status) ?></span></td>
                            <td><?= esc($job->created_at) ?></td>
                            <td>
                                <?php if (in_array($job->status, ['validated', 'verified'], true)): ?>
                                    <div class="d-flex gap-1 flex-wrap align-items-center">
                                        <form method="post" action="<?= base_url('admin/rating/imports/' . (int) $job->id . '/approve') ?>" class="d-inline">
                                            <button class="btn btn-sm btn-success">Approve</button>
                                        </form>
                                        <form method="post" action="<?= base_url('admin/rating/imports/' . (int) $job->id . '/reject') ?>" class="d-inline d-flex gap-1">
                                            <input type="text" name="reason" class="form-control form-control-sm" style="min-width:180px" required placeholder="Lý do reject">
                                            <button class="btn btn-sm btn-outline-danger">Reject</button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <span class="small text-muted">Không khả dụng (đợi validated / verified)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; if (! $imports): ?>
                        <tr>
                            <td colspan="6" class="text-muted">Chưa có import job.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
