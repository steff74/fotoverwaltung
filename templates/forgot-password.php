<p class="breadcrumb">
    <a href="/">Start</a> / <a href="/login">Login</a> / Passwort vergessen
</p>

<article class="login-form">
    <header>
        <h1>Passwort vergessen</h1>
    </header>

    <p>Gib deine E-Mail-Adresse ein und wir senden dir einen Link zum Zurücksetzen des Passworts.</p>

    <form method="post" action="/passwort-vergessen">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

        <label>
            E-Mail
            <input type="email" name="email" placeholder="name@beispiel.at" required autofocus>
        </label>

        <button type="submit">Reset-Link senden</button>
    </form>

    <p class="forgot-link">
        <a href="/login">Zurück zum Login</a>
    </p>

    <?php if (isset($_SESSION['dev_reset_token'])): ?>
        <hr>
        <p><small>Entwicklungsmodus - Reset-Link:</small></p>
        <code>/reset?token=<?= htmlspecialchars($_SESSION['dev_reset_token']) ?></code>
    <?php endif; ?>
</article>
