<?php

/**
 * Zentrale Konfigurationswerte der Anwendung.
 */

define('APP_NAME','Webforum MVP');
define('PER_PAGE', 10);
define('MIN_PASS', 8);
define('MIN_TITLE', 3);
define('MIN_CONTENT', 3);

// Laufzeitumgebung aus der Container-Umgebungsvariable, damit Staging und Production
// unterscheidbar bleiben. Ohne gesetzte Variable gilt der sichere Standardwert.
define('APP_ENV', getenv('APP_ENV') ?: 'production');
