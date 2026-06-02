<h1>Login</h1>

<form method="POST" action="index.php?action=login">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

    <label for="email">E-Mail</label>
    <input type="email" id="email" name="email" required
           value="<?= hsc($_POST['email'] ?? '') ?>">

    <label for="password">Passwort</label>
    <input type="password" id="password" name="password" required>

    <button type="submit">Einloggen</button>
</form>

<p>Noch kein Konto? <a href="index.php?action=register">Jetzt registrieren</a></p>
