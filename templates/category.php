<p class="breadcrumb">
    <a href="/">Start</a> / <?= htmlspecialchars($category['name']) ?>
</p>

<h1><?= htmlspecialchars($category['name']) ?></h1>
<p><?= count($photos) ?> Foto<?= count($photos) !== 1 ? 's' : '' ?></p>

<?php if ($auth->isLoggedIn()): ?>
    <p><a href="/upload?category=<?= htmlspecialchars($category['slug']) ?>" role="button" class="outline">Fotos hochladen</a></p>
<?php endif; ?>

<?php if (empty($photos)): ?>
    <p>Noch keine Fotos in dieser Kategorie.</p>
<?php else: ?>
    <div x-data="photoGallery()">
        <div class="photo-grid">
            <?php foreach ($photos as $photo): ?>
                <div class="photo-item" data-id="<?= htmlspecialchars($photo['id']) ?>">
                    <a href="#" @click.prevent="openLightbox('/uploads/originals/<?= htmlspecialchars($categorySlug) ?>/<?= htmlspecialchars($photo['filename']) ?>', '<?= htmlspecialchars($photo['uploaded_by'] ?? '') ?>')">
                        <img src="/uploads/thumbs/<?= htmlspecialchars($categorySlug) ?>/<?= htmlspecialchars($photo['filename']) ?>"
                             alt="<?= htmlspecialchars($photo['description'] ?: $photo['original_name']) ?>"
                             loading="lazy">
                    </a>
                    <p class="photo-meta">
                        <?php if (!empty($photo['taken_at'])): ?>
                            <span class="photo-date"><?= date('d.m.Y', strtotime($photo['taken_at'])) ?></span>
                        <?php endif; ?>
                        <?php if ($auth->isLoggedIn()): ?>
                            <a href="#" class="photo-delete"
                               @click.prevent="if(confirm('Foto wirklich löschen?')) deletePhoto('<?= htmlspecialchars($photo['id']) ?>', '<?= htmlspecialchars($categorySlug) ?>')">Löschen</a>
                        <?php endif; ?>
                    </p>
                    <?php if (!empty($photo['description'])): ?>
                        <p class="photo-description"><?= htmlspecialchars($photo['description']) ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Lightbox -->
        <div class="lightbox" x-show="lightboxOpen" x-cloak @click="closeLightbox()" @keydown.escape.window="closeLightbox()">
            <button class="lightbox-close" @click="closeLightbox()" aria-label="Schließen">&times;</button>
            <div class="lightbox-content" @click.stop>
                <p class="lightbox-title" x-show="lightboxUploader" x-text="lightboxUploader"></p>
                <img :src="lightboxSrc" alt="Foto">
                <div class="lightbox-actions">
                    <a :href="lightboxSrc" target="_blank" class="lightbox-btn">Vergrößern</a>
                    <button @click="closeLightbox()" class="lightbox-btn">Schließen</button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
