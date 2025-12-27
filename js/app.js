// Adventmarkt Fotos - JavaScript

// CSRF Token aus Meta-Tag holen
function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

// API-Request mit CSRF
async function apiRequest(url, data = {}) {
    const formData = new FormData();
    formData.append('csrf_token', getCsrfToken());

    for (const [key, value] of Object.entries(data)) {
        formData.append(key, value);
    }

    const response = await fetch(url, {
        method: 'POST',
        body: formData
    });

    return response.json();
}

// Upload-Formular
function uploadForm() {
    return {
        category: new URLSearchParams(window.location.search).get('category') || '',
        description: '',
        files: [],
        previews: [],
        uploading: false,
        progress: 0,
        results: [],
        isDragging: false,

        handleFileSelect(event) {
            this.addFiles(event.target.files);
        },

        handleDrop(event) {
            this.isDragging = false;
            this.addFiles(event.dataTransfer.files);
        },

        addFiles(fileList) {
            for (const file of fileList) {
                if (file.type.startsWith('image/')) {
                    this.files.push(file);

                    // Vorschau erstellen
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.previews.push({
                            name: file.name,
                            url: e.target.result
                        });
                    };
                    reader.readAsDataURL(file);
                }
            }
        },

        removeFile(index) {
            this.files.splice(index, 1);
            this.previews.splice(index, 1);
        },

        async submitForm() {
            if (!this.files.length || !this.category) return;

            this.uploading = true;
            this.progress = 0;
            this.results = [];

            const formData = new FormData();
            formData.append('csrf_token', getCsrfToken());
            formData.append('category', this.category);
            formData.append('description', this.description);

            for (const file of this.files) {
                formData.append('photos[]', file);
            }

            try {
                const xhr = new XMLHttpRequest();

                xhr.upload.addEventListener('progress', (e) => {
                    if (e.lengthComputable) {
                        this.progress = Math.round((e.loaded / e.total) * 100);
                    }
                });

                xhr.onload = () => {
                    const response = JSON.parse(xhr.responseText);
                    if (response.results) {
                        this.results = response.results;
                    }
                    this.uploading = false;

                    // Bei Erfolg: Dateien zurücksetzen
                    if (response.success) {
                        this.files = [];
                        this.previews = [];
                        this.description = '';
                    }
                };

                xhr.onerror = () => {
                    this.results = [{ success: false, error: 'Netzwerkfehler' }];
                    this.uploading = false;
                };

                xhr.open('POST', '/upload');
                xhr.send(formData);
            } catch (error) {
                this.results = [{ success: false, error: error.message }];
                this.uploading = false;
            }
        }
    };
}

// Foto-Galerie mit Lightbox und Löschen
function photoGallery(photos = [], categorySlug = '', canEdit = false) {
    return {
        photos: photos,
        categorySlug: categorySlug,
        canEdit: canEdit,
        currentIndex: 0,
        lightboxOpen: false,
        lightboxSrc: '',
        lightboxUploader: '',
        lightboxDescription: '',
        imageLoading: true,
        editingDescription: false,
        tempDescription: '',

        openLightbox(index) {
            this.currentIndex = index;
            this.showPhoto();
            this.lightboxOpen = true;
            document.body.style.overflow = 'hidden';
        },

        showPhoto() {
            this.imageLoading = true;
            const photo = this.photos[this.currentIndex];
            if (photo) {
                this.lightboxSrc = photo.src;
                this.lightboxUploader = photo.uploader || '';
                this.lightboxDescription = photo.description || '';
            }
        },

        nextPhoto() {
            this.currentIndex = (this.currentIndex + 1) % this.photos.length;
            this.showPhoto();
        },

        prevPhoto() {
            this.currentIndex = (this.currentIndex - 1 + this.photos.length) % this.photos.length;
            this.showPhoto();
        },

        onImageLoad() {
            this.imageLoading = false;
        },

        closeLightbox() {
            this.lightboxOpen = false;
            this.lightboxSrc = '';
            this.lightboxUploader = '';
            this.lightboxDescription = '';
            this.imageLoading = true;
            this.editingDescription = false;
            document.body.style.overflow = '';
        },

        startEditDescription() {
            if (!this.canEdit) return;
            this.tempDescription = this.lightboxDescription;
            this.editingDescription = true;
            this.$nextTick(() => {
                this.$refs.descriptionInput?.focus();
            });
        },

        cancelEditDescription() {
            this.editingDescription = false;
            this.tempDescription = '';
        },

        async saveDescription() {
            const photo = this.photos[this.currentIndex];
            if (!photo) return;

            const result = await apiRequest('/api/photos/update', {
                id: photo.id,
                category: this.categorySlug,
                description: this.tempDescription
            });

            if (result.success) {
                location.reload();
            } else {
                alert(result.error || 'Fehler beim Speichern');
            }
        },

        async deletePhoto(id, category) {
            const result = await apiRequest('/api/photos/delete', { id, category });

            if (result.success) {
                document.querySelector(`[data-id="${id}"]`)?.remove();
            } else {
                alert(result.error || 'Fehler beim Löschen');
            }
        }
    };
}

// Kategorien-Admin
function categoryAdmin() {
    return {
        newCategoryName: '',
        message: '',
        messageType: '',

        async createCategory() {
            const result = await apiRequest('/api/categories', { name: this.newCategoryName });

            if (result.success) {
                this.message = 'Kategorie erstellt';
                this.messageType = 'success-message';
                this.newCategoryName = '';
                // Seite neu laden um Liste zu aktualisieren
                setTimeout(() => location.reload(), 500);
            } else {
                this.message = result.error || 'Fehler';
                this.messageType = 'error-message';
            }
        },

        async renameCategory(id, currentName) {
            const newName = prompt('Neuer Name für die Kategorie:', currentName);
            if (!newName || newName.trim() === '' || newName === currentName) return;

            const result = await apiRequest('/api/categories/rename', { id, name: newName.trim() });

            if (result.success) {
                this.message = 'Kategorie umbenannt';
                this.messageType = 'success-message';
                // Seite neu laden um Liste zu aktualisieren
                setTimeout(() => location.reload(), 500);
            } else {
                this.message = result.error || 'Fehler beim Umbenennen';
                this.messageType = 'error-message';
            }
        },

        async deleteCategory(id, name, photoCount) {
            let msg = `Kategorie "${name}" wirklich löschen?`;
            if (photoCount > 0) {
                msg += `\n\nACHTUNG: ${photoCount} Foto${photoCount !== 1 ? 's' : ''} werden ebenfalls gelöscht!`;
            }
            if (!confirm(msg)) return;

            const result = await apiRequest('/api/categories/delete', { id });

            if (result.success) {
                document.querySelector(`[data-id="${id}"]`)?.remove();
                this.message = 'Kategorie gelöscht';
                this.messageType = 'success-message';
            } else {
                this.message = result.error || 'Fehler beim Löschen';
                this.messageType = 'error-message';
            }
        }
    };
}

// User-Admin
function userAdmin() {
    return {
        newUser: {
            email: '',
            password: '',
            role: 'user'
        },
        message: '',
        messageType: '',

        async createUser() {
            const result = await apiRequest('/api/users', this.newUser);

            if (result.success) {
                this.message = 'User erstellt';
                this.messageType = 'success-message';
                this.newUser = { email: '', password: '', role: 'user' };
                setTimeout(() => location.reload(), 500);
            } else {
                this.message = result.error || 'Fehler';
                this.messageType = 'error-message';
            }
        },

        async toggleUser(id, active) {
            const result = await apiRequest('/api/users/toggle', { id, active: active ? '1' : '0' });

            if (result.success) {
                location.reload();
            } else {
                this.message = result.error || 'Fehler';
                this.messageType = 'error-message';
            }
        },

        async deleteUser(id, email) {
            if (!confirm(`User "${email}" wirklich löschen?`)) return;

            const result = await apiRequest('/api/users/delete', { id });

            if (result.success) {
                document.querySelector(`[data-id="${id}"]`)?.remove();
                this.message = 'User gelöscht';
                this.messageType = 'success-message';
            } else {
                this.message = result.error || 'Fehler beim Löschen';
                this.messageType = 'error-message';
            }
        }
    };
}
