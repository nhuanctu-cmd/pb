<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('styles') ?>
<style>
.scheduler-grid { display: grid; grid-template-columns: 320px 1fr; gap: 16px; align-items: start; }
.group-board { display: grid; gap: 12px; }
.group-box { border: 1px solid #dee2e6; border-radius: 8px; background: #fff; overflow: hidden; }
.group-box header { padding: 10px 12px; background: #f8f9fa; border-bottom: 1px solid #dee2e6; font-weight: 600; }
.team-chip { display: flex; justify-content: space-between; gap: 8px; margin: 8px; padding: 8px 10px; border: 1px solid #ced4da; border-radius: 6px; background: #fff; cursor: grab; }
.timeline { display: grid; gap: 8px; }
.timeline-row { display: grid; grid-template-columns: 70px 110px minmax(220px,1fr) minmax(260px,330px) 90px; gap: 8px; align-items: center; padding: 10px; border: 1px solid #dee2e6; border-radius: 8px; background: #fff; }
.timeline-row.locked { border-color: #ffc107; background: #fffdf2; }
.conflict-item { border-left: 4px solid #dc3545; padding: 8px 12px; background: #fff5f5; margin-bottom: 8px; }
@media (max-width: 1200px) { .timeline-row { grid-template-columns: 60px 100px 1fr; } .timeline-row > form:first-of-type { grid-column:1/-1; } .timeline-row > form:last-child { grid-column:1/-1; } }
@media (max-width: 992px) { .scheduler-grid { grid-template-columns: 1fr; } .timeline-row { grid-template-columns: 1fr; } }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php if (! empty($category)): ?><div class="erp-action-bar mb-3"><div class="d-flex gap-2 flex-wrap"><a class="erp-btn" href="/admin/tournaments/show/<?= (int) $category->tournament_id ?>"><i class="bi bi-arrow-left"></i> Chi tiết giải</a><a class="erp-btn" href="/admin/tournaments/registrations/<?= (int) $category->tournament_id ?>?category_id=<?= (int) $category->id ?>"><i class="bi bi-person-check"></i> Đăng ký hạng mục</a><a class="erp-btn" href="/admin/tournaments/bracket?category_id=<?= (int) $category->id ?>"><i class="bi bi-diagram-3"></i> Cây đấu</a><a class="erp-btn" href="/admin/print-center/print?document=schedule&tournament_id=<?= (int) $category->tournament_id ?>" target="_blank"><i class="bi bi-printer"></i> Xem/in lịch</a><a class="erp-btn" href="/admin/tournaments/scheduler/export?category_id=<?= (int) $category->id ?>"><i class="bi bi-filetype-csv"></i> Xuất lịch</a></div><span class="erp-summary-chip">Hạng mục: <strong><?= esc($category->name_vi) ?></strong></span></div><?php endif; ?>
<form method="get" action="/admin/tournaments/scheduler" class="card mb-3">
    <div class="card-body row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label">Hạng mục thi đấu</label>
            <select name="category_id" class="form-select" required><option value="">Chọn giải / hạng mục</option><?php foreach (($categories ?? []) as $item): ?><option value="<?= (int) $item->id ?>" <?= (int) $categoryId === (int) $item->id ? 'selected' : '' ?>><?= esc(($item->tournament_name ?? 'Giải') . ' · ' . $item->name_vi) ?></option><?php endforeach; ?></select>
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
        <input type="number" name="seed_index" class="form-control" value="0" min="0" title="Chỉ số seed phụ để đổi biến thể draw khi cùng dữ liệu" style="max-width: 125px" placeholder="seed_index">
        <input type="text" name="rebuild_reason" class="form-control" placeholder="Lý do rebuild draw (nếu cần)" style="width: 200px">
        <label class="d-flex align-items-center gap-1 small text-muted">
            <input type="checkbox" name="force_rebuild" value="1"> Cho phép rebuild khi đã publish
        </label>
        <button class="btn btn-primary" type="submit"><i class="bi bi-magic"></i> Auto schedule</button>
    </form>
    <form method="post" action="/admin/tournaments/scheduler/rerun-unlocked">
        <?= csrf_field() ?>
        <input type="hidden" name="category_id" value="<?= (int) $categoryId ?>">
        <button class="btn btn-outline-warning" type="submit"><i class="bi bi-arrow-repeat"></i> Rerun unlocked</button>
    </form>
    <?php if (!empty($published)): ?>
        <form method="post" action="/admin/tournaments/scheduler/unpublish/<?= (int) $categoryId ?>" onsubmit="return confirm('Mở khóa lịch để chỉnh sửa?')"><?= csrf_field() ?><button class="btn btn-warning" type="submit"><i class="bi bi-unlock me-1"></i>Mở khóa lịch</button></form>
    <?php else: ?>
        <form method="post" action="/admin/tournaments/scheduler/publish/<?= (int) $categoryId ?>" onsubmit="return confirm('Công bố và khóa toàn bộ lịch chưa hoàn tất?')"><?= csrf_field() ?><button class="btn btn-success" type="submit"><i class="bi bi-megaphone me-1"></i>Công bố lịch</button></form>
    <?php endif; ?>
    <a class="btn btn-outline-secondary" href="/admin/tournaments/scheduler/export?category_id=<?= (int) $categoryId ?>"><i class="bi bi-filetype-csv me-1"></i>Xuất lịch</a>
    <a class="btn btn-outline-dark" href="/admin/tournaments/bracket?category_id=<?= (int) $categoryId ?>"><i class="bi bi-diagram-3"></i> Mở cây đấu</a>
</div>

<details class="card mb-3">
    <summary class="card-header cursor-pointer"><i class="bi bi-plus-circle me-1"></i>Thêm trận đấu thủ công</summary>
    <div class="card-body">
        <form method="post" action="/admin/tournaments/scheduler/manual" class="row g-2 align-items-end">
            <?= csrf_field() ?><input type="hidden" name="category_id" value="<?= (int) $categoryId ?>">
            <div class="col-md-2"><label class="form-label">Vòng</label><input type="number" name="round_no" min="1" max="20" value="1" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Tên vòng</label><input type="text" name="round_name" value="Trận thủ công" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">VĐV/đội A</label><select name="participant_a" class="form-select"><option value="">TBD</option><?php foreach (($participants ?? []) as $id => $label): ?><option value="<?= (int) $id ?>"><?= esc($label) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-2"><label class="form-label">VĐV/đội B</label><select name="participant_b" class="form-select"><option value="">TBD</option><?php foreach (($participants ?? []) as $id => $label): ?><option value="<?= (int) $id ?>"><?= esc($label) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-2"><label class="form-label">Sân</label><select name="court_id" class="form-select"><option value="">Chưa phân</option><?php foreach (($courts ?? []) as $court): ?><option value="<?= (int) $court->id ?>"><?= esc($court->name_vi ?? $court->code ?? ('Sân #' . $court->id)) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-2"><label class="form-label">Ngày / giờ</label><input type="date" name="scheduled_date" class="form-control mb-1"><input type="time" name="start_time" class="form-control"></div>
            <div class="col-12"><button class="btn btn-primary" type="submit"><i class="bi bi-plus-lg me-1"></i>Tạo trận</button></div>
        </form>
    </div>
</details>

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
                    <strong><?= esc($participants[(int) ($match->team_a_id ?? 0)] ?? (($match->team_a_id ?? null) ? 'VĐV/đội #' . (int) $match->team_a_id : 'TBD')) ?> vs <?= esc($participants[(int) ($match->team_b_id ?? 0)] ?? (($match->team_b_id ?? null) ? 'VĐV/đội #' . (int) $match->team_b_id : 'TBD')) ?></strong>
                    <form method="post" action="/admin/tournaments/scheduler/matches/<?= (int) $match->id ?>/participants" class="d-flex gap-1 mt-1">
                        <?= csrf_field() ?><select name="participant_a" class="form-select form-select-sm" <?= ($match->is_locked || $published) ? 'disabled' : '' ?>><option value="">A: TBD</option><?php foreach (($participants ?? []) as $id => $label): ?><option value="<?= (int) $id ?>" <?= (int) ($match->team_a_id ?? 0) === (int) $id ? 'selected' : '' ?>><?= esc($label) ?></option><?php endforeach; ?></select><select name="participant_b" class="form-select form-select-sm" <?= ($match->is_locked || $published) ? 'disabled' : '' ?>><option value="">B: TBD</option><?php foreach (($participants ?? []) as $id => $label): ?><option value="<?= (int) $id ?>" <?= (int) ($match->team_b_id ?? 0) === (int) $id ? 'selected' : '' ?>><?= esc($label) ?></option><?php endforeach; ?></select><button class="btn btn-sm btn-outline-secondary" type="submit" <?= ($match->is_locked || $published) ? 'disabled' : '' ?> title="Gán VĐV/đội"><i class="bi bi-person-gear"></i></button>
                    </form>
                    <span class="text-muted ms-2">
                        <?= esc($match->scheduled_date ?? '-') ?>
                        <?= esc($match->start_time ? substr($match->start_time, 0, 5) : '-') ?>
                        <?= $match->court_id ? ' · ' . esc($match->court_label ?? ('Sân #' . (int) $match->court_id)) : '' ?>
                    </span>
                </span>
                <form method="post" action="/admin/tournaments/scheduler/schedule/<?= (int) $match->id ?>" class="d-flex gap-1 align-items-center">
                    <?= csrf_field() ?>
                    <select name="court_id" class="form-select form-select-sm" required <?= ($match->is_locked || $published) ? 'disabled' : '' ?>><option value="">Sân</option><?php foreach (($courts ?? []) as $court): ?><option value="<?= (int) $court->id ?>" <?= (int) ($match->court_id ?? 0) === (int) $court->id ? 'selected' : '' ?>><?= esc($court->name_vi ?? $court->code ?? ('Sân #' . $court->id)) ?></option><?php endforeach; ?></select>
                    <input type="date" name="scheduled_date" class="form-control form-control-sm" value="<?= esc($match->scheduled_date ?? '') ?>" required <?= ($match->is_locked || $published) ? 'disabled' : '' ?>><input type="time" name="start_time" class="form-control form-control-sm" value="<?= esc($match->start_time ? substr($match->start_time, 0, 5) : '') ?>" required <?= ($match->is_locked || $published) ? 'disabled' : '' ?>><button class="btn btn-sm btn-outline-primary" type="submit" <?= ($match->is_locked || $published) ? 'disabled' : '' ?> title="Lưu lịch"><i class="bi bi-save"></i></button>
                </form>
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
