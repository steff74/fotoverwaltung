<?php
/**
 * Generische JSON-Datei-Operationen
 */

declare(strict_types=1);

class JsonStorage
{
    private string $filePath;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * Lädt Daten aus JSON-Datei
     */
    public function load(): array
    {
        if (!file_exists($this->filePath)) {
            return [];
        }

        $content = file_get_contents($this->filePath);
        if ($content === false) {
            return [];
        }

        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
    }

    /**
     * Speichert Daten in JSON-Datei
     */
    public function save(array $data): bool
    {
        $dir = dirname($this->filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return file_put_contents($this->filePath, $json) !== false;
    }

    /**
     * Fügt einen Eintrag zu einer Liste hinzu
     */
    public function addToList(string $key, array $item): bool
    {
        $data = $this->load();
        if (!isset($data[$key])) {
            $data[$key] = [];
        }
        $data[$key][] = $item;
        return $this->save($data);
    }

    /**
     * Aktualisiert einen Eintrag anhand der ID
     */
    public function updateInList(string $key, string $id, array $newData): bool
    {
        $data = $this->load();
        if (!isset($data[$key])) {
            return false;
        }

        foreach ($data[$key] as $index => $item) {
            if (isset($item['id']) && $item['id'] === $id) {
                $data[$key][$index] = array_merge($item, $newData);
                return $this->save($data);
            }
        }

        return false;
    }

    /**
     * Löscht einen Eintrag anhand der ID
     */
    public function deleteFromList(string $key, string $id): bool
    {
        $data = $this->load();
        if (!isset($data[$key])) {
            return false;
        }

        foreach ($data[$key] as $index => $item) {
            if (isset($item['id']) && $item['id'] === $id) {
                array_splice($data[$key], $index, 1);
                return $this->save($data);
            }
        }

        return false;
    }

    /**
     * Findet einen Eintrag anhand eines Feldes
     */
    public function findInList(string $key, string $field, mixed $value): ?array
    {
        $data = $this->load();
        if (!isset($data[$key])) {
            return null;
        }

        foreach ($data[$key] as $item) {
            if (isset($item[$field]) && $item[$field] === $value) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Generiert eine UUID
     */
    public static function generateId(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
