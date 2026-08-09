<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<form method="get" class="card mb-3">
    <div class="card-body row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label">Scope</label>
            <select name="scope_type" class="form-select">
                <?php foreach (['global' => 'Global', 'region' => 'Region', 'facility' => 'Cluster / Facility', 'tournament' => 'Tournament'] as $value => $label): ?>
                <option value="<?= $value ?>" <?= $scopeType === $value ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Region</label>
            <select name="region" class="form-select">
                <option value="">All</option>
                <?php foreach (($regions ?? []) as $item): ?>
                <option value="<?= esc($item) ?>" <?= $region === $item ? 'selected' : '' ?>><?= esc($item) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Scope ID</label>
            <input type="number" name="scope_id" class="form-control" value="<?= esc($scopeId ?? '') ?>" placeholder="Facility or tournament id">
        </div>
        <div class="col-md-3">
            <button class="btn btn-primary w-100" type="submit"><i class="bi bi-filter"></i> Filter</button>
        </div>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>#</th><th>Player</th><th>Region</th><th>Scope</th><th>ELO</th><th>Games</th><th>W/L</th></tr></thead>
            <tbody>
            <?php foreach (($rankings ?? []) as $index => $row): ?>
                <tr>
                    <td class="fw-semibold"><?= $index + 1 ?></td>
                    <td><a href="/admin/players/profile/<?= $row->player_id ?>"><?= esc($row->full_name) ?></a><br><small class="text-muted"><?= esc($row->player_code) ?></small></td>
                    <td><?= esc($row->region ?? '-') ?></td>
                    <td><?= esc($row->scope_type) ?><?= $row->scope_id ? ' #' . esc($row->scope_id) : '' ?></td>
                    <td class="fw-semibold"><?= (int) $row->rating ?></td>
                    <td><?= (int) $row->games_played ?></td>
                    <td><?= (int) $row->wins ?> / <?= (int) $row->losses ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($rankings)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4"><?= lang('App.no_data') ?></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
