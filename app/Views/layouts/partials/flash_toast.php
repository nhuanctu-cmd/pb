<div class="position-fixed top-0 end-0 p-3" style="z-index: 1080" data-toast-container></div>
<?php foreach (['success', 'error', 'warning', 'info'] as $type): ?>
    <?php if (session()->has($type)): ?>
        <div data-toast="<?= esc(session($type)) ?>" data-toast-type="<?= $type === 'error' ? 'danger' : esc($type) ?>"></div>
    <?php endif; ?>
<?php endforeach; ?>
