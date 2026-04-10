<?php
/**
 * Performancetest mit 500 Themen und je 5 Beiträgen.
 * Misst Ladezeiten und bereinigt die Testdaten danach.
 *
 * Ausführung: php database/perftest.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../helpers/session.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/logging.php';
require_once __DIR__ . '/../app/models/Topic.php';

startSession();

$db = getDB();
$adminId = (int) $db->query("SELECT id FROM users WHERE role = 'admin'")->fetchColumn();

// =========================================================================
//  1. Massendaten erzeugen (in einer Transaktion für Geschwindigkeit)
// =========================================================================

// Alle Inserts in einer Transaktion, sonst ist SQLite sehr langsam.

echo "Generiere 500 Themen mit je 5 Beiträgen...\n";

$stmtTopic = $db->prepare(
    'INSERT INTO topics (title, user_id) VALUES (:title, :user_id)'
);

$stmtPost = $db->prepare(
    'INSERT INTO posts (content, user_id, topic_id) VALUES (:content, :user_id, :topic_id)'
);

$db->beginTransaction();

for ($i = 1; $i <= 500; $i++) {
    $stmtTopic->execute([
        ':title'   => "Performancetest-Thema #$i",
        ':user_id' => $adminId
    ]);

    $topicId = (int) $db->lastInsertId();

    for ($j = 1; $j <= 5; $j++) {
        $stmtPost->execute([
            ':content'  => "Testbeitrag $j zu Thema $i. Synthetischer Inhalt fuer den Performancetest.",
            ':user_id'  => $adminId,
            ':topic_id' => $topicId
        ]);
    }
}

$db->commit();

$totalTopics = (int) $db->query('SELECT COUNT(*) FROM topics')->fetchColumn();
$totalPosts  = (int) $db->query('SELECT COUNT(*) FROM posts')->fetchColumn();

echo "Datenbasis: $totalTopics Themen, $totalPosts Beiträge.\n\n";

// =========================================================================
//  2. Ladezeit messen: Paginierte Übersicht (Seite 1)
// =========================================================================

$start  = microtime(true);
$result = Topic::getAll(1, PER_PAGE);
$end    = microtime(true);
$ms1    = round(($end - $start) * 1000, 2);

echo "Topic::getAll(1, 10) ohne Suche:    {$ms1} ms  ({$result['total']} Themen)\n";

// =========================================================================
//  3. Ladezeit messen: Paginierte Übersicht mit Suche
// =========================================================================

$start  = microtime(true);
$result = Topic::getAll(1, PER_PAGE, 'Performancetest');
$end    = microtime(true);
$ms2    = round(($end - $start) * 1000, 2);

echo "Topic::getAll(1, 10) mit Suche:     {$ms2} ms  ({$result['total']} Treffer)\n";

// =========================================================================
//  4. Ladezeit messen: Letzte Seite (ungünstigster Fall für große Seitenversätze)
// =========================================================================

$lastPage = (int) ceil($result['total'] / PER_PAGE);
$start    = microtime(true);
$result   = Topic::getAll($lastPage, PER_PAGE, 'Performancetest');
$end      = microtime(true);
$ms3      = round(($end - $start) * 1000, 2);

echo "Topic::getAll($lastPage, 10) hoher OFFSET:   {$ms3} ms\n";

// =========================================================================
//  5. Bewertung
// =========================================================================

echo "\n--- Bewertung ---\n";

$threshold = 200;
$allOk = ($ms1 < $threshold && $ms2 < $threshold && $ms3 < $threshold);

if ($allOk) {
    echo "✅ Alle Abfragen unter {$threshold} ms. Pagination performant.\n";
} else {
    echo "⚠️  Mindestens eine Abfrage über {$threshold} ms.\n";
    echo "   Empfehlung: Index auf topics.title ergänzen:\n";
    echo "   CREATE INDEX idx_topics_title ON topics(title);\n";
}

// =========================================================================
//  6. Testdaten bereinigen
// =========================================================================

echo "\nBereinige Testdaten...\n";

$db->exec("DELETE FROM posts WHERE content LIKE 'Testbeitrag%'");
$db->exec("DELETE FROM topics WHERE title LIKE 'Performancetest%'");

$remaining = (int) $db->query('SELECT COUNT(*) FROM topics')->fetchColumn();
echo "Bereinigt. Verbleibende Themen: $remaining\n";
