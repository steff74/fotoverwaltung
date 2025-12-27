<p class="breadcrumb">
    <a href="/">Start</a> / <a href="/login">Login</a> / Neues Passwort
</p>

<article class="login-form">
    <header>
        <h1>Neues Passwort setzen</h1>
    </header>

    <?php if (empty($error) || isset($_POST['token'])): ?>
        <form method="post" action="/reset">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

            <label>
                Neues Passwort
                <input type="password" name="password" required autofocus minlength="8"
                       placeholder="Mindestens 8 Zeichen">
            </label>

            <label>
                Passwort wiederholen
                <input type="password" name="password_confirm" required minlength="8">
            </label>

            <button type="submit">Passwort ändern</button>
        </form>
    <?php endif; ?>

    <p class="forgot-link">
        <a href="/login">Zurück zum Login</a>
    </p>
</article>
