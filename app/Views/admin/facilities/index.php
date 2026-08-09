<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <form method="get" class="d-flex gap-2">
        <input type="search" name="search" class="form-control" value="<?= esc($filters['search'] ?? '') ?>" placeholder="Search facilities">
        <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
    </form>
    <a href="/admin/facilities/create" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Create</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Contact</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (($facilities ?? []) as $facility): ?>
                <tr>
                    <td><code><?= esc($facility->code) ?></code></td>
                    <td>
                        <div class="fw-semibold"><?= esc($facility->getName()) ?></div>
                        <small class="text-muted"><?= esc($facility->address ?? '') ?></small>
                    </td>
                    <td>
                        <div><?= esc($facility->phone ?? '-') ?></div>
                        <small class="text-muted"><?= esc($facility->email ?? '') ?></small>
                    </td>
                    <td><span class="badge bg-<?= $facility->status === 'active' ? 'success' : 'secondary' ?>"><?= esc($facility->status) ?></span></td>
                    <td class="text-end">
                        <a href="/admin/facilities/dashboard/<?= $facility->id ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-speedometer2"></i></a>
                        <a href="/admin/facilities/edit/<?= $facility->id ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                        <a href="/admin/facilities/delete/<?= $facility->id ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('<?= lang('App.confirm_delete') ?>')"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($facilities)): ?>
                <tr><td colspan="5" class="text-center text-muted py-4"><?= lang('App.no_data') ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
