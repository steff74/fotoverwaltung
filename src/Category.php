<?php
/**
 * Kategorien-Verwaltung
 */

declare(strict_types=1);

class Category
{
    private JsonStorage $storage;
    private string $originalsPath;
    private string $thumbsPath;

    public function __construct(array $config)
    {
        $dataPath = $config['paths']['data'];
        $this->storage = new JsonStorage($dataPath . '/categories.json');
        $this->originalsPath = $config['paths']['originals'];
        $this->thumbsPath = $config['paths']['thumbs'];
    }

    /**
     * Gibt alle Kategorien zurück (alphabetisch sortiert)
     */
    public function getAll(): array
    {
        $data = $this->storage->load();
        $categories = $data['categories'] ?? [];

        usort($categories, fn($a, $b) => strcasecmp($a['name'], $b['name']));

        return $categories;
    }

    /**
     * Findet Kategorie anhand ID
     */
    public function getById(string $id): ?array
    {
        return $this->storage->findInList('categories', 'id', $id);
    }

    /**
     * Findet Kategorie anhand Slug
     */
    public function getBySlug(string $slug): ?array
    {
        return $this->storage->findInList('categories', 'slug', $slug);
    }

    /**
     * Erstellt neue Kategorie
     */
    public function create(string $name, string $createdBy): ?array
    {
        $slug = $this->createSlug($name);

        # Prüfen ob Slug schon existiert
        if ($this->getBySlug($slug)) {
            return null;
        }

        $category = [
            'id' => JsonStorage::generateId(),
            'name' => $name,
            'slug' => $slug,
            'created_by' => $createdBy,
            'created_at' => date('c'),
        ];

        if ($this->storage->addToList('categories', $category)) {
            return $category;
        }

        return null;
    }

    /**
     * Aktualisiert Kategorie
     */
    public function update(string $id, array $data): bool
    {
        # Bei Namensänderung neuen Slug generieren
        if (isset($data['name'])) {
            $data['slug'] = $this->createSlug($data['name']);
        }

        return $this->storage->updateInList('categories', $id, $data);
    }

    /**
     * Löscht Kategorie
     */
    public function delete(string $id): bool
    {
        $category = $this->getById($id);
        if (!$category) {
            return false;
        }

        # Ordner löschen
        $originalsDir = $this->originalsPath . '/' . $category['slug'];
        $thumbsDir = $this->thumbsPath . '/' . $category['slug'];

        $this->deleteDirectory($originalsDir);
        $this->deleteDirectory($thumbsDir);

        return $this->storage->deleteFromList('categories', $id);
    }

    /**
     * Benennt Kategorie um (mit Berechtigungsprüfung)
     */
    public function rename(string $id, string $newName, string $userId, bool $isAdmin): array
    {
        $category = $this->getById($id);
        if (!$category) {
            return ['success' => false, 'error' => 'Kategorie nicht gefunden'];
        }

        # Berechtigung prüfen: Owner oder Admin
        if (!$isAdmin && $category['created_by'] !== $userId) {
            return ['success' => false, 'error' => 'Keine Berechtigung'];
        }

        # Neuen Slug generieren
        $newSlug = $this->createSlug($newName);
        $oldSlug = $category['slug'];

        # Wenn Slug sich nicht ändert, nur Namen aktualisieren
        if ($newSlug === $oldSlug) {
            $this->storage->updateInList('categories', $id, ['name' => $newName]);
            return ['success' => true, 'category' => array_merge($category, ['name' => $newName])];
        }

        # Prüfen ob neuer Slug bereits existiert
        if ($this->getBySlug($newSlug)) {
            return ['success' => false, 'error' => 'Eine Kategorie mit diesem Namen existiert bereits'];
        }

        # Ordner umbenennen
        $oldOriginalsDir = $this->originalsPath . '/' . $oldSlug;
        $newOriginalsDir = $this->originalsPath . '/' . $newSlug;
        $oldThumbsDir = $this->thumbsPath . '/' . $oldSlug;
        $newThumbsDir = $this->thumbsPath . '/' . $newSlug;

        if (is_dir($oldOriginalsDir)) {
            if (!rename($oldOriginalsDir, $newOriginalsDir)) {
                return ['success' => false, 'error' => 'Fehler beim Umbenennen des Ordners'];
            }
        }

        if (is_dir($oldThumbsDir)) {
            if (!rename($oldThumbsDir, $newThumbsDir)) {
                # Rollback: Originals-Ordner zurück
                if (is_dir($newOriginalsDir)) {
                    rename($newOriginalsDir, $oldOriginalsDir);
                }
                return ['success' => false, 'error' => 'Fehler beim Umbenennen des Thumb-Ordners'];
            }
        }

        # Kategorie aktualisieren
        $this->storage->updateInList('categories', $id, [
            'name' => $newName,
            'slug' => $newSlug
        ]);

        return [
            'success' => true,
            'category' => array_merge($category, ['name' => $newName, 'slug' => $newSlug])
        ];
    }

    /**
     * Gibt Anzahl der Fotos in einer Kategorie zurück
     */
    public function getPhotoCount(string $slug): int
    {
        $metaFile = $this->originalsPath . '/' . $slug . '/meta.json';
        if (!file_exists($metaFile)) {
            return 0;
        }

        $storage = new JsonStorage($metaFile);
        $data = $storage->load();
        return count($data['photos'] ?? []);
    }

    /**
     * Prüft ob User Berechtigung für Kategorie hat
     */
    public function canModify(string $id, string $userId, bool $isAdmin): bool
    {
        if ($isAdmin) {
            return true;
        }

        $category = $this->getById($id);
        return $category && $category['created_by'] === $userId;
    }

    /**
     * Löscht ein Verzeichnis rekursiv
     */
    private function deleteDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return true;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }

        return rmdir($dir);
    }

    /**
     * Erzeugt URL-freundlichen Slug aus Name
     */
    private function createSlug(string $name): string
    {
        # Umlaute ersetzen
        $replacements = [
            'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue',
            'Ä' => 'ae', 'Ö' => 'oe', 'Ü' => 'ue',
            'ß' => 'ss',
        ];
        $slug = str_replace(array_keys($replacements), array_values($replacements), $name);

        # Kleinschreibung
        $slug = strtolower($slug);

        # Nur Buchstaben, Zahlen und Bindestriche
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);

        # Mehrfache Bindestriche entfernen
        $slug = preg_replace('/-+/', '-', $slug);

        # Bindestriche am Anfang/Ende entfernen
        return trim($slug, '-');
    }
}
