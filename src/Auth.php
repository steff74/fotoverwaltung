<?php
/**
 * Authentifizierung und Session-Handling
 */

declare(strict_types=1);

class Auth
{
    private array $config;
    private ?User $userManager = null;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    private function getUserManager(): User
    {
        if ($this->userManager === null) {
            $this->userManager = new User($this->config);
        }
        return $this->userManager;
    }

    /**
     * Prüft Login-Daten und startet Session
     */
    public function login(string $email, string $password): bool
    {
        # Zuerst Admin aus Config prüfen
        if ($this->checkAdminConfig($email, $password)) {
            $_SESSION['user'] = [
                'id' => 'admin',
                'email' => $email,
                'role' => 'admin',
            ];
            return true;
        }

        # Dann User aus JSON prüfen
        $user = $this->getUserManager()->getByEmail($email);
        if ($user && $user['active'] && password_verify($password, $user['password_hash'])) {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'email' => $user['email'],
                'role' => $user['role'],
            ];
            return true;
        }

        return false;
    }

    /**
     * Prüft Admin-Credentials aus Config
     */
    private function checkAdminConfig(string $email, string $password): bool
    {
        $adminConfig = $this->config['admin'] ?? null;
        if (!$adminConfig) {
            return false;
        }

        return $adminConfig['email'] === $email
            && $adminConfig['password'] === $password;
    }

    /**
     * Logout
     */
    public function logout(): void
    {
        unset($_SESSION['user']);
        session_destroy();
    }

    /**
     * Ist User eingeloggt?
     */
    public function isLoggedIn(): bool
    {
        return isset($_SESSION['user']);
    }

    /**
     * Ist User Admin?
     */
    public function isAdmin(): bool
    {
        return $this->isLoggedIn() && ($_SESSION['user']['role'] ?? '') === 'admin';
    }

    /**
     * Gibt aktuelle Email zurück
     */
    public function getEmail(): string
    {
        return $_SESSION['user']['email'] ?? '';
    }

    /**
     * Gibt aktuelle User-ID zurück
     */
    public function getUserId(): string
    {
        return $_SESSION['user']['id'] ?? '';
    }

    /**
     * Gibt User-Daten zurück
     */
    public function getUser(): ?array
    {
        return $_SESSION['user'] ?? null;
    }
}
