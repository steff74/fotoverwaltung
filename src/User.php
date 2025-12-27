<?php
/**
 * User-Verwaltung
 */

declare(strict_types=1);

class User
{
    private JsonStorage $storage;
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $dataPath = $config['paths']['data'];
        $this->storage = new JsonStorage($dataPath . '/users.json');
    }

    /**
     * Gibt alle User zurück
     */
    public function getAll(): array
    {
        $data = $this->storage->load();
        return $data['users'] ?? [];
    }

    /**
     * Findet User anhand ID
     */
    public function getById(string $id): ?array
    {
        return $this->storage->findInList('users', 'id', $id);
    }

    /**
     * Findet User anhand Email
     */
    public function getByEmail(string $email): ?array
    {
        return $this->storage->findInList('users', 'email', $email);
    }

    /**
     * Findet User anhand Reset-Token
     */
    public function getByResetToken(string $token): ?array
    {
        $users = $this->getAll();
        foreach ($users as $user) {
            if (isset($user['reset_token']) && $user['reset_token'] === $token) {
                # Prüfen ob Token noch gültig
                if (isset($user['reset_expires']) && strtotime($user['reset_expires']) > time()) {
                    return $user;
                }
            }
        }
        return null;
    }

    /**
     * Erstellt neuen User
     */
    public function create(string $email, string $password, string $role = 'user'): ?array
    {
        # Prüfen ob Email schon existiert
        if ($this->getByEmail($email)) {
            return null;
        }

        $user = [
            'id' => JsonStorage::generateId(),
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
            'active' => true,
            'reset_token' => null,
            'reset_expires' => null,
            'created_at' => date('c'),
        ];

        if ($this->storage->addToList('users', $user)) {
            return $user;
        }

        return null;
    }

    /**
     * Aktualisiert User
     */
    public function update(string $id, array $data): bool
    {
        # Passwort separat behandeln
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        unset($data['password']);

        return $this->storage->updateInList('users', $id, $data);
    }

    /**
     * Sperrt/Entsperrt User
     */
    public function setActive(string $id, bool $active): bool
    {
        return $this->storage->updateInList('users', $id, ['active' => $active]);
    }

    /**
     * Löscht User
     */
    public function delete(string $id): bool
    {
        return $this->storage->deleteFromList('users', $id);
    }

    /**
     * Generiert Reset-Token für Passwort-Vergessen
     */
    public function generateResetToken(string $email): ?string
    {
        $user = $this->getByEmail($email);
        if (!$user) {
            return null;
        }

        $token = bin2hex(random_bytes(32));
        $expires = date('c', strtotime('+1 hour'));

        $this->update($user['id'], [
            'reset_token' => $token,
            'reset_expires' => $expires,
        ]);

        return $token;
    }

    /**
     * Setzt Passwort mit Reset-Token
     */
    public function resetPassword(string $token, string $newPassword): bool
    {
        $user = $this->getByResetToken($token);
        if (!$user) {
            return false;
        }

        return $this->update($user['id'], [
            'password' => $newPassword,
            'reset_token' => null,
            'reset_expires' => null,
        ]);
    }
}
