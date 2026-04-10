<?php

require_once __DIR__ . '/../models/Post.php';
require_once __DIR__ . '/../models/Topic.php';

/**
 * Controller für Forenbeiträge.
 * Erstellen, Bearbeiten und Löschen erfordern einen Login.
 */

class PostController
{
    // =========================================================================
    //  Beitrag erstellen (aus der Thread-Ansicht heraus)
    // =========================================================================

    public static function create(): void
    {
        requireLogin();

        $topicId = (int) ($_POST['topic_id'] ?? 0);

        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Ungültiges Formular-Token.');
            logAction('CSRF_FAIL', 'action=posts.create');
            header('Location: index.php?action=topics.show&id=' . $topicId);
            exit;
        }

        $content = trim($_POST['content'] ?? '');

        if (strlen($content) < MIN_CONTENT) {
            setFlash('error', 'Beitrag muss mindestens ' . MIN_CONTENT . ' Zeichen lang sein.');
            header('Location: index.php?action=topics.show&id=' . $topicId);
            exit;
        }

        // Thema-Existenz prüfen, sonst gibt es einen kryptischen DB-Fehler
        $topic = Topic::getById($topicId);
        if (!$topic) {
            setFlash('error', 'Thema nicht gefunden.');
            header('Location: index.php?action=topics.index');
            exit;
        }

        try {
            Post::create($content, currentUser()['id'], $topicId);
            logAction('POST_CREATE', "topic=$topicId");
            setFlash('success', 'Beitrag erstellt.');
        } catch (Exception $e) {
            logAction('POST_CREATE_FAIL', "topic=$topicId error=" . $e->getMessage());
            setFlash('error', 'Fehler beim Erstellen des Beitrags.');
        }

        header('Location: index.php?action=topics.show&id=' . $topicId);
        exit;
    }

    // =========================================================================
    //  Beitrag bearbeiten (Ersteller oder Moderator/Admin)
    // =========================================================================

    public static function edit(): void
    {
        requireLogin();

        $id   = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
        $post = Post::getById($id);

        if (!$post) {
            setFlash('error', 'Beitrag nicht gefunden.');
            header('Location: index.php?action=topics.index');
            exit;
        }

        // Nur Eigentümer oder Moderator/Admin dürfen bearbeiten
        if ($post['user_id'] !== currentUser()['id'] && !hasRole('moderator')) {
            setFlash('error', 'Keine Berechtigung für diese Aktion.');
            header('Location: index.php?action=topics.show&id=' . $post['topic_id']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $csrfToken = generateCsrfToken();
            require __DIR__ . '/../views/layout/header.php';
            require __DIR__ . '/../views/posts/edit.php';
            require __DIR__ . '/../views/layout/footer.php';
            return;
        }

        // POST-Verarbeitung

        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Ungültiges Formular-Token.');
            logAction('CSRF_FAIL', 'action=posts.edit');
            header('Location: index.php?action=posts.edit&id=' . $id);
            exit;
        }

        $content = trim($_POST['content'] ?? '');

        if (strlen($content) < MIN_CONTENT) {
            setFlash('error', 'Beitrag muss mindestens ' . MIN_CONTENT . ' Zeichen lang sein.');
            header('Location: index.php?action=posts.edit&id=' . $id);
            exit;
        }

        try {
            Post::update($id, $content);
            logAction('POST_EDIT', "post=$id");
            setFlash('success', 'Beitrag aktualisiert.');
        } catch (Exception $e) {
            logAction('POST_EDIT_FAIL', "post=$id error=" . $e->getMessage());
            setFlash('error', 'Fehler beim Bearbeiten des Beitrags.');
        }

        header('Location: index.php?action=topics.show&id=' . $post['topic_id']);
        exit;
    }

    // =========================================================================
    //  Beitrag löschen (Ersteller oder Moderator/Admin, nur via POST)
    // =========================================================================

    public static function delete(): void
    {
        requireLogin();

        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Ungültiges Formular-Token.');
            header('Location: index.php?action=topics.index');
            exit;
        }

        $id   = (int) ($_POST['id'] ?? 0);
        $post = Post::getById($id);

        if (!$post) {
            header('Location: index.php?action=topics.index');
            exit;
        }

        if ($post['user_id'] !== currentUser()['id'] && !hasRole('moderator')) {
            setFlash('error', 'Keine Berechtigung für diese Aktion.');
            header('Location: index.php?action=topics.show&id=' . $post['topic_id']);
            exit;
        }

        $topicId = $post['topic_id'];

        try {
            Post::delete($id);
            logAction('POST_DELETE', "post=$id topic=$topicId");
            setFlash('success', 'Beitrag gelöscht.');
        } catch (Exception $e) {
            logAction('POST_DELETE_FAIL', "post=$id error=" . $e->getMessage());
            setFlash('error', 'Fehler beim Löschen des Beitrags.');
        }

        header('Location: index.php?action=topics.show&id=' . $topicId);
        exit;
    }
}
