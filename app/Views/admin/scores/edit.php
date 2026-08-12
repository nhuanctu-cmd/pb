<?= $this->extend('layouts/admin_master') ?>

<?= $this->section('content') ?>
<?php
$scoreMap = [];
foreach ($scores ?? [] as $score) {
    $scoreMap[(int) $score->set_no] = $score;
}
$bestOf = (int) ($match->best_of ?? $match->number_of_sets ?? $match->sets_count ?? 3);
$bestOf = $bestOf >= 5 ? 5 : ($bestOf >= 3 ? 3 : 1);
$teamA = $match->team_a_name ?? ('Team #' . ($match->team_a_id ?? '-'));
$teamB = $match->team_b_name ?? ('Team #' . ($match->team_b_id ?? '-'));
?>

<div class="erp-action-bar">
    <a href="<?= esc($returnUrl ?? '/admin/scores') ?>" class="erp-btn"><i class="bi bi-arrow-left"></i> Quay lại</a>
    <form method="post" action="<?= esc($postBase ?? '/admin/scores') ?>/<?= (int) $match->id ?>/start" class="d-inline">
        <?= csrf_field() ?>
        <button class="erp-btn erp-btn-primary" type="submit"><i class="bi bi-play-fill"></i> Bắt đầu trận</button>
    </form>
    <?php if (($match->status ?? '') === 'completed' && empty($match->is_locked)): ?><form method="post" action="<?= esc($postBase ?? '/admin/scores') ?>/<?= (int) $match->id ?>/lock" class="d-inline"><?= csrf_field() ?><button class="erp-btn" type="submit"><i class="bi bi-lock"></i> Khóa kết quả</button></form><?php elseif (! empty($match->is_locked)): ?><form method="post" action="<?= esc($postBase ?? '/admin/scores') ?>/<?= (int) $match->id ?>/unlock" class="d-inline"><?= csrf_field() ?><button class="erp-btn" type="submit"><i class="bi bi-unlock"></i> Mở khóa</button></form><?php endif; ?>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="erp-card">
            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <div class="erp-muted">Trận #<?= (int) $match->id ?> · BO<?= $bestOf ?></div>
                    <h3 class="mb-0"><?= esc($teamA) ?> vs <?= esc($teamB) ?></h3>
                </div>
                <?= renderStatusBadge($match->status ?? 'scheduled', 'general') ?>
            </div>

            <form method="post" action="<?= esc($postBase ?? '/admin/scores') ?>/<?= (int) $match->id ?>/update" class="vstack gap-3">
                <?= csrf_field() ?>
                <div>
                    <label class="form-label">Thể thức</label>
                    <select name="best_of" class="form-select" style="max-width:180px">
                        <?php foreach ([1, 3, 5] as $mode): ?>
                            <option value="<?= $mode ?>" <?= $bestOf === $mode ? 'selected' : '' ?>>BO<?= $mode ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="erp-table-wrap">
                    <table class="erp-table">
                        <thead>
                            <tr>
                                <th>Set</th>
                                <th><?= esc($teamA) ?></th>
                                <th><?= esc($teamB) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for ($i = 1; $i <= 5; $i++): $row = $scoreMap[$i] ?? null; ?>
                                <tr>
                                    <td><strong>Set <?= $i ?></strong><input type="hidden" name="sets[<?= $i ?>][set_no]" value="<?= $i ?>"></td>
                                    <td><input type="number" min="0" name="sets[<?= $i ?>][team_a_score]" class="form-control form-control-lg" value="<?= esc($row->team_a_score ?? '') ?>"></td>
                                    <td><input type="number" min="0" name="sets[<?= $i ?>][team_b_score]" class="form-control form-control-lg" value="<?= esc($row->team_b_score ?? '') ?>"></td>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>

                <div>
                    <label class="form-label">Lý do sửa điểm</label>
                    <input name="reason" class="form-control" placeholder="Ví dụ: trọng tài xác nhận lại điểm set 2">
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <button class="erp-btn erp-btn-primary" type="submit"><i class="bi bi-save"></i> Lưu điểm</button>
                    <button class="erp-btn" type="submit" formaction="<?= esc($postBase ?? '/admin/scores') ?>/<?= (int) $match->id ?>/finish"><i class="bi bi-check2-circle"></i> Xác nhận kết quả</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="erp-card">
            <h4>Lịch sử sửa điểm</h4>
            <?php if (empty($logs)): ?>
                <p class="erp-muted mb-0">Chưa có log sửa điểm.</p>
            <?php else: ?>
                <div class="vstack gap-2">
                    <?php foreach ($logs as $log): ?>
                        <div class="border-bottom pb-2">
                            <strong><?= esc($log->reason ?? 'Cập nhật điểm') ?></strong>
                            <div class="erp-muted"><?= esc($log->created_at) ?> · User #<?= esc($log->changed_by ?? '-') ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
