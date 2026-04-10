<?php

/**
 * Gibt pro Seitenaufruf genau eine Datenbankverbindung zurück.
 * Bei SQLite liefert lastInsertId() nur auf derselben Verbindung korrekte Werte.
 */

define('DB_PATH', __DIR__ . '/../database/forum.sqlite');

function getDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // Ohne dieses PRAGMA ignoriert SQLite alle ON DELETE CASCADE Regeln.
        $pdo->exec('PRAGMA foreign_keys = ON');
    }

    return $pdo;
}
