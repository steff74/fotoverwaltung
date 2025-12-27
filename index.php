<?php
/**
 * Einstiegspunkt und Router für adventmarkt.usbdata.at
 */

declare(strict_types=1);

# Autoloader für src/-Klassen
spl_autoload_register(function (string $class): void {
    $file = __DIR__ . '/src/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

# Konfiguration laden
$config = require __DIR__ . '/config/config.php';

# Session starten
session_name($config['session']['name']);
session_start();

# CSRF-Token generieren falls nicht vorhanden
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

# Request-Pfad ermitteln
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$basePath = dirname($_SERVER['SCRIPT_NAME']);
if ($basePath !== '/') {
    $requestUri = substr($requestUri, strlen($basePath));
}
$path = parse_url($requestUri, PHP_URL_PATH) ?: '/';
$path = '/' . trim($path, '/');

# HTTP-Methode
$method = $_SERVER['REQUEST_METHOD'];

# Hilfsfunktion für Templates
function render(string $template, array $data = []): void {
    extract($data);
    require __DIR__ . '/templates/layout.php';
}

# Hilfsfunktion für JSON-Response
function jsonResponse(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

# CSRF-Prüfung
function checkCsrf(): bool {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return hash_equals($_SESSION['csrf_token'], $token);
}

# Einfaches Routing
$routes = [
    'GET' => [
        '/' => 'home',
        '/login' => 'login',
        '/logout' => 'logout',
        '/upload' => 'upload',
        '/passwort-vergessen' => 'forgot-password',
        '/reset' => 'reset-password',
        '/admin/users' => 'admin/users',
        '/admin/categories' => 'admin/categories',
    ],
    'POST' => [
        '/login' => 'login_post',
        '/upload' => 'upload_post',
        '/passwort-vergessen' => 'forgot-password_post',
        '/reset' => 'reset-password_post',
        '/api/photos' => 'api/photos',
        '/api/photos/delete' => 'api/photos_delete',
        '/api/categories' => 'api/categories',
        '/api/categories/delete' => 'api/categories_delete',
        '/api/categories/rename' => 'api/categories_rename',
        '/api/users' => 'api/users',
        '/api/users/delete' => 'api/users_delete',
        '/api/users/toggle' => 'api/users_toggle',
    ],
];

# Kategorie-Route (dynamisch)
if (preg_match('#^/kategorie/([a-z0-9-]+)$#', $path, $matches)) {
    $categorySlug = $matches[1];
    $template = 'category';
} elseif (isset($routes[$method][$path])) {
    $template = $routes[$method][$path];
} else {
    http_response_code(404);
    $template = '404';
}

# Auth-Instanz für Templates
$auth = new Auth($config);
$userManager = new User($config);
$categoryManager = new Category($config);

# Template-spezifische Logik
switch ($template) {
    case 'logout':
        $auth->logout();
        header('Location: /');
        exit;

    case 'login_post':
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        if ($auth->login($email, $password)) {
            header('Location: /');
            exit;
        }
        $error = 'Ungültige Zugangsdaten';
        $template = 'login';
        break;

    case 'forgot-password_post':
        $email = $_POST['email'] ?? '';
        $token = $userManager->generateResetToken($email);
        # Immer Erfolg zeigen (Sicherheit: keine Info ob Email existiert)
        $success = 'Falls ein Konto mit dieser E-Mail existiert, wurde ein Reset-Link versendet.';
        if ($token) {
            # TODO: Email versenden mit Link /reset?token=$token
            # Für Entwicklung: Token in Session speichern
            $_SESSION['dev_reset_token'] = $token;
        }
        $template = 'forgot-password';
        break;

    case 'reset-password':
        $token = $_GET['token'] ?? '';
        $validToken = $userManager->getByResetToken($token);
        if (!$validToken) {
            $error = 'Ungültiger oder abgelaufener Reset-Link.';
        }
        break;

    case 'reset-password_post':
        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        if ($password !== $passwordConfirm) {
            $error = 'Passwörter stimmen nicht überein.';
            $template = 'reset-password';
        } elseif (strlen($password) < 8) {
            $error = 'Passwort muss mindestens 8 Zeichen lang sein.';
            $template = 'reset-password';
        } elseif ($userManager->resetPassword($token, $password)) {
            $success = 'Passwort wurde geändert. Du kannst dich jetzt anmelden.';
            $template = 'login';
        } else {
            $error = 'Ungültiger oder abgelaufener Reset-Link.';
            $template = 'reset-password';
        }
        break;

    case 'upload':
        if (!$auth->isLoggedIn()) {
            header('Location: /login');
            exit;
        }
        break;

    case 'upload_post':
        if (!$auth->isLoggedIn()) {
            jsonResponse(['error' => 'Nicht eingeloggt'], 401);
        }
        if (!checkCsrf()) {
            jsonResponse(['error' => 'Ungültiges Token'], 403);
        }

        $photoManager = new Photo($config);
        $categorySlug = $_POST['category'] ?? '';
        $description = $_POST['description'] ?? '';

        if (empty($categorySlug)) {
            jsonResponse(['error' => 'Kategorie fehlt'], 400);
        }

        $results = [];
        if (isset($_FILES['photos'])) {
            $files = $_FILES['photos'];
            $count = is_array($files['name']) ? count($files['name']) : 1;

            for ($i = 0; $i < $count; $i++) {
                $file = [
                    'name' => is_array($files['name']) ? $files['name'][$i] : $files['name'],
                    'type' => is_array($files['type']) ? $files['type'][$i] : $files['type'],
                    'tmp_name' => is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'],
                    'error' => is_array($files['error']) ? $files['error'][$i] : $files['error'],
                    'size' => is_array($files['size']) ? $files['size'][$i] : $files['size'],
                ];

                $result = $photoManager->upload($file, $categorySlug, $auth->getEmail(), $description);
                $results[] = $result;
            }
        }

        jsonResponse(['success' => true, 'results' => $results]);
        break;

    case 'api/photos_delete':
        if (!$auth->isLoggedIn()) {
            jsonResponse(['error' => 'Nicht eingeloggt'], 401);
        }
        if (!checkCsrf()) {
            jsonResponse(['error' => 'Ungültiges Token'], 403);
        }

        $photoManager = new Photo($config);
        $photoId = $_POST['id'] ?? '';
        $categorySlug = $_POST['category'] ?? '';

        # Jeder eingeloggte User darf löschen
        $photo = $photoManager->getById($categorySlug, $photoId);
        if (!$photo) {
            jsonResponse(['error' => 'Foto nicht gefunden'], 404);
        }

        if ($photoManager->delete($categorySlug, $photoId)) {
            jsonResponse(['success' => true]);
        } else {
            jsonResponse(['error' => 'Löschen fehlgeschlagen'], 500);
        }
        break;

    case 'api/categories':
        if (!$auth->isLoggedIn()) {
            jsonResponse(['error' => 'Nicht eingeloggt'], 401);
        }
        if (!checkCsrf()) {
            jsonResponse(['error' => 'Ungültiges Token'], 403);
        }

        $name = $_POST['name'] ?? '';
        if (empty($name)) {
            jsonResponse(['error' => 'Name fehlt'], 400);
        }

        $category = $categoryManager->create($name, $auth->getUserId());
        if ($category) {
            jsonResponse(['success' => true, 'category' => $category]);
        } else {
            jsonResponse(['error' => 'Kategorie existiert bereits'], 400);
        }
        break;

    case 'api/categories_delete':
        if (!$auth->isLoggedIn()) {
            jsonResponse(['error' => 'Nicht eingeloggt'], 401);
        }
        if (!checkCsrf()) {
            jsonResponse(['error' => 'Ungültiges Token'], 403);
        }

        $id = $_POST['id'] ?? '';

        # Berechtigung prüfen: Owner oder Admin
        if (!$categoryManager->canModify($id, $auth->getUserId(), $auth->isAdmin())) {
            jsonResponse(['error' => 'Keine Berechtigung'], 403);
        }

        if ($categoryManager->delete($id)) {
            jsonResponse(['success' => true]);
        } else {
            jsonResponse(['error' => 'Löschen fehlgeschlagen'], 500);
        }
        break;

    case 'api/categories_rename':
        if (!$auth->isLoggedIn()) {
            jsonResponse(['error' => 'Nicht eingeloggt'], 401);
        }
        if (!checkCsrf()) {
            jsonResponse(['error' => 'Ungültiges Token'], 403);
        }

        $id = $_POST['id'] ?? '';
        $name = trim($_POST['name'] ?? '');

        if (empty($name)) {
            jsonResponse(['error' => 'Name darf nicht leer sein'], 400);
        }

        $result = $categoryManager->rename($id, $name, $auth->getUserId(), $auth->isAdmin());

        if ($result['success']) {
            jsonResponse(['success' => true, 'category' => $result['category']]);
        } else {
            jsonResponse(['error' => $result['error']], 400);
        }
        break;

    case 'api/users':
        if (!$auth->isAdmin()) {
            jsonResponse(['error' => 'Keine Berechtigung'], 403);
        }
        if (!checkCsrf()) {
            jsonResponse(['error' => 'Ungültiges Token'], 403);
        }

        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'user';

        if (empty($email) || empty($password)) {
            jsonResponse(['error' => 'Email und Passwort erforderlich'], 400);
        }

        $user = $userManager->create($email, $password, $role);
        if ($user) {
            unset($user['password_hash']);
            jsonResponse(['success' => true, 'user' => $user]);
        } else {
            jsonResponse(['error' => 'Email existiert bereits'], 400);
        }
        break;

    case 'api/users_delete':
        if (!$auth->isAdmin()) {
            jsonResponse(['error' => 'Keine Berechtigung'], 403);
        }
        if (!checkCsrf()) {
            jsonResponse(['error' => 'Ungültiges Token'], 403);
        }

        $id = $_POST['id'] ?? '';
        if ($userManager->delete($id)) {
            jsonResponse(['success' => true]);
        } else {
            jsonResponse(['error' => 'Löschen fehlgeschlagen'], 500);
        }
        break;

    case 'api/users_toggle':
        if (!$auth->isAdmin()) {
            jsonResponse(['error' => 'Keine Berechtigung'], 403);
        }
        if (!checkCsrf()) {
            jsonResponse(['error' => 'Ungültiges Token'], 403);
        }

        $id = $_POST['id'] ?? '';
        $active = $_POST['active'] === '1';
        if ($userManager->setActive($id, $active)) {
            jsonResponse(['success' => true]);
        } else {
            jsonResponse(['error' => 'Änderung fehlgeschlagen'], 500);
        }
        break;

    case 'admin/users':
        if (!$auth->isLoggedIn()) {
            header('Location: /login');
            exit;
        }
        if (!$auth->isAdmin()) {
            http_response_code(403);
            $template = '403';
        }
        break;

    case 'admin/categories':
        if (!$auth->isLoggedIn()) {
            header('Location: /login');
            exit;
        }
        # Alle eingeloggten User haben Zugriff
        break;
}

# Kategorien laden mit Foto-Anzahl
$categories = $categoryManager->getAll();
$photoManager = new Photo($config);

foreach ($categories as &$cat) {
    $cat['photo_count'] = count($photoManager->getByCategory($cat['slug']));
}
unset($cat);

# Bei Kategorie-Seite: Fotos laden
if ($template === 'category' && isset($categorySlug)) {
    $category = $categoryManager->getBySlug($categorySlug);
    if (!$category) {
        http_response_code(404);
        $template = '404';
    } else {
        $photoManager = new Photo($config);
        $photos = $photoManager->getByCategory($categorySlug);
    }
}

# User-Liste für Admin
if ($template === 'admin/users') {
    $users = $userManager->getAll();
}

# Template rendern
$templateFile = __DIR__ . '/templates/' . $template . '.php';
if (!file_exists($templateFile)) {
    $templateFile = __DIR__ . '/templates/404.php';
}

render($template, [
    'config' => $config,
    'auth' => $auth,
    'csrf_token' => $_SESSION['csrf_token'],
    'categories' => $categories ?? [],
    'category' => $category ?? null,
    'categorySlug' => $categorySlug ?? null,
    'photos' => $photos ?? [],
    'users' => $users ?? [],
    'error' => $error ?? null,
    'success' => $success ?? null,
    'token' => $token ?? $_GET['token'] ?? '',
]);
