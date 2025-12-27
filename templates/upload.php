<p class="breadcrumb">
    <a href="/">Start</a> / Fotos hochladen
</p>

<h1>Fotos hochladen</h1>

<div x-data="uploadForm()" class="upload-container">
    <form @submit.prevent="submitForm">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

        <label>
            Kategorie
            <select name="category" x-model="category" required>
                <option value="">Bitte wählen...</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat['slug']) ?>"
                            <?= (isset($_GET['category']) && $_GET['category'] === $cat['slug']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Beschreibung (optional)
            <input type="text" name="description" x-model="description" placeholder="z.B. Glühweinstand am Samstag">
        </label>

        <div class="dropzone"
             @dragover.prevent="isDragging = true"
             @dragleave="isDragging = false"
             @drop.prevent="handleDrop($event)"
             :class="{ 'dragging': isDragging }">
            <input type="file"
                   name="photos[]"
                   multiple
                   accept="image/jpeg,image/png,image/webp"
                   @change="handleFileSelect($event)"
                   id="file-input">
            <label for="file-input" class="dropzone-label">
                <span x-show="!files.length">Fotos hierher ziehen oder klicken zum Auswählen</span>
                <span x-show="files.length" x-text="files.length + ' Datei(en) ausgewählt'"></span>
            </label>
        </div>

        <!-- Vorschau -->
        <div class="preview-grid" x-show="previews.length">
            <template x-for="(preview, index) in previews" :key="index">
                <div class="preview-item">
                    <img :src="preview.url" :alt="preview.name">
                    <button type="button" @click="removeFile(index)" class="remove-preview">&times;</button>
                </div>
            </template>
        </div>

        <!-- Upload-Fortschritt -->
        <div x-show="uploading" class="upload-progress">
            <progress :value="progress" max="100"></progress>
            <span x-text="progress + '%'"></span>
        </div>

        <!-- Ergebnisse -->
        <div x-show="results.length" class="upload-results">
            <template x-for="result in results" :key="result.photo?.id || Math.random()">
                <p :class="result.success ? 'success' : 'error'">
                    <span x-text="result.success ? 'Hochgeladen: ' + (result.photo?.original_name || 'OK') : 'Fehler: ' + result.error"></span>
                </p>
            </template>
        </div>

        <button type="submit" :disabled="!files.length || uploading || !category">
            <span x-show="!uploading">Hochladen</span>
            <span x-show="uploading">Wird hochgeladen...</span>
        </button>
    </form>
</div>
