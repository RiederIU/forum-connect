<?php

require_once __DIR__ . '/../models/User.php';

/**
 * Controller für den Admin-Bereich.
 * Nur Nutzer mit der Rolle 'admin' haben Zugriff.
 */

class AdminController
{
    // =========================================================================
    //  Nutzerverwaltung (Übersicht)
    // =========================================================================

    public static function users(): void
    {
        requireRole('admin');

        $users     = User::getAll();
        $csrfToken = generateCsrfToken();

        require __DIR__ . '/../views/layout/header.php';
        require __DIR__ . '/../views/admin/users.php';
        require __DIR__ . '/../views/layout/footer.php';
    }

    // =========================================================================
    //  Rolle ändern (mit Selbstschutz)
    // =========================================================================

    public static function updateRole(): void
    {
        requireRole('admin');

        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Ungültiges Formular-Token.');
            logAction('CSRF_FAIL', 'action=admin.updateRole');
            header('Location: index.php?action=admin.users');
            exit;
        }

        $userId  = (int) ($_POST['user_id'] ?? 0);
        $newRole = $_POST['role'] ?? '';

        // Eigene Rolle nicht änderbar, sonst sperrt man sich selbst aus
        if ($userId === (int) currentUser()['id']) {
            setFlash('error', 'Die eigene Rolle kann nicht geändert werden.');
            header('Location: index.php?action=admin.users');
            exit;
        }

        $targetUser = User::getById($userId);
        if (!$targetUser) {
            setFlash('error', 'Nutzer nicht gefunden.');
            header('Location: index.php?action=admin.users');
            exit;
        }

        if (!User::updateRole($userId, $newRole)) {
            setFlash('error', 'Ungültige Rolle.');
            header('Location: index.php?action=admin.users');
            exit;
        }

        logAction('ROLE_CHANGE', "target=$userId ({$targetUser['username']}) new_role=$newRole");
        setFlash('success', 'Rolle von „' . $targetUser['username'] . '" auf „' . $newRole . '" geändert.');
        header('Location: index.php?action=admin.users');
        exit;
    }

    // =========================================================================
    //  Nutzer löschen (inkl. Selbstschutz, Themen und Beiträge werden mitgelöscht)
    // =========================================================================

    public static function deleteUser(): void
    {
        requireRole('admin');

        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Ungültiges Formular-Token.');
            logAction('CSRF_FAIL', 'action=admin.deleteUser');
            header('Location: index.php?action=admin.users');
            exit;
        }

        $userId = (int) ($_POST['user_id'] ?? 0);

        if ($userId === (int) currentUser()['id']) {
            setFlash('error', 'Das eigene Konto kann nicht gelöscht werden.');
            header('Location: index.php?action=admin.users');
            exit;
        }

        $targetUser = User::getById($userId);
        if (!$targetUser) {
            setFlash('error', 'Nutzer nicht gefunden.');
            header('Location: index.php?action=admin.users');
            exit;
        }

        User::delete($userId);
        logAction('USER_DELETE', "target=$userId ({$targetUser['username']})");
        setFlash('success', 'Nutzer „' . $targetUser['username'] . '" und alle zugehörigen Inhalte gelöscht.');
        header('Location: index.php?action=admin.users');
        exit;
    }
}
