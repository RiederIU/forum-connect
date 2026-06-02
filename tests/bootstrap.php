<?php

declare(strict_types=1);

// Schutz vor einem Lauf gegen die echte Datenbank: DB_PATH kommt aus der
// <env>-Variable der phpunit.xml. Fehlt sie (etwa Aufruf ohne --configuration),
// würden die DELETE-FROM-Anweisungen der Tests die produktive forum.sqlite leeren.
if (getenv('DB_PATH') !== ':memory:') {
    throw new RuntimeException('Tests nur gegen :memory:');
}

require __DIR__ . '/../vendor/autoload.php';

// Die :memory:-Datenbank startet ohne Tabellen, daher das Schema einmalig einspielen.
getDB()->exec(file_get_contents(__DIR__ . '/../database/schema.sql'));
