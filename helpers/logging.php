<?php

/**
 * Schreibt Zeitstempel, Aktion, Nutzer und IP nach database/app.log.
 */

define('LOG_FILE', __DIR__ . '/../database/app.log');

function logAction(string $action, string $details = ''): void
{
    $timestamp = date('Y-m-d H:i:s');
    $user      = currentUser();
    $userId    = $user ? $user['id'] : 'guest';
    $username  = $user ? $user['username'] : 'anonymous';
    $ip        = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    $line = "[$timestamp] [$action] user=$username(id=$userId) ip=$ip $details";
    file_put_contents(LOG_FILE, $line . PHP_EOL, FILE_APPEND);
}
