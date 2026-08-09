<?= $this->extend('player/layouts/main') ?>
<?= $this->section('title') ?>Tạo team<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="container py-4">
    <h1 class="h3 mb-3">Tạo team</h1>
    <form method="post" action="/player/teams/create" class="card"><div class="card-body">
        <?= csrf_field() ?>
        <div class="mb-3">
            <label class="form-label">Tên team</label>
            <input name="team_name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Club</label>
            <select name="club_id" class="form-select">
                <option value="">Không thuộc club</option>
                <?php foreach ($clubs as $club): ?><option value="<?= $club->id ?>"><?= esc($club->name_vi) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Loại team</label>
            <select name="team_type" class="form-select">
                <option value="group">Group</option>
                <option value="male_double">Nam đôi</option>
                <option value="female_double">Nữ đôi</option>
                <option value="mixed_double">Đôi nam nữ</option>
            </select>
        </div>
        <button class="btn btn-primary w-100"><i class="bi bi-check-circle"></i> Tạo team</button>
    </div></form>
</div>
<?= $this->endSection() ?>
