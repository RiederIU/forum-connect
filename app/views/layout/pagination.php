<?php

/**
 * Wiederverwendbarer Pagination-Block.
 * Die einbindende View muss $page, $totalPages und $baseUrl bereitstellen.
 * ARIA-Attribute ermöglichen die Screenreader-Navigation.
 */
?>
<?php if ($totalPages > 1): ?>
<nav class="pagination" aria-label="Seitennavigation">
    <?php if ($page > 1): ?>
        <a href="<?= hsc($baseUrl) ?>&amp;page=<?= $page - 1 ?>">&laquo; Zurück</a>
    <?php endif; ?>

    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <?php if ($i === $page): ?>
            <span class="current" aria-current="page"><?= $i ?></span>
        <?php else: ?>
            <a href="<?= hsc($baseUrl) ?>&amp;page=<?= $i ?>"><?= $i ?></a>
        <?php endif; ?>
    <?php endfor; ?>

    <?php if ($page < $totalPages): ?>
        <a href="<?= hsc($baseUrl) ?>&amp;page=<?= $page + 1 ?>">Weiter &raquo;</a>
    <?php endif; ?>
</nav>
<?php endif; ?>
