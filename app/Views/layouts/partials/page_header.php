<?php if (! empty($title)): ?>
<div class="erp-page-header">
    <div>
        <h1><?= esc($title) ?></h1>
        <?php if (! empty($description)): ?><div class="erp-page-description"><?= esc($description) ?></div><?php endif; ?>
    </div>
    <?php if (! empty($actions)): ?>
        <div class="d-flex gap-2 align-items-start"><?= $actions ?></div>
    <?php endif; ?>
</div>
<?php endif; ?>
