-- =============================================================================
-- Datenbankschema des Forum-MVP mit drei Tabellen.
-- AUTOINCREMENT stellt sicher, dass URLs und Verweise nach dem Löschen
-- eines Datensatzes gültig bleiben.
--
-- CHECK(role) validiert die Rolle auf DB-Ebene als zweite Sicherheitsschicht
-- neben der PHP-Prüfung in User::updateRole().
--
-- ON DELETE CASCADE verhindert verwaiste Beiträge und Themen beim Löschen
-- eines Nutzers, ohne dass die Anwendungslogik jeden Datensatz einzeln
-- entfernen muss.
-- =============================================================================

CREATE TABLE IF NOT EXISTS users (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    username        TEXT    UNIQUE NOT NULL,
    email           TEXT    UNIQUE NOT NULL,
    password_hash   TEXT    NOT NULL,
    role            TEXT    DEFAULT 'user'
                    CHECK(role IN ('user', 'moderator', 'admin')),
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS topics (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    title           TEXT    NOT NULL,
    user_id         INTEGER NOT NULL,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS posts (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    content         TEXT    NOT NULL,
    user_id         INTEGER NOT NULL,
    topic_id        INTEGER NOT NULL,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE,
    FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE CASCADE
);
