<!DOCTYPE html>
<html lang="de" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($config['site']['title']) ?></title>

    <!-- Pico CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">

    <!-- Eigene Styles -->
    <link rel="stylesheet" href="/css/app.css?v=<?= filemtime(__DIR__ . '/../css/app.css') ?>">

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- CSRF Token für JS -->
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf_token) ?>">
</head>
<body>
    <header class="container site-header">
        <h1 class="site-title"><a href="/"><?= htmlspecialchars($config['site']['title']) ?></a></h1>
        <nav class="main-nav">
            <?php if ($auth->isLoggedIn()): ?>
                <a href="/upload">Hochladen</a>
                <a href="/admin/categories">Kategorien</a>
                <?php if ($auth->isAdmin()): ?>
                    <a href="/admin/users">User</a>
                <?php endif; ?>
                <a href="/logout">Logout</a>
            <?php else: ?>
                <a href="/login">Login</a>
            <?php endif; ?>
        </nav>
    </header>

    <main class="container">
        <?php if (!empty($error)): ?>
            <p role="alert" class="error-message"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <p role="alert" class="success-message"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>

        <?php
        $contentFile = __DIR__ . '/' . $template . '.php';
        if (file_exists($contentFile)) {
            require $contentFile;
        }
        ?>
    </main>

    <footer class="container">
        <hr>
        <small><?= htmlspecialchars($config['site']['footer']) ?></small>
    </footer>

    <!-- Eigene Scripts -->
    <script src="/js/app.js?v=<?= filemtime(__DIR__ . '/../js/app.js') ?>"></script>
</body>
</html>
