<p class="breadcrumb">
    <a href="/">Start</a> / Kategorien
</p>

<h1>Kategorien verwalten</h1>

<div x-data="categoryAdmin()">
    <!-- Neue Kategorie anlegen -->
    <form @submit.prevent="createCategory" class="inline-form">
        <input type="text" x-model="newCategoryName" placeholder="Neue Kategorie..." required>
        <button type="submit" :disabled="!newCategoryName.trim()">Anlegen</button>
    </form>

    <p x-show="message" :class="messageType" x-text="message"></p>

    <!-- Kategorien-Liste -->
    <?php if (empty($categories)): ?>
        <p>Noch keine Kategorien vorhanden.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Fotos</th>
                    <th>Erstellt</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $cat): ?>
                    <?php $canModify = $auth->isAdmin() || ($cat['created_by'] ?? '') === $auth->getUserId(); ?>
                    <tr data-id="<?= htmlspecialchars($cat['id']) ?>" data-name="<?= htmlspecialchars($cat['name']) ?>" data-photo-count="<?= $cat['photo_count'] ?>">
                        <td>
                            <a href="/kategorie/<?= htmlspecialchars($cat['slug']) ?>">
                                <?= htmlspecialchars($cat['name']) ?>
                            </a>
                        </td>
                        <td><code><?= htmlspecialchars($cat['slug']) ?></code></td>
                        <td><?= $cat['photo_count'] ?></td>
                        <td><?= date('d.m.Y', strtotime($cat['created_at'])) ?></td>
                        <td>
                            <?php if ($canModify): ?>
                                <button class="outline small"
                                        @click="renameCategory('<?= htmlspecialchars($cat['id']) ?>', '<?= htmlspecialchars($cat['name']) ?>')">
                                    Umbenennen
                                </button>
                                <button class="outline secondary small"
                                        @click="deleteCategory('<?= htmlspecialchars($cat['id']) ?>', '<?= htmlspecialchars($cat['name']) ?>', <?= $cat['photo_count'] ?>)">
                                    Löschen
                                </button>
                            <?php else: ?>
                                <span class="text-muted">–</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
