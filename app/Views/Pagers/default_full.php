<?php

use CodeIgniter\Pager\PagerRenderer;

/**
 * @var PagerRenderer $pager
 */
$pager->setSurroundCount(2);
?>

<nav aria-label="Navegação de páginas">
	<ul class="pagination">
		<?php if ($pager->hasPreviousPage()) : ?>
			<li class="page-item">
				<a class="page-link" href="<?= $pager->getPreviousPage() ?>" aria-label="Anterior">
					<span aria-hidden="true">&laquo; Anterior</span>
				</a>
			</li>
		<?php endif ?>

		<?php foreach ($pager->links() as $link) : ?>
			<li class="page-item <?= $link['active'] ? 'active' : '' ?>">
				<a class="page-link" href="<?= $link['uri'] ?>">
					<?= $link['title'] ?>
				</a>
			</li>
		<?php endforeach ?>

		<?php if ($pager->hasNextPage()) : ?>
			<li class="page-item">
				<a class="page-link" href="<?= $pager->getNextPage() ?>" aria-label="Próximo">
					<span aria-hidden="true">Próximo &raquo;</span>
				</a>
			</li>
		<?php endif ?>
	</ul>
</nav>