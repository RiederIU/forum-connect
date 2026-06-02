<h1>Registrieren</h1>

<form method="POST" action="index.php?action=register">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

    <label for="username">Benutzername</label>
    <input type="text" id="username" name="username" required
           value="<?= hsc($_POST['username'] ?? '') ?>">

    <label for="email">E-Mail</label>
    <input type="email" id="email" name="email" required
           value="<?= hsc($_POST['email'] ?? '') ?>">

    <label for="password">Passwort (min. <?= MIN_PASS ?> Zeichen)</label>
    <input type="password" id="password" name="password" required>

    <label for="password_confirm">Passwort bestätigen</label>
    <input type="password" id="password_confirm" name="password_confirm" required>

    <button type="submit">Registrieren</button>
</form>

<p>Bereits registriert? <a href="index.php?action=login">Zum Login</a></p>
