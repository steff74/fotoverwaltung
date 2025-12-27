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
    <?php
    // Foto-Daten für JavaScript aufbereiten
    $photosJs = array_values(array_map(function($photo) use ($categorySlug) {
        return [
            'id' => $photo['id'],
            'src' => '/uploads/originals/' . $categorySlug . '/' . $photo['filename'],
            'uploader' => $photo['uploaded_by'] ?? '',
            'description' => $photo['description'] ?? ''
        ];
    }, $photos));
    ?>
    <div x-data="photoGallery(<?= htmlspecialchars(json_encode($photosJs)) ?>, '<?= htmlspecialchars($categorySlug) ?>', <?= $auth->isLoggedIn() ? 'true' : 'false' ?>)">
        <div class="photo-grid">
            <?php foreach ($photos as $index => $photo): ?>
                <div class="photo-item" data-id="<?= htmlspecialchars($photo['id']) ?>">
                    <a href="#" @click.prevent="openLightbox(<?= $index ?>)">
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
        <div class="lightbox" x-show="lightboxOpen" x-cloak @click="closeLightbox()"
             @keydown.escape.window="closeLightbox()"
             @keydown.left.window="prevPhoto()"
             @keydown.right.window="nextPhoto()">
            <button class="lightbox-close" @click="closeLightbox()" aria-label="Schließen">&times;</button>

            <!-- Navigation: Zurück -->
            <button class="lightbox-nav lightbox-prev" @click.stop="prevPhoto()" aria-label="Vorheriges Bild">&#8249;</button>

            <div class="lightbox-content" @click.stop>
                <div class="lightbox-header" x-show="!imageLoading">
                    <!-- Beschreibung: Anzeige oder Edit -->
                    <span class="lightbox-description" x-show="!editingDescription"
                          @click="startEditDescription()"
                          :class="{ 'editable': canEdit, 'placeholder': !lightboxDescription }"
                          x-text="lightboxDescription || (canEdit ? 'Beschreibung hinzufügen...' : '')"></span>

                    <!-- Edit-Modus -->
                    <div class="lightbox-edit" x-show="editingDescription" @click.stop>
                        <input type="text" x-model="tempDescription" x-ref="descriptionInput"
                               placeholder="Beschreibung eingeben..."
                               @keydown.enter="saveDescription()"
                               @keydown.escape="cancelEditDescription()">
                        <button class="lightbox-edit-btn" @click="saveDescription()">&#10003;</button>
                        <button class="lightbox-edit-btn" @click="cancelEditDescription()">&#10005;</button>
                    </div>

                    <span class="lightbox-uploader" x-text="lightboxUploader"></span>
                </div>

                <!-- Spinner -->
                <div class="lightbox-spinner" x-show="imageLoading">
                    <div class="spinner"></div>
                </div>

                <!-- Bild -->
                <img :src="lightboxSrc" alt="Foto" x-show="!imageLoading" @load="onImageLoad()">

                <div class="lightbox-actions" x-show="!imageLoading">
                    <a :href="lightboxSrc" target="_blank" class="lightbox-btn">Vergrößern</a>
                    <button @click="closeLightbox()" class="lightbox-btn">Schließen</button>
                </div>
            </div>

            <!-- Navigation: Weiter -->
            <button class="lightbox-nav lightbox-next" @click.stop="nextPhoto()" aria-label="Nächstes Bild">&#8250;</button>
        </div>
    </div>
<?php endif; ?>
