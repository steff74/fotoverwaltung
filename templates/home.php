<h1>Kategorien</h1>

<?php if (empty($categories)): ?>
    <p>Noch keine Kategorien vorhanden.</p>
    <?php if ($auth->isLoggedIn()): ?>
        <p><a href="/admin/categories" role="button">Kategorie anlegen</a></p>
    <?php endif; ?>
<?php else: ?>
    <ul class="categories">
        <?php foreach ($categories as $cat): ?>
            <li>
                <a href="/kategorie/<?= htmlspecialchars($cat['slug']) ?>">
                    <?= htmlspecialchars($cat['name']) ?>
                </a>
                <span class="photo-count"><?= $cat['photo_count'] ?> Foto<?= $cat['photo_count'] !== 1 ? 's' : '' ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
