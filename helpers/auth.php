<?php

/**
 * Hilfsfunktionen für Authentifizierung und Autorisierung.
 * Stellt eine rollenbasierte Zugriffskontrolle mit Hierarchie bereit.
 */

function isLoggedIn(): bool
{
    return isset($_SESSION['user']);
}

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

/**
 * Rollenprüfung über numerische Hierarchie (user=1, moderator=2, admin=3).
 */

function hasRole(string $requiredRole): bool
{
    $hierarchy = ['user' => 1, 'moderator' => 2, 'admin' => 3];
    $user = currentUser();

    if ($user === null) {
        return false;
    }

    return ($hierarchy[$user['role']] ?? 0) >= ($hierarchy[$requiredRole] ?? 0);
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        setFlash('error', 'Bitte zuerst einloggen.');
        header('Location: index.php?action=login');
        exit;
    }
}

function requireRole(string $role): void
{
    requireLogin();

    if (!hasRole($role)) {
        setFlash('error', 'Keine Berechtigung für diese Aktion.');
        header('Location: index.php?action=topics.index');
        exit;
    }
}
