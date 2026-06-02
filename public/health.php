<?php

/**
 * Bereitstellungsprüfung für den Smoke-Test der Pipeline.
 * Prüft in einer Anfrage die drei Laufzeitvoraussetzungen des Containers:
 * Datenbankverbindung, vorhandenes Schema (Lesezugriff auf users) und die
 * Schreibbarkeit der SQLite-Datei. Die Schreibprüfung deckt den
 * readonly-database-Fall ab, den der reine Aufruf der Startseite nicht
 * zuverlässig sichtbar macht. Antwortet maschinenlesbar mit HTTP 200 und
 * status ok oder mit HTTP 500.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

$db = null;

try {
    $db = getDB();

    // Lese- und Schemaprüfung: schlägt fehl, falls die Tabelle users fehlt.
    $db->query('SELECT COUNT(*) FROM users')->fetchColumn();

    // Schreibprüfung gegen die SQLite-Datei. Die Tabelle wird in einer
    // Transaktion angelegt und sofort zurückgerollt, es bleibt keine Spur.
    // Auf einer schreibgeschützten Datei schlägt bereits das CREATE fehl.
    $db->beginTransaction();
    $db->exec('CREATE TABLE _health_write_probe (pruef INTEGER)');
    $db->exec('INSERT INTO _health_write_probe (pruef) VALUES (1)');
    $db->rollBack();

    http_response_code(200);
    echo json_encode(['status' => 'ok']);
} catch (Throwable $e) {
    // Eine offene Transaktion zurücknehmen, keinen Stacktrace nach außen geben.
    if ($db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['status' => 'error']);
}
