<?php

/**
 * Componente Page Header com Breadcrumb
 * 
 * @param string $title - Título da página
 * @param array $breadcrumbs - Array com os breadcrumbs
 * @param string $homeUrl - URL da home (opcional)
 */

$homeUrl = $homeUrl ?? base_url('admin/dashboard');
$breadcrumbs = $pageHeader['breadcrumbs'] ?? [];
?>

<div class="row align-items-center mb-10">
    <div class="col-12">
        <div class="d-sm-flex align-items-center justify-space-between">
            <h4 class="mb-4 mb-sm-0 card-title"><?= esc($pageHeader['title']) ?></h4>

            <?php if (!empty($breadcrumbs)): ?>
                <nav aria-label="breadcrumb" class="ms-auto">
                    <ol class="breadcrumb bg-primary-subtle px-3 py-2 rounded">
                        <!-- Home -->
                        <li class="breadcrumb-item d-flex align-items-center">
                            <a class="text-muted text-decoration-none d-flex" href="<?= $homeUrl ?>">
                                <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                            </a>
                        </li>

                        <!-- Breadcrumbs dinâmicos -->
                        <?php foreach ($breadcrumbs as $index => $breadcrumb): ?>
                            <?php $isLast = ($index === count($breadcrumbs) - 1); ?>

                            <li class="breadcrumb-item <?= $isLast ? 'active text-primary' : '' ?>"
                                <?= $isLast ? 'aria-current="page"' : '' ?>>

                                <?php if ($isLast || empty($breadcrumb['url'])): ?>
                                    <?= esc($breadcrumb['title']) ?>
                                <?php else: ?>
                                    <a href="<?= $breadcrumb['url'] ?>" class="text-primary">
                                        <?= esc($breadcrumb['title']) ?>
                                    </a>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>