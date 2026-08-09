<?php $breadcrumbs = $breadcrumbs ?? [['label' => 'Trang chủ', 'url' => '/admin/dashboard']]; ?>
<nav aria-label="breadcrumb" class="d-none d-md-block">
    <ol class="breadcrumb mb-0">
        <?php foreach ($breadcrumbs as $index => $item): ?>
            <li class="breadcrumb-item <?= $index === array_key_last($breadcrumbs) ? 'active' : '' ?>">
                <?php if (! empty($item['url']) && $index !== array_key_last($breadcrumbs)): ?>
                    <a href="<?= esc($item['url']) ?>"><?= esc($item['label']) ?></a>
                <?php else: ?>
                    <?= esc($item['label']) ?>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
</nav>
