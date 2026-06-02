<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Unit-Tests für den CSRF-Schutz aus helpers/session.php.
 *
 * Die Helfer sind globale Funktionen (keine Klasse), der Token-Speicher
 * ist die $_SESSION-Superglobale. Es wird kein session_start() benötigt,
 * da $_SESSION auch im CLI verfügbar ist.
 */
final class CsrfHelperTest extends TestCase
{
    protected function setUp(): void
    {
        // $_SESSION ist prozessweit. Ohne Zurücksetzen bleibt ein bereits
        // etabliertes Token zwischen den Testfällen erhalten.
        $_SESSION = [];
    }

    public function testGenerateCsrfTokenLiefert64HexZeichenUndIstIdempotent(): void
    {
        $token = generateCsrfToken();

        // bin2hex(random_bytes(32)) ergibt 64 Hex-Zeichen. Per Regex statt
        // fester Länge geprüft, damit auch das Zeichenformat abgedeckt ist.
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);

        // Ein zweiter Aufruf darf kein neues Token erzeugen.
        $second = generateCsrfToken();
        $this->assertSame($token, $second);
    }

    public function testValidateCsrfTokenAkzeptiertKorrektesToken(): void
    {
        $token = generateCsrfToken();

        $this->assertTrue(validateCsrfToken($token));
    }

    public function testValidateCsrfTokenLehntFalschesTokenAb(): void
    {
        // Erst ein gültiges Token etablieren, damit der hash_equals()-Vergleich
        // greift und nicht der vorgelagerte isset()-Kurzschluss.
        generateCsrfToken();

        $this->assertFalse(validateCsrfToken('falsches-token'));
    }
}
