<p class="breadcrumb">
    <a href="/">Start</a> / Login
</p>

<article class="login-form">
    <header>
        <h1>Login</h1>
    </header>

    <form method="post" action="/login">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

        <label>
            E-Mail
            <input type="email" name="email" placeholder="name@beispiel.at" required autofocus>
        </label>

        <label>
            Passwort
            <input type="password" name="password" required>
        </label>

        <button type="submit">Anmelden</button>
    </form>

    <p class="forgot-link">
        <a href="/passwort-vergessen">Passwort vergessen?</a>
    </p>
</article>
