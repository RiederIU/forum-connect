# forum-connect

[![CI/CD](https://github.com/RiederIU/forum-connect/actions/workflows/ci-cd.yml/badge.svg)](https://github.com/RiederIU/forum-connect/actions/workflows/ci-cd.yml)

Webbasiertes Diskussionsforum (PHP) mit einer vollständigen CI/CD-Pipeline. Jede Änderung wird über GitHub Actions automatisch geprüft, getestet, als Docker-Image gebaut und nach Staging sowie nach manueller Freigabe nach Production ausgeliefert. Im Mittelpunkt dieses Repositorys steht die Automatisierung von Build, Test und Bereitstellung. Die Forenanwendung dient als realer Anwendungsfall der Pipeline.

## CI/CD-Pipeline

Definiert in `.github/workflows/ci-cd.yml`. Die Pipeline läuft bei jedem Pull Request (Stufen 1 bis 3, ohne Upload und ohne Deployment) und bei jedem Merge auf `main` (alle fünf Stufen). Jede Stufe ist ein Quality Gate für die nächste.

1. **Lint** prüft den Codestil mit PHP_CodeSniffer (PSR-12) und führt die statische Analyse mit PHPStan (Level 6) aus. Die Stufen 7 bis 9 wurden bewusst nicht gewählt, weil sie Refactorings am bereits fertig implementierten Prototyp erzwängen.
2. **Test** führt die automatisierten PHPUnit-Tests gegen eine In-Memory-SQLite-Datenbank aus und prüft die ermittelte Line-Coverage gegen eine Mindestschwelle (Coverage-Gate).
3. **Build** baut genau ein auslieferbares Docker-Image, prüft es im selben Schritt mit Trivy auf bekannte Schwachstellen (CRITICAL und HIGH, nicht blockierend, Befunde nur im Log) und lädt es erst bei einem Merge auf `main` mit dem Commit-SHA als Tag nach Docker Hub hoch.
4. **Deploy Staging** zieht das geprüfte SHA-Image, prüft es per HTTP-Smoke-Test und veröffentlicht es erst nach bestandenem Test unter dem Tag `:staging` (automatisch nach jedem Merge auf `main`).
5. **Deploy Production** zieht nach manueller Freigabe dasselbe SHA-Image, prüft es erneut per Smoke-Test und veröffentlicht es erst nach bestandenem Test unter dem Tag `:production`.

Zentrale Eigenschaften:

- **Quality Gates.** Der Build startet nur, wenn Lint und Test erfolgreich sind (`needs:`-Kette).
- **Geschützter Hauptzweig.** Direkte Pushes auf `main` sind gesperrt, Änderungen gelangen ausschließlich über Pull Requests in den Hauptzweig.
- **Manuelle Freigabe.** Das Production-Deployment erfordert die Genehmigung eines Reviewers (GitHub Environment Protection Rule).
- **Build-once und Rollback.** Es wird genau ein Image gebaut und unverändert über die Umgebungen befördert. Der unveränderliche SHA-Tag ist die Versionswahrheit, `:staging` und `:production` sind verschiebbare Tags. Ein Rollback erfolgt durch erneutes Deployment eines früheren SHA-Tags.

## Schnellstart mit Docker

Die Anwendung läuft als Container auf Basis des offiziellen `php:8.2-apache`-Images, also derselben Laufzeitumgebung, die auch die Pipeline für Staging und Production verwendet.

Voraussetzung: Docker.

```bash
docker build -t forum-connect:dev .
docker run -d --name forum-connect -p 8080:80 forum-connect:dev
```

Der Container legt die SQLite-Datenbank beim Start automatisch an (Schema und Admin-Account über `database/init.php`). Anschließend ist die Anwendung unter `http://localhost:8080/` erreichbar. Beenden und aufräumen mit `docker stop forum-connect && docker rm forum-connect`.

## Technologie-Stack

| Schicht | Technologie |
|---------|-------------|
| Backend | PHP >= 8.2 (ohne Framework) |
| Frontend | HTML5, CSS3, natives JavaScript |
| Datenbank | SQLite (Zugriff über PDO mit Prepared Statements) |
| Architektur | Model-View-Controller (MVC), Front-Controller-Routing |
| Sicherheit | bcrypt-Hashing, CSRF-Token, Output-Escaping, rollenbasierte Zugriffskontrolle |
| Abhängigkeitsverwaltung | Composer (Autoload über `files` und `classmap`, kein PSR-4) |
| Codequalität | PHP_CodeSniffer (phpcs, Standard PSR-12) und PHPStan (Level 6) |
| Automatisierte Tests | PHPUnit |
| Containerisierung | Docker (Basis-Image `php:8.2-apache`) |
| Container-Registry | Docker Hub |
| Laufzeitumgebung | Apache im Container (DocumentRoot `public/`, Port 80), lokal alternativ XAMPP |
| CI/CD | GitHub Actions, fünfstufige Pipeline (Runner `ubuntu-latest`) |
| Deployment-Strategie | Mit Commit-SHA versehene Images plus verschiebbare Tags `:staging` und `:production`, HTTP-200-Smoke-Test |
| Versionierung | Git + GitHub |

## Projektstruktur

```
forum-connect/
├── .github/
│   ├── workflows/
│   │   └── ci-cd.yml            CI/CD-Pipeline (fünf Stufen)
│   └── dependabot.yml           Automatische Abhängigkeitsaktualisierungen
├── public/
│   ├── index.php                Front-Controller
│   ├── health.php               Health-Endpoint für Smoke-Test und HEALTHCHECK
│   └── css/style.css
├── app/
│   ├── controllers/             Auth, Topic, Post, Admin
│   ├── models/                  User, Topic, Post
│   └── views/                   Layout, Auth, Topics, Posts, Admin
├── config/
│   ├── database.php             PDO-Verbindung (Singleton)
│   └── app.php                  Anwendungskonstanten
├── database/
│   ├── schema.sql               Tabellendefinitionen
│   ├── init.php                 DB-Initialisierung
│   ├── seed.php                 Testdaten
│   ├── perftest.php             Performancetest
│   └── security_audit.php       Sicherheits-Audit
├── helpers/
│   ├── session.php              Session, Flash-Messages, CSRF-Schutz
│   ├── auth.php                 Authentifizierung und Autorisierung
│   └── logging.php              Audit-Logging
├── tests/                       PHPUnit-Tests (Unit und DB-Komponente)
├── Dockerfile                   Container-Image (php:8.2-apache)
├── .dockerignore                Vom Build-Kontext ausgeschlossene Dateien
├── composer.json                Abhängigkeiten und Autoload
├── composer.lock                Festgeschriebene Abhängigkeitsversionen
├── phpstan.neon                 PHPStan-Konfiguration (Level 6)
├── .phpcs.xml                   PHP_CodeSniffer-Konfiguration (PSR-12)
├── phpunit.xml                  PHPUnit-Konfiguration
├── .gitattributes               Git-Attribute für Zeilenenden und Export
├── .gitignore                   Von der Versionierung ausgeschlossene Dateien
└── README.md
```

## Tests und Qualitätssicherung

Die Werkzeuge der Lint- und Test-Stufe lassen sich nach `composer install` auch lokal ausführen:

```bash
composer install
vendor/bin/phpcs                  # Codestil (PSR-12)
vendor/bin/phpstan analyse        # statische Analyse (Level 6)
vendor/bin/phpunit --testdox      # automatisierte Tests
```

Zusätzlich enthält die Anwendung ein Sicherheits-Audit-Skript (`database/security_audit.php`) und einen Performancetest (`database/perftest.php`).

## Die Anwendung

forum-connect ist ein Diskussionsforum als Minimum Viable Product. Funktionsumfang:

- Registrierung, Login und Logout mit bcrypt-Hashing
- CRUD-Operationen für Themen und Beiträge
- Rollenbasierte Zugriffskontrolle mit vier Stufen (Gast, User, Moderator, Admin)
- Suche über Thementitel und Beitragsinhalte mit Pagination
- Sicherheit: CSRF-Schutz, Prepared Statements, Output-Escaping gegen XSS, Session-Regeneration gegen Session-Fixation

### Test-Zugangsdaten

Die Seed-Daten (`database/init.php` und `database/seed.php`) legen folgende Konten an. Die Zugangsdaten gelten ausschließlich für die lokale Entwicklungsumgebung.

| Benutzername | E-Mail | Passwort | Rolle |
|--------------|--------|----------|-------|
| admin | admin@forum.local | `admin123` | Admin |
| moderator | mod@forum.local | `test1234` | Moderator |
| alice | alice@forum.local | `test1234` | User |
| bob | bob@forum.local | `test1234` | User |
| charlie | charlie@forum.local | `test1234` | User |

## Lokale Entwicklung mit XAMPP

Alternative ohne Docker, etwa zur Weiterentwicklung unter Windows. Voraussetzungen: XAMPP >= 8.x (Apache und PHP), PHP >= 8.2 mit aktivierter `pdo_sqlite`-Erweiterung und Git.

```bash
git clone https://github.com/RiederIU/forum-connect.git C:/xampp/htdocs/forum-connect
cd C:/xampp/htdocs/forum-connect
php database/init.php       # Datenbank und Admin-Account anlegen
php database/seed.php       # Testdaten laden
```

Falls `php` nicht im PATH liegt (Standard bei XAMPP), den vollen Pfad nutzen, etwa `C:/xampp/php/php.exe database/init.php`. Anschließend Apache in XAMPP starten und `http://localhost/forum-connect/public/` aufrufen.
