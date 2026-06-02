<?php

/**
 * Erstellt Tabellen und Admin-Nutzer, falls noch nicht vorhanden.
 * Ausführung: php database/init.php
 */

require_once __DIR__ . '/../config/database.php';

$db = getDB();

$schemaSql = file_get_contents(__DIR__ . '/schema.sql');
$db->exec($schemaSql);

$stmt = $db->prepare('SELECT COUNT(*) FROM users WHERE role = :role');
$stmt->execute([':role' => 'admin']);

if ((int) $stmt->fetchColumn() === 0) {
    // Passwort aus der Umgebung, kein Klartext-Literal im Quellcode. Ohne gesetzte
    // Variable gilt der dokumentierte Entwicklungs-Standard. PASSWORD_DEFAULT nutzt bcrypt.
    $adminPassword = getenv('SEED_ADMIN_PASSWORD') ?: 'changeme';
    $hash = password_hash($adminPassword, PASSWORD_DEFAULT);

    $db->prepare(
        'INSERT INTO users (username, email, password_hash, role)
         VALUES (:username, :email, :password_hash, :role)'
    )->execute([
        ':username'      => 'admin',
        ':email'         => 'admin@forum.local',
        ':password_hash' => $hash,
        ':role'          => 'admin'
    ]);

    echo "Admin-Nutzer angelegt (admin@forum.local, Passwort aus SEED_ADMIN_PASSWORD)\n";
}

echo "Datenbank initialisiert.\n";
