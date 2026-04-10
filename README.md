# Webforum MVP

Webbasiertes Diskussionsforum als Minimum Viable Product (MVP), entwickelt im Rahmen des Moduls
DLBITPEWP01-01 (Einstieg in die Web-Programmierung) an der IU Internationalen Hochschule.

## Voraussetzungen

- XAMPP >= 8.x (Apache + PHP)
- PHP >= 8.0 mit `pdo_sqlite` und `sqlite3`
- Git
- Aktueller Browser (Chrome, Firefox, Edge oder Safari)

## Installation

Standard-Setup: XAMPP unter `C:/xampp/`. Bei abweichendem XAMPP-Pfad alle folgenden Pfade entsprechend anpassen.

```bash
git clone https://github.com/RiederIU/forum-mvp.git C:/xampp/htdocs/forum
cd C:/xampp/htdocs/forum
php database/init.php       # Datenbank und Admin-Account anlegen
php database/seed.php       # Testdaten laden (5 Nutzende, 8 Themen, 48 Beiträge)
```

Falls der Befehl `php` nicht gefunden wird (Standard-XAMPP fügt PHP nicht zum PATH hinzu), den vollen Pfad nutzen: `C:/xampp/php/php.exe database/init.php` (analog für `seed.php`).

Anschließend Apache in XAMPP starten und `http://localhost/forum/public/` aufrufen.

## Test-Zugangsdaten

| Benutzername | E-Mail              | Passwort  | Rolle     |
|--------------|---------------------|-----------|-----------|
| admin        | admin@forum.local   | admin123  | Admin     |
| moderator    | mod@forum.local     | test1234  | Moderator |
| alice        | alice@forum.local   | test1234  | User      |
| bob          | bob@forum.local     | test1234  | User      |
| charlie      | charlie@forum.local | test1234  | User      |

Die Zugangsdaten gelten ausschließlich für die lokale Entwicklungsumgebung.

## Projektstruktur

```
forum/
├── public/
│   ├── index.php                Front-Controller
│   └── css/style.css
├── app/
│   ├── controllers/             Auth, Topic, Post, Admin
│   ├── models/                  User, Topic, Post
│   └── views/                   Layout, Auth, Topics, Posts, Admin
├── config/
│   ├── database.php             PHP Data Objects (PDO), Singleton
│   └── app.php                  Anwendungskonstanten
├── database/
│   ├── schema.sql               Tabellendefinitionen
│   ├── init.php                 DB-Initialisierung
│   ├── seed.php                 Testdaten
│   ├── perftest.php             Performancetest
│   └── security_audit.php       Sicherheitsaudit (39 Prüfungen)
├── helpers/
│   ├── session.php              Session, Flash-Messages, CSRF-Schutz
│   ├── auth.php                 Authentifizierung und Autorisierung
│   └── logging.php              Audit-Logging
├── .gitignore
└── README.md
```

## Technologie-Stack

| Schicht     | Technologie       |
|-------------|-------------------|
| Backend     | PHP 8.x (ohne Framework) |
| Frontend    | HTML5, CSS3, natives JavaScript |
| Datenbank   | SQLite 3          |
| Architektur | Model-View-Controller (MVC) |
| Webserver   | Apache via XAMPP   |
| Versionierung | Git + GitHub |

## Funktionsumfang

**Pflichtfunktionen** (Aufgabenstellung, vgl. Projektbericht Kap. 2.2):

- Registrierung, Login und Logout mit bcrypt-Hashing
- Vollständige CRUD-Operationen (Create, Read, Update, Delete) für Themen und Beiträge

**Weitere Teilziele** (vgl. Projektbericht Kap. 1.3):

- Rollenbasierte Zugriffskontrolle (RBAC) mit vier Stufen (Gast, User, Moderator, Admin)
- Volltextsuche über Thementitel und Beitragsinhalte mit Pagination

**Sicherheitsmaßnahmen** (vgl. Projektbericht Kap. 2.3): Cross-Site Request Forgery-Schutz (CSRF), Prepared Statements, Output-Encoding gegen Cross-Site Scripting (XSS), Session-Regeneration gegen Session-Fixation.

**Erweiterungen** (Admin-Bereich mit Selbstschutz-Mechanismus, Bump-Semantik, Flash-Messages, Barrierefreiheitsmaßnahmen, responsives Mobile-first-Layout, JavaScript-Bestätigungsdialoge, strukturiertes Logging) — Details siehe Projektbericht Kap. 2.2.

## Tests ausführen

```bash
php database/security_audit.php    # 39 automatisierte Sicherheitsprüfungen
php database/perftest.php          # Performancetest mit 500 Themen und 2.500 Beiträgen
```
