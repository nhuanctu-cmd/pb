<?= $this->extend('player/layouts/main') ?>
<?= $this->section('title') ?>Tạo kèo<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="container py-4">
    <h1 class="h3 mb-3">Tạo kèo</h1>
    <form method="post" action="/player/matches/create" class="card"><div class="card-body">
        <?= csrf_field() ?>
        <div class="mb-3"><label class="form-label">Chi nhánh</label><select name="branch_id" class="form-select" required><?php foreach ($branches as $branch): ?><option value="<?= $branch->id ?>"><?= esc($branch->name) ?></option><?php endforeach; ?></select></div>
        <div class="mb-3"><label class="form-label">Ngày</label><input type="date" name="preferred_date" class="form-control" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" required></div>
        <div class="row g-2">
            <div class="col-6 mb-3"><label class="form-label">Bắt đầu</label><input type="time" name="preferred_start_time" class="form-control" required></div>
            <div class="col-6 mb-3"><label class="form-label">Kết thúc</label><input type="time" name="preferred_end_time" class="form-control" required></div>
        </div>
        <div class="row g-2">
            <div class="col-6 mb-3"><label class="form-label">Rating từ</label><input type="number" name="level_from" class="form-control" value="0"></div>
            <div class="col-6 mb-3"><label class="form-label">Rating đến</label><input type="number" name="level_to" class="form-control" value="9999"></div>
        </div>
        <div class="row g-2">
            <div class="col-6 mb-3"><label class="form-label">Loại trận</label><select name="match_type" class="form-select"><option value="single">Single</option><option value="double" selected>Double</option><option value="mixed">Mixed</option></select></div>
            <div class="col-6 mb-3"><label class="form-label">Cần người</label><input type="number" name="need_players" class="form-control" value="1" min="1" max="3"></div>
        </div>
        <button class="btn btn-primary w-100"><i class="bi bi-search"></i> Tạo kèo</button>
    </div></form>
</div>
<?= $this->endSection() ?>
