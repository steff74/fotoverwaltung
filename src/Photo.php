<?php
/**
 * Foto-Verwaltung mit Upload, Resize und EXIF-Auslesen
 */

declare(strict_types=1);

class Photo
{
    private array $config;
    private string $originalsPath;
    private string $thumbsPath;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->originalsPath = $config['paths']['originals'];
        $this->thumbsPath = $config['paths']['thumbs'];
    }

    /**
     * Gibt alle Fotos einer Kategorie zurück (sortiert nach Aufnahmedatum)
     */
    public function getByCategory(string $categorySlug): array
    {
        $metaFile = $this->originalsPath . '/' . $categorySlug . '/meta.json';
        $storage = new JsonStorage($metaFile);
        $data = $storage->load();
        $photos = $data['photos'] ?? [];

        # Nach Aufnahmedatum sortieren (neueste zuerst)
        usort($photos, function ($a, $b) {
            $dateA = $a['taken_at'] ?? $a['uploaded_at'];
            $dateB = $b['taken_at'] ?? $b['uploaded_at'];
            return strtotime($dateB) - strtotime($dateA);
        });

        return $photos;
    }

    /**
     * Findet ein Foto anhand der ID
     */
    public function getById(string $categorySlug, string $id): ?array
    {
        $photos = $this->getByCategory($categorySlug);
        foreach ($photos as $photo) {
            if ($photo['id'] === $id) {
                return $photo;
            }
        }
        return null;
    }

    /**
     * Upload eines Fotos
     */
    public function upload(array $file, string $categorySlug, string $uploadedBy, string $description = ''): array
    {
        # Fehlerprüfung
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => $this->getUploadErrorMessage($file['error'])];
        }

        # Dateityp prüfen
        $allowedTypes = $this->config['images']['allowed_types'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            return ['success' => false, 'error' => 'Ungültiger Dateityp: ' . $mimeType];
        }

        # Dateigröße prüfen
        $maxSize = $this->config['images']['max_upload_size'];
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'error' => 'Datei zu groß (max. ' . ($maxSize / 1024 / 1024) . ' MB)'];
        }

        # Verzeichnisse erstellen
        $categoryOriginalPath = $this->originalsPath . '/' . $categorySlug;
        $categoryThumbPath = $this->thumbsPath . '/' . $categorySlug;

        if (!is_dir($categoryOriginalPath)) {
            mkdir($categoryOriginalPath, 0755, true);
        }
        if (!is_dir($categoryThumbPath)) {
            mkdir($categoryThumbPath, 0755, true);
        }

        # Eindeutigen Dateinamen generieren
        $id = JsonStorage::generateId();
        $extension = $this->getExtensionFromMime($mimeType);
        $filename = $id . '.' . $extension;

        $originalFile = $categoryOriginalPath . '/' . $filename;
        $thumbFile = $categoryThumbPath . '/' . $filename;

        # Datei verschieben
        if (!move_uploaded_file($file['tmp_name'], $originalFile)) {
            return ['success' => false, 'error' => 'Datei konnte nicht gespeichert werden'];
        }

        # EXIF-Daten auslesen
        $exifData = $this->readExifData($originalFile);
        $takenAt = $exifData['taken_at'];
        $width = $exifData['width'];
        $height = $exifData['height'];

        # Thumbnail erstellen
        $this->createThumbnail($originalFile, $thumbFile, $mimeType);

        # Metadaten speichern
        $photo = [
            'id' => $id,
            'filename' => $filename,
            'original_name' => $file['name'],
            'description' => $description,
            'uploaded_by' => $uploadedBy,
            'uploaded_at' => date('c'),
            'taken_at' => $takenAt,
            'width' => $width,
            'height' => $height,
        ];

        $metaFile = $categoryOriginalPath . '/meta.json';
        $storage = new JsonStorage($metaFile);
        $storage->addToList('photos', $photo);

        return ['success' => true, 'photo' => $photo];
    }

    /**
     * Löscht ein Foto
     */
    public function delete(string $categorySlug, string $id): bool
    {
        $photo = $this->getById($categorySlug, $id);
        if (!$photo) {
            return false;
        }

        # Dateien löschen
        $originalFile = $this->originalsPath . '/' . $categorySlug . '/' . $photo['filename'];
        $thumbFile = $this->thumbsPath . '/' . $categorySlug . '/' . $photo['filename'];

        if (file_exists($originalFile)) {
            unlink($originalFile);
        }
        if (file_exists($thumbFile)) {
            unlink($thumbFile);
        }

        # Aus Metadaten entfernen
        $metaFile = $this->originalsPath . '/' . $categorySlug . '/meta.json';
        $storage = new JsonStorage($metaFile);
        return $storage->deleteFromList('photos', $id);
    }

    /**
     * Aktualisiert Foto-Beschreibung
     */
    public function updateDescription(string $categorySlug, string $id, string $description): bool
    {
        $metaFile = $this->originalsPath . '/' . $categorySlug . '/meta.json';
        $storage = new JsonStorage($metaFile);
        return $storage->updateInList('photos', $id, ['description' => $description]);
    }

    /**
     * Liest EXIF-Daten aus einem Bild
     */
    private function readExifData(string $filepath): array
    {
        $result = [
            'taken_at' => null,
            'width' => 0,
            'height' => 0,
        ];

        # Bildgröße ermitteln
        $imageInfo = getimagesize($filepath);
        if ($imageInfo) {
            $result['width'] = $imageInfo[0];
            $result['height'] = $imageInfo[1];
        }

        # EXIF-Daten lesen (nur für JPEG)
        if (function_exists('exif_read_data')) {
            $exif = @exif_read_data($filepath, 'EXIF');
            if ($exif) {
                # Aufnahmedatum ermitteln
                $dateFields = ['DateTimeOriginal', 'DateTimeDigitized', 'DateTime'];
                foreach ($dateFields as $field) {
                    if (!empty($exif[$field])) {
                        $dateTime = DateTime::createFromFormat('Y:m:d H:i:s', $exif[$field]);
                        if ($dateTime) {
                            $result['taken_at'] = $dateTime->format('c');
                            break;
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Erstellt ein Thumbnail
     */
    private function createThumbnail(string $source, string $dest, string $mimeType): bool
    {
        $maxWidth = $this->config['images']['thumb_max_width'];

        # Bild laden
        switch ($mimeType) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($source);
                break;
            case 'image/png':
                $image = imagecreatefrompng($source);
                break;
            case 'image/webp':
                $image = imagecreatefromwebp($source);
                break;
            default:
                return false;
        }

        if (!$image) {
            return false;
        }

        # EXIF-Orientierung korrigieren
        $image = $this->fixOrientation($source, $image);

        $origWidth = imagesx($image);
        $origHeight = imagesy($image);

        # Nur verkleinern wenn nötig
        if ($origWidth <= $maxWidth) {
            $newWidth = $origWidth;
            $newHeight = $origHeight;
        } else {
            $newWidth = $maxWidth;
            $newHeight = (int) ($origHeight * ($maxWidth / $origWidth));
        }

        # Neues Bild erstellen
        $thumb = imagecreatetruecolor($newWidth, $newHeight);

        # Transparenz für PNG erhalten
        if ($mimeType === 'image/png') {
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
        }

        # Resamplen
        imagecopyresampled($thumb, $image, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

        # Speichern
        $result = false;
        switch ($mimeType) {
            case 'image/jpeg':
                $result = imagejpeg($thumb, $dest, 85);
                break;
            case 'image/png':
                $result = imagepng($thumb, $dest, 8);
                break;
            case 'image/webp':
                $result = imagewebp($thumb, $dest, 85);
                break;
        }

        imagedestroy($image);
        imagedestroy($thumb);

        return $result;
    }

    /**
     * Korrigiert die Bildorientierung basierend auf EXIF-Daten
     */
    private function fixOrientation(string $filepath, $image)
    {
        if (!function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($filepath);
        if (!$exif || !isset($exif['Orientation'])) {
            return $image;
        }

        switch ($exif['Orientation']) {
            case 3:
                $image = imagerotate($image, 180, 0);
                break;
            case 6:
                $image = imagerotate($image, -90, 0);
                break;
            case 8:
                $image = imagerotate($image, 90, 0);
                break;
        }

        return $image;
    }

    /**
     * Ermittelt Dateierweiterung aus MIME-Typ
     */
    private function getExtensionFromMime(string $mimeType): string
    {
        return match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };
    }

    /**
     * Gibt Upload-Fehlermeldung zurück
     */
    private function getUploadErrorMessage(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Datei zu groß',
            UPLOAD_ERR_PARTIAL => 'Upload unvollständig',
            UPLOAD_ERR_NO_FILE => 'Keine Datei hochgeladen',
            UPLOAD_ERR_NO_TMP_DIR => 'Temporäres Verzeichnis fehlt',
            UPLOAD_ERR_CANT_WRITE => 'Schreibfehler',
            default => 'Unbekannter Fehler',
        };
    }

    /**
     * Gibt URL zum Original zurück
     */
    public function getOriginalUrl(string $categorySlug, string $filename): string
    {
        return '/uploads/originals/' . $categorySlug . '/' . $filename;
    }

    /**
     * Gibt URL zum Thumbnail zurück
     */
    public function getThumbUrl(string $categorySlug, string $filename): string
    {
        return '/uploads/thumbs/' . $categorySlug . '/' . $filename;
    }
}
