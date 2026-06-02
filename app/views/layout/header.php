<?php

/** Escaping gegen XSS. Im Header definiert, damit alle Views sie nutzen können. */
function hsc(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= hsc(APP_NAME) ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<nav>
    <a href="index.php?action=topics.index" class="nav-brand"><?= hsc(APP_NAME) ?></a>
    <button class="nav-toggle" aria-label="Menü öffnen" aria-expanded="false">&#9776;</button>

    <div class="nav-links">
        <?php if (isLoggedIn()): ?>
            <span class="nav-user">
                Eingeloggt als <?= hsc(currentUser()['username']) ?>
                (<?= hsc(currentUser()['role']) ?>)
            </span>
            <?php if (hasRole('admin')): ?>
                <a href="index.php?action=admin.users">Admin-Bereich</a>
            <?php endif; ?>
            <a href="index.php?action=logout">Logout</a>
        <?php else: ?>
            <a href="index.php?action=login">Login</a>
            <a href="index.php?action=register">Registrieren</a>
        <?php endif; ?>
    </div>
</nav>
<script>
    document.querySelector('.nav-toggle').addEventListener('click', function () {
        var open = document.querySelector('nav').classList.toggle('nav-open');
        this.setAttribute('aria-expanded', open);
        this.setAttribute('aria-label', open ? 'Menü schließen' : 'Menü öffnen');
    });
</script>

<main>
    <?php $flash = getFlash(); ?>
    <?php if ($flash !== null): ?>
        <!-- role="alert" macht die Meldung für Screenreader sofort hörbar -->
        <div class="flash flash-<?= hsc($flash['type']) ?>" role="alert">
            <?= hsc($flash['message']) ?>
        </div>
    <?php endif; ?>
