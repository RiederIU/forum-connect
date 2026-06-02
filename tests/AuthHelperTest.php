<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Unit-Tests für die globalen Authentifizierungs-Helfer aus helpers/auth.php.
 * Die Anwendung ist funktionsbasiert, daher werden isLoggedIn() und hasRole()
 * direkt aufgerufen. Keine Datenbank, der Zustand liegt allein in $_SESSION.
 */
class AuthHelperTest extends TestCase
{
    // $_SESSION ist prozessweit und wird von PHPUnit nicht zurückgesetzt,
    // sonst bleibt der "angemeldet"-Zustand im "abgemeldet"-Test erhalten.
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testIsLoggedInTrueWithSession(): void
    {
        $_SESSION['user'] = ['role' => 'user'];

        $this->assertTrue(isLoggedIn());
    }

    public function testIsLoggedInFalseWithoutSession(): void
    {
        $this->assertFalse(isLoggedIn());
    }

    public function testAdminHasAdminRole(): void
    {
        $_SESSION['user'] = ['role' => 'admin'];

        $this->assertTrue(hasRole('admin'));
    }

    public function testModeratorHasNotAdminRole(): void
    {
        $_SESSION['user'] = ['role' => 'moderator'];

        $this->assertFalse(hasRole('admin'));
    }

    public function testModeratorHasUserRole(): void
    {
        $_SESSION['user'] = ['role' => 'moderator'];

        $this->assertTrue(hasRole('user'));
    }

    public function testHasRoleFalseWithoutSession(): void
    {
        $this->assertFalse(hasRole('user'));
    }
}
