<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * DB-Komponententests für die statischen Methoden von User.
 * Greifen über getDB() auf die In-Memory-SQLite-DB aus bootstrap.php zu.
 */
class UserModelTest extends TestCase
{
    protected function setUp(): void
    {
        // getDB() speichert die Verbindung prozessweit zwischen (static $pdo), daher
        // überlebt die :memory:-DB jeden Test. Ohne Leeren scheitert das Anlegen eines
        // zweiten Datensatzes mit gleichem Nutzernamen an der UNIQUE-Bedingung.
        $db = getDB();
        $db->exec('DELETE FROM users');
        // Setzt den AUTOINCREMENT-Zähler zurück, damit die geprüften IDs stabil bleiben.
        $db->exec("DELETE FROM sqlite_sequence WHERE name = 'users'");
    }

    public function testCreateReturnsNewIntegerId(): void
    {
        // create() erwartet einen fertigen Passwort-Hash, keinen Klartext.
        $hash = password_hash('geheim123', PASSWORD_DEFAULT);
        $id = User::create('alice', 'alice@example.com', $hash);

        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);
    }

    public function testUpdateRoleRejectsUnknownRole(): void
    {
        // 'superadmin' ist keine erlaubte Rolle, die PHP-Prüfung lehnt ohne
        // DB-Zugriff ab.
        $this->assertFalse(User::updateRole(1, 'superadmin'));
    }

    public function testExistsByUsernameOrEmailFindsExistingUser(): void
    {
        $hash = password_hash('geheim123', PASSWORD_DEFAULT);
        User::create('bob', 'bob@example.com', $hash);

        $this->assertTrue(User::existsByUsernameOrEmail('bob', 'bob@example.com'));
    }

    public function testExistsByUsernameOrEmailReturnsFalseForNewUser(): void
    {
        $this->assertFalse(User::existsByUsernameOrEmail('carol', 'carol@example.com'));
    }

    public function testExistsByUsernameOrEmailDetectsDuplicateEmail(): void
    {
        $hash = password_hash('geheim123', PASSWORD_DEFAULT);
        User::create('dave', 'dave@example.com', $hash);

        // Anderer Nutzername, aber bereits vergebene E-Mail muss als Konflikt
        // erkannt werden.
        $this->assertTrue(User::existsByUsernameOrEmail('erin', 'dave@example.com'));
    }

    public function testFindByEmailReturnsUserIncludingHash(): void
    {
        $hash = password_hash('geheim123', PASSWORD_DEFAULT);
        User::create('frank', 'frank@example.com', $hash);

        $user = User::findByEmail('frank@example.com');
        $this->assertNotNull($user);
        $this->assertSame('frank', $user['username']);
        // findByEmail liefert den Hash bewusst mit, da der Login ihn für password_verify() braucht.
        $this->assertSame($hash, $user['password_hash']);
    }

    public function testFindByEmailReturnsNullForUnknownEmail(): void
    {
        $this->assertNull(User::findByEmail('niemand@example.com'));
    }

    public function testGetAllOmitsPasswordHash(): void
    {
        $hash = password_hash('geheim123', PASSWORD_DEFAULT);
        User::create('gina', 'gina@example.com', $hash);

        $users = User::getAll();
        $this->assertCount(1, $users);
        // Sicherheitszusage: Die Admin-Liste gibt den Passwort-Hash nicht aus.
        $this->assertArrayNotHasKey('password_hash', $users[0]);
        $this->assertArrayHasKey('role', $users[0]);
    }

    public function testGetByIdOmitsPasswordHash(): void
    {
        $hash = password_hash('geheim123', PASSWORD_DEFAULT);
        $id = User::create('hans', 'hans@example.com', $hash);

        $user = User::getById($id);
        $this->assertNotNull($user);
        $this->assertArrayNotHasKey('password_hash', $user);
        $this->assertSame('hans', $user['username']);
    }

    public function testGetByIdReturnsNullForMissingUser(): void
    {
        $this->assertNull(User::getById(999));
    }

    public function testDeleteRemovesUser(): void
    {
        $hash = password_hash('geheim123', PASSWORD_DEFAULT);
        $id = User::create('ida', 'ida@example.com', $hash);

        User::delete($id);
        $this->assertNull(User::getById($id));
    }
}
