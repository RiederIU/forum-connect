<?php

/**
 * Modell für Nutzer.
 * Kapselt alle Datenbankoperationen auf der users-Tabelle.
 */

class User
{
    public static function findByEmail(string $email): ?array
    {
        $db = getDB();
        $stmt = $db->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /** Prüft, ob Nutzername oder E-Mail bereits vergeben ist. */
    public static function existsByUsernameOrEmail(string $username, string $email): bool
    {
        $db = getDB();
        $stmt = $db->prepare(
            'SELECT id FROM users WHERE username = :username OR email = :email'
        );
        $stmt->execute([':username' => $username, ':email' => $email]);
        return (bool) $stmt->fetch();
    }

    public static function create(string $username, string $email, string $passwordHash): int
    {
        $db = getDB();
        $stmt = $db->prepare(
            'INSERT INTO users (username, email, password_hash)
             VALUES (:username, :email, :password_hash)'
        );
        $stmt->execute([
            ':username'      => $username,
            ':email'         => $email,
            ':password_hash' => $passwordHash
        ]);
        return (int) $db->lastInsertId();
    }

    // =========================================================================
    //  Admin-Funktionen
    // =========================================================================

    /** Gibt alle Nutzer ohne password_hash zurück. */
    public static function getAll(): array
    {
        $db = getDB();
        $stmt = $db->prepare(
            'SELECT id, username, email, role, created_at
             FROM users
             ORDER BY created_at ASC'
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function getById(int $id): ?array
    {
        $db = getDB();
        $stmt = $db->prepare(
            'SELECT id, username, email, role, created_at
             FROM users
             WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /** Ändert die Rolle. Prüft vorher in PHP, ob der Wert gültig ist. */
    public static function updateRole(int $userId, string $newRole): bool
    {
        $allowed = ['user', 'moderator', 'admin'];
        if (!in_array($newRole, $allowed, true)) {
            return false;
        }

        $db = getDB();
        $stmt = $db->prepare(
            'UPDATE users SET role = :role WHERE id = :id'
        );
        $stmt->execute([':role' => $newRole, ':id' => $userId]);
        return true;
    }

    /** Löscht einen Nutzer samt Themen und Beiträgen (CASCADE). */
    public static function delete(int $userId): void
    {
        $db = getDB();
        $stmt = $db->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute([':id' => $userId]);
    }
}
