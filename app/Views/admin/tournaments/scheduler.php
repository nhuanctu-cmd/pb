<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('styles') ?>
<style>
.scheduler-grid { display: grid; grid-template-columns: 320px 1fr; gap: 16px; align-items: start; }
.group-board { display: grid; gap: 12px; }
.group-box { border: 1px solid #dee2e6; border-radius: 8px; background: #fff; overflow: hidden; }
.group-box header { padding: 10px 12px; background: #f8f9fa; border-bottom: 1px solid #dee2e6; font-weight: 600; }
.team-chip { display: flex; justify-content: space-between; gap: 8px; margin: 8px; padding: 8px 10px; border: 1px solid #ced4da; border-radius: 6px; background: #fff; cursor: grab; }
.timeline { display: grid; gap: 8px; }
.timeline-row { display: grid; grid-template-columns: 90px 120px 1fr 120px; gap: 8px; align-items: center; padding: 10px; border: 1px solid #dee2e6; border-radius: 8px; background: #fff; }
.timeline-row.locked { border-color: #ffc107; background: #fffdf2; }
.conflict-item { border-left: 4px solid #dc3545; padding: 8px 12px; background: #fff5f5; margin-bottom: 8px; }
@media (max-width: 992px) { .scheduler-grid { grid-template-columns: 1fr; } .timeline-row { grid-template-columns: 1fr; } }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<form method="get" action="/admin/tournaments/scheduler" class="card mb-3">
    <div class="card-body row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label">Category ID</label>
            <input type="number" name="category_id" class="form-control" value="<?= esc($categoryId ?: '') ?>" required>
        </div>
        <div class="col-md-2">
            <button class="btn btn-outline-primary w-100" type="submit"><i class="bi bi-search"></i> Xem</button>
        </div>
    </div>
</form>

<?php if ($categoryId): ?>
<div class="d-flex flex-wrap gap-2 mb-3">
    <form method="post" action="/admin/tournaments/scheduler/auto-schedule" class="d-flex gap-2">
        <?= csrf_field() ?>
        <input type="hidden" name="category_id" value="<?= (int) $categoryId ?>">
        <input type="number" name="groups" class="form-control" value="<?= max(2, count($groups ?: [])) ?>" min="1" style="width: 110px">
        <button class="btn btn-primary" type="submit"><i class="bi bi-magic"></i> Auto schedule</button>
    </form>
    <form method="post" action="/admin/tournaments/scheduler/rerun-unlocked">
        <?= csrf_field() ?>
        <input type="hidden" name="category_id" value="<?= (int) $categoryId ?>">
        <button class="btn btn-outline-warning" type="submit"><i class="bi bi-arrow-repeat"></i> Rerun unlocked</button>
    </form>
</div>

<?php if (!empty($conflicts)): ?>
<div class="mb-3">
    <?php foreach ($conflicts as $conflict): ?>
    <div class="conflict-item">
        <strong><?= esc($conflict['type']) ?></strong>
        <span><?= esc($conflict['message']) ?></span>
        <span class="text-muted">#<?= (int) $conflict['match_id'] ?><?= $conflict['other_match_id'] ? ' / #' . (int) $conflict['other_match_id'] : '' ?></span>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="scheduler-grid">
    <section class="group-board">
        <?php foreach ($groups as $group): ?>
        <div class="group-box" data-group-id="<?= (int) $group->id ?>">
            <header><?= esc($group->group_name) ?></header>
            <div class="group-dropzone">
                <?php foreach ($group->teams ?? [] as $team): ?>
                <div class="team-chip" draggable="true" data-team-id="<?= (int) $team->team_id ?>">
                    <span>Team #<?= (int) $team->team_id ?><?= !empty($team->team_name) ? ' - ' . esc($team->team_name) : '' ?></span>
                    <small class="text-muted">Seed <?= esc($team->seed_no ?? '-') ?></small>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </section>

    <section>
        <div class="timeline">
            <?php foreach ($matches as $match): ?>
            <div class="timeline-row <?= $match->is_locked ? 'locked' : '' ?>">
                <strong>#<?= (int) $match->match_no ?></strong>
                <span><?= esc($match->round_name) ?></span>
                <span>
                    Team <?= esc($match->team_a_id ?? 'BYE') ?> vs Team <?= esc($match->team_b_id ?? 'BYE') ?>
                    <span class="text-muted ms-2">
                        <?= esc($match->scheduled_date ?? '-') ?>
                        <?= esc($match->start_time ? substr($match->start_time, 0, 5) : '-') ?>
                        <?= $match->court_id ? ' · Court #' . (int) $match->court_id : '' ?>
                    </span>
                </span>
                <form method="post" action="/admin/tournaments/scheduler/<?= $match->is_locked ? 'unlock' : 'lock' ?>/<?= (int) $match->id ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm <?= $match->is_locked ? 'btn-warning' : 'btn-outline-secondary' ?>" type="submit">
                        <i class="bi <?= $match->is_locked ? 'bi-unlock' : 'bi-lock' ?>"></i>
                        <?= $match->is_locked ? 'Unlock' : 'Lock' ?>
                    </button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>
<?php endif; ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
let draggedTeam = null;
document.querySelectorAll('.team-chip').forEach((chip) => {
    chip.addEventListener('dragstart', () => draggedTeam = chip);
});
document.querySelectorAll('.group-box').forEach((box) => {
    box.addEventListener('dragover', (event) => event.preventDefault());
    box.addEventListener('drop', async () => {
        if (!draggedTeam) return;
        const form = new FormData();
        form.append('team_id', draggedTeam.dataset.teamId);
        form.append('group_id', box.dataset.groupId);
        form.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
        const response = await fetch('/admin/tournaments/scheduler/team-group', { method: 'POST', body: form });
        const result = await response.json();
        if (result.success) {
            box.querySelector('.group-dropzone').appendChild(draggedTeam);
        }
    });
});
</script>
<?= $this->endSection() ?>
