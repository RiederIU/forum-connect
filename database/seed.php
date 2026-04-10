<?php
/**
 * Legt Testnutzer, Themen und Beiträge für alle Rollen an.
 * Bestehende Daten werden vorher gelöscht, damit das Skript mehrfach ausführbar ist.
 *
 * Ausführung: php database/seed.php
 * Alle Testnutzer verwenden das Passwort 'test1234'.
 */

require_once __DIR__ . '/../config/database.php';

$db = getDB();

// =========================================================================
//  1. Bestehende Daten entfernen (Reihenfolge beachtet Fremdschlüssel)
// =========================================================================

$db->exec('DELETE FROM posts');
$db->exec('DELETE FROM topics');
$db->exec("DELETE FROM users WHERE username != 'admin'");

echo "Bestehende Daten bereinigt.\n";

// =========================================================================
//  2. Testnutzer anlegen
// =========================================================================

$testUsers = [
    ['moderator', 'mod@forum.local',     'moderator'],
    ['alice',     'alice@forum.local',    'user'],
    ['bob',       'bob@forum.local',      'user'],
    ['charlie',   'charlie@forum.local',  'user'],
];

$stmtUser = $db->prepare(
    'INSERT INTO users (username, email, password_hash, role)
     VALUES (:username, :email, :password_hash, :role)'
);

foreach ($testUsers as [$username, $email, $role]) {
    $stmtUser->execute([
        ':username'      => $username,
        ':email'         => $email,
        ':password_hash' => password_hash('test1234', PASSWORD_DEFAULT),
        ':role'          => $role
    ]);
}

echo "4 Testnutzer angelegt.\n";

// =========================================================================
//  3. Alle User-IDs sammeln (inkl. Admin)
// =========================================================================

$userIds = $db->query('SELECT id FROM users ORDER BY id ASC')->fetchAll(PDO::FETCH_COLUMN);

// =========================================================================
//  4. Themen erstellen (8 Stück, verteilt auf alle Nutzer)
// =========================================================================

$topicTitles = [
    'Willkommen im Forum: Vorstellungsrunde',
    'PHP vs. Java: Welche Sprache für Webprojekte?',
    'Beste Lernressourcen für Einsteiger',
    'SQLite im Produktiveinsatz: Erfahrungen?',
    'MVC-Pattern verständlich erklärt',
    'CSS-Tipps für saubere Layouts',
    'Sicherheit im Web: Best Practices',
    'Projektideen für das nächste Semester',
];

$stmtTopic = $db->prepare(
    'INSERT INTO topics (title, user_id) VALUES (:title, :user_id)'
);

foreach ($topicTitles as $i => $title) {
    $stmtTopic->execute([
        ':title'   => $title,
        ':user_id' => $userIds[$i % count($userIds)]
    ]);
}

echo "8 Themen erstellt.\n";

// =========================================================================
//  5. Beiträge generieren (5 bis 7 pro Thema, passend zum jeweiligen Thema)
// =========================================================================

// Jedes Thema hat passende Beiträge, damit die Volltextsuche sinnvolle Treffer liefert.

$topicPosts = [
    // Willkommen im Forum: Vorstellungsrunde
    [
        'Hallo zusammen! Ich freue mich, dieses Forum zu eröffnen. Stellt euch gerne vor — Name, Studiengang und was euch zu diesem Forum gebracht hat.',
        'Hi! Ich bin Alice, studiere Wirtschaftsinformatik im zweiten Semester. Ich freue mich auf den Austausch hier.',
        'Moin, ich bin Bob. Ich programmiere seit einem Jahr und freue mich auf spannende Diskussionen.',
        'Hallo! Ich bin Charlie und noch ziemlich neu in der Webentwicklung. Hoffe, hier viel lernen zu können.',
        'Schön, dass so viele mitmachen! Auf gute Zusammenarbeit.',
        'Klasse Initiative! Genau so ein Forum hat noch gefehlt.',
    ],
    // PHP vs. Java: Welche Sprache für Webprojekte?
    [
        'PHP ist für schnelle Webentwicklung unschlagbar. Es läuft nativ auf fast jedem Webserver und braucht kaum Konfiguration.',
        'Java mit Spring Boot ist mächtiger, aber der Einstieg ist deutlich steiler. Für große Projekte lohnt sich der Aufwand.',
        'Für Einsteiger würde ich immer PHP empfehlen. Die Lernkurve ist flacher und man sieht schnell erste Ergebnisse.',
        'Es hängt vom Einsatzbereich ab. Für Enterprise-Anwendungen würde ich Java nehmen, für kleinere Projekte PHP.',
        'Ich habe beide gelernt — PHP ist schneller zu starten, Java ist strukturierter. Beides hat seine Berechtigung.',
        'Für unser Kurs-Projekt war PHP die richtige Wahl: weniger Setup-Aufwand, direkt einsatzbereit.',
        'Java erzwingt durch das Typsystem saubereren Code. Das hilft langfristig, kostet aber am Anfang mehr Zeit.',
    ],
    // Beste Lernressourcen für Einsteiger
    [
        'Die offizielle PHP-Dokumentation auf php.net ist erstaunlich gut. Kombiniert mit einem Tutorial kommt man schnell voran.',
        'Ich habe mit YouTube-Tutorials angefangen. Konkrete Beispiele helfen mir mehr als reine Textdokumentation.',
        'W3Schools ist gut für den schnellen Einstieg, aber danach sollte man direkt zur offiziellen Dokumentation wechseln.',
        'Am meisten habe ich durch eigene kleine Projekte gelernt. Theorie und Praxis zusammen bringt die schnellsten Fortschritte.',
        'Stack Overflow ist unverzichtbar. Fast jedes Problem wurde dort schon einmal gestellt und beantwortet.',
    ],
    // SQLite im Produktiveinsatz: Erfahrungen?
    [
        'SQLite ist super für kleine bis mittlere Anwendungen. Kein separater Server, einfaches Backup — einfach die Datei kopieren.',
        'Für ein MVP ist SQLite ideal. Wenn die Nutzerzahlen steigen, kann man später auf MySQL migrieren.',
        'Die Grenze liegt bei gleichzeitigen Schreibzugriffen. Bei vielen parallelen Requests kann es zu Sperrkonflikten kommen.',
        'Ich nutze SQLite für alle Lernprojekte. Für mehr als 100 gleichzeitige Nutzer würde ich aber zu MySQL wechseln.',
        'Das Schöne an SQLite: Die gesamte Datenbank ist eine einzige Datei. Das macht den Transport und das Testen sehr einfach.',
        'Wichtig zu wissen: SQLite unterstützt kein echtes paralleles Schreiben. Für leseintensive Anwendungen ist das aber kein Problem.',
    ],
    // MVC-Pattern verständlich erklärt
    [
        'MVC steht für Model-View-Controller. Das Model enthält die Datenbanklogik, die View ist das HTML-Template und der Controller verbindet beides.',
        'Das Wichtigste ist die Trennung: SQL-Abfragen gehören ins Model, niemals in die View. So bleibt der Code wartbar.',
        'Gutes Beispiel: Der Controller prüft die Berechtigung und ruft dann die passende Model-Methode auf. Die View zeigt nur das Ergebnis an.',
        'MVC macht den Code testbar — man kann das Model unabhängig von der View testen. Das zahlt sich bei wachsenden Projekten aus.',
        'Meine Faustregel: Wenn Code SQL enthält, gehört er ins Model. Wenn Code HTML ausgibt, gehört er in die View.',
        'Ich hatte anfangs Probleme, Controller und Model auseinanderzuhalten. Seitdem ich die Faustregel kenne, ist es viel klarer.',
        'Laravel und Symfony setzen beide auf MVC. Wer das Pattern einmal verstanden hat, findet sich in jedem Framework schnell zurecht.',
    ],
    // CSS-Tipps für saubere Layouts
    [
        'Flexbox hat meine CSS-Arbeit deutlich vereinfacht. Für die meisten Layouts reicht display: flex mit gap und justify-content vollkommen.',
        'box-sizing: border-box als globaler Reset erspart viele Kopfschmerzen beim Berechnen von Breiten und Padding.',
        'Mobile-first ist der richtige Ansatz: erst das Layout für kleine Bildschirme definieren, dann mit Media Queries für größere erweitern.',
        'CSS-Variablen wie --primary-color: #2c3e50 machen es einfach, Farben konsistent zu halten und das Design schnell anzupassen.',
        'Für komplexe Grid-Layouts lohnt sich CSS Grid. Für einfache Zeilen- und Spaltenaufteilungen reicht Flexbox.',
    ],
    // Sicherheit im Web: Best Practices
    [
        'Die drei wichtigsten Maßnahmen: Prepared Statements gegen SQL-Injection, htmlspecialchars() gegen XSS und CSRF-Token in jedem Formular.',
        'Passwörter niemals im Klartext speichern. PHP bietet password_hash() mit bcrypt — einfach zu nutzen und sehr sicher.',
        'Session-Fixation wird oft unterschätzt. Nach dem Login immer session_regenerate_id(true) aufrufen, damit die alte Session ungültig wird.',
        'HTTPS ist Pflicht, auch für Entwicklungsumgebungen. Cookies sollten immer mit HttpOnly und Secure gesetzt werden.',
        'Der häufigste Fehler: Nutzereingaben vertrauen ohne Validierung. Alles was vom Nutzer kommt, muss als potenziell gefährlich behandelt werden.',
        'Bei Fehlermeldungen beim Login immer dieselbe Meldung ausgeben, egal ob E-Mail oder Passwort falsch ist. Sonst kann man gültige E-Mails erraten.',
    ],
    // Projektideen für das nächste Semester
    [
        'Eine Todo-App mit Nutzerverwaltung ist ein klassisches Lernprojekt — überschaubar, aber man lernt alle wichtigen Konzepte.',
        'Ich plane einen Link-Shortener. Datenbank, Routing und Redirect — gute Übung für Einsteiger.',
        'Eine Rezeptverwaltung wäre interessant: Rezepte anlegen, kategorisieren und nach Zutaten suchen. Gute Übung für CRUD und Suche.',
        'Wie wäre es mit einem Buchungskalender? Termine eintragen, anzeigen und löschen — direkt praxisrelevant.',
        'Ich möchte ein Quiz-Tool bauen. Fragen und Antworten in der Datenbank, Auswertung am Ende. Klingt einfach, hat aber viele Teilprobleme.',
        'Ein einfaches Haushaltsbuch wäre auch eine gute Idee: Einnahmen und Ausgaben erfassen, Summen berechnen, Kategorien filtern.',
    ],
];

$topicIds = $db->query('SELECT id FROM topics ORDER BY id ASC')->fetchAll(PDO::FETCH_COLUMN);

$stmtPost = $db->prepare(
    'INSERT INTO posts (content, user_id, topic_id)
     VALUES (:content, :user_id, :topic_id)'
);

$totalPosts = 0;

foreach ($topicIds as $i => $topicId) {
    $posts = $topicPosts[$i] ?? [];
    foreach ($posts as $content) {
        $stmtPost->execute([
            ':content'  => $content,
            ':user_id'  => $userIds[array_rand($userIds)],
            ':topic_id' => $topicId
        ]);
        $totalPosts++;
    }
}

echo "$totalPosts Beiträge generiert.\n";

// =========================================================================
//  Zusammenfassung
// =========================================================================

echo "\n--- Seed abgeschlossen ---\n";
echo "Nutzer:  " . count($db->query('SELECT id FROM users')->fetchAll()) . "\n";
echo "Themen:  " . count($topicIds) . "\n";
echo "Posts:   $totalPosts\n";

echo "\nZugangsdaten:\n";
echo "  admin     / admin@forum.local     / admin123   (Admin)\n";
echo "  moderator / mod@forum.local       / test1234   (Moderator)\n";
echo "  alice     / alice@forum.local     / test1234   (User)\n";
echo "  bob       / bob@forum.local       / test1234   (User)\n";
echo "  charlie   / charlie@forum.local   / test1234   (User)\n";
