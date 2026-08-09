<?= $this->extend('layouts/admin_master') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <?= flash_message() ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <?php foreach (['image' => 'Images', 'document' => 'Documents', 'video' => 'Videos'] as $t => $label): ?>
                <a href="<?= base_url('admin/media?type=' . $t) ?>" class="btn btn-sm <?= $type === $t ? 'btn-primary' : 'btn-outline-secondary' ?>"><?= lang('App.media_type_' . $t) ?></a>
            <?php endforeach; ?>
            <a href="<?= base_url('admin/media') ?>" class="btn btn-sm <?= $type ? 'btn-outline-secondary' : 'btn-primary' ?>"><?= lang('App.all') ?></a>
        </div>
        <form method="post" action="<?= base_url('admin/media/upload') ?>" enctype="multipart/form-data" class="d-flex gap-2">
            <?= csrf_field() ?>
            <input type="file" class="form-control form-control-sm" name="file" required>
            <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-cloud-upload"></i> <?= lang('App.media_upload') ?></button>
        </form>
    </div>

    <div class="row">
        <?php if (empty($media)): ?>
            <div class="col-12 text-center text-muted py-5"><?= lang('App.no_data') ?></div>
        <?php else: ?>
            <?php foreach ($media as $item): ?>
                <div class="col-md-2 col-sm-4 col-6 mb-3">
                    <div class="card h-100">
                        <?php if (in_array($item->extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])): ?>
                            <img src="<?= base_url($item->file_path) ?>" class="card-img-top" style="height: 120px; object-fit: cover;" alt="<?= esc($item->alt_text) ?>">
                        <?php else: ?>
                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 120px;">
                                <i class="bi bi-file-earmark fs-1 text-muted"></i>
                            </div>
                        <?php endif; ?>
                        <div class="card-body p-2">
                            <small class="text-truncate d-block" title="<?= esc($item->file_name) ?>"><?= esc($item->file_name) ?></small>
                            <small class="text-muted"><?= number_format($item->file_size / 1024, 1) ?> KB · <?= esc($item->extension) ?></small>
                        </div>
                        <div class="card-footer p-2 d-flex gap-1">
                            <button type="button" class="btn btn-sm btn-outline-secondary w-100 btn-copy" data-url="<?= base_url($item->file_path) ?>">Copy</button>
                            <a href="<?= base_url('admin/media/delete/' . $item->id) ?>" class="btn btn-sm btn-outline-danger w-100" onclick="return confirm('<?= lang('App.confirm_delete') ?>')"><?= lang('App.delete') ?></a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.querySelectorAll('.btn-copy').forEach((btn) => {
    btn.addEventListener('click', function () {
        navigator.clipboard.writeText(this.dataset.url).then(() => {
            this.textContent = '<?= lang('App.copied') ?>';
            setTimeout(() => this.textContent = 'Copy', 1500);
        });
    });
});
</script>
<?= $this->endSection() ?>
