<?php

require_once __DIR__ . '/../models/Topic.php';
require_once __DIR__ . '/../models/Post.php';

/**
 * Controller für Forenthemen.
 * Die Übersicht ist öffentlich, Schreibaktionen erfordern einen Login.
 */

class TopicController
{
    // =========================================================================
    //  Themenübersicht (mit integrierter Suche und Pagination)
    // =========================================================================

    public static function index(): void
    {
        $page   = max(1, (int) ($_GET['page'] ?? 1));
        $search = trim($_GET['search'] ?? '');

        $result     = Topic::getAll($page, PER_PAGE, $search !== '' ? $search : null);
        $topics     = $result['topics'];
        $total      = $result['total'];
        $totalPages = (int) ceil($total / PER_PAGE);
        $csrfToken  = generateCsrfToken();

        require __DIR__ . '/../views/layout/header.php';
        require __DIR__ . '/../views/topics/index.php';
        require __DIR__ . '/../views/layout/footer.php';
    }

    // =========================================================================
    //  Thema erstellen (Thema und erster Beitrag in einer Transaktion)
    // =========================================================================

    public static function create(): void
    {
        requireLogin();

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $csrfToken = generateCsrfToken();
            require __DIR__ . '/../views/layout/header.php';
            require __DIR__ . '/../views/topics/create.php';
            require __DIR__ . '/../views/layout/footer.php';
            return;
        }

        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Ungültiges Formular-Token.');
            logAction('CSRF_FAIL', 'action=topics.create');
            header('Location: index.php?action=topics.create');
            exit;
        }

        $title   = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');

        $errors = [];
        if (strlen($title) < MIN_TITLE) {
            $errors[] = 'Titel muss mindestens ' . MIN_TITLE . ' Zeichen lang sein.';
        }
        if (strlen($content) < MIN_CONTENT) {
            $errors[] = 'Beitrag muss mindestens ' . MIN_CONTENT . ' Zeichen lang sein.';
        }

        if (count($errors) > 0) {
            setFlash('error', implode(' ', $errors));
            header('Location: index.php?action=topics.create');
            exit;
        }

        try {
            $topicId = Topic::createWithFirstPost($title, $content, currentUser()['id']);
        } catch (Exception $e) {
            logAction('TOPIC_CREATE_FAIL', 'error=' . $e->getMessage());
            setFlash('error', 'Fehler beim Erstellen des Themas.');
            header('Location: index.php?action=topics.index');
            exit;
        }

        logAction('TOPIC_CREATE', "topic=$topicId");
        setFlash('success', 'Thema erfolgreich erstellt.');
        header('Location: index.php?action=topics.show&id=' . $topicId);
        exit;
    }

    // =========================================================================
    //  Thread-Ansicht (Thema + paginierte Beiträge)
    // =========================================================================

    public static function show(): void
    {
        $id    = (int) ($_GET['id'] ?? 0);
        $topic = Topic::getById($id);

        if (!$topic) {
            setFlash('error', 'Thema nicht gefunden.');
            header('Location: index.php?action=topics.index');
            exit;
        }

        $page       = max(1, (int) ($_GET['page'] ?? 1));
        $result     = Post::getByTopic($id, $page, PER_PAGE);
        $posts      = $result['posts'];
        $total      = $result['total'];
        $totalPages = (int) ceil($total / PER_PAGE);
        $csrfToken  = generateCsrfToken();

        require __DIR__ . '/../views/layout/header.php';
        require __DIR__ . '/../views/topics/show.php';
        require __DIR__ . '/../views/layout/footer.php';
    }

    // =========================================================================
    //  Thema bearbeiten (Ersteller oder Moderator/Admin)
    // =========================================================================

    public static function edit(): void
    {
        requireLogin();

        $id    = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
        $topic = Topic::getById($id);

        if (!$topic) {
            setFlash('error', 'Thema nicht gefunden.');
            header('Location: index.php?action=topics.index');
            exit;
        }

        // Nur Eigentümer oder Moderator/Admin dürfen bearbeiten
        if ($topic['user_id'] !== currentUser()['id'] && !hasRole('moderator')) {
            setFlash('error', 'Keine Berechtigung für diese Aktion.');
            header('Location: index.php?action=topics.index');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $csrfToken = generateCsrfToken();
            require __DIR__ . '/../views/layout/header.php';
            require __DIR__ . '/../views/topics/edit.php';
            require __DIR__ . '/../views/layout/footer.php';
            return;
        }

        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Ungültiges Formular-Token.');
            logAction('CSRF_FAIL', 'action=topics.edit');
            header('Location: index.php?action=topics.edit&id=' . $id);
            exit;
        }

        $title = trim($_POST['title'] ?? '');

        if (strlen($title) < MIN_TITLE) {
            setFlash('error', 'Titel muss mindestens ' . MIN_TITLE . ' Zeichen lang sein.');
            header('Location: index.php?action=topics.edit&id=' . $id);
            exit;
        }

        Topic::update($id, $title);
        logAction('TOPIC_EDIT', "topic=$id");
        setFlash('success', 'Thema aktualisiert.');
        header('Location: index.php?action=topics.show&id=' . $id);
        exit;
    }

    // =========================================================================
    //  Thema löschen (Ersteller oder Admin, nur via POST)
    // =========================================================================

    public static function delete(): void
    {
        requireLogin();

        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Ungültiges Formular-Token.');
            header('Location: index.php?action=topics.index');
            exit;
        }

        // Löschen nur via POST (GET darf keine Daten verändern)
        $id    = (int) ($_POST['id'] ?? 0);
        $topic = Topic::getById($id);

        if (!$topic) {
            header('Location: index.php?action=topics.index');
            exit;
        }

        if ($topic['user_id'] !== currentUser()['id'] && !hasRole('admin')) {
            setFlash('error', 'Keine Berechtigung für diese Aktion.');
            header('Location: index.php?action=topics.index');
            exit;
        }

        Topic::delete($id);
        logAction('TOPIC_DELETE', "topic=$id");
        setFlash('success', 'Thema und alle zugehörigen Beiträge gelöscht.');
        header('Location: index.php?action=topics.index');
        exit;
    }
}
