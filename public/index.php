<?php

/**
 * Zentraler Einstiegspunkt der Anwendung.
 * Initialisiert Session, Datenbankverbindung und Hilfsfunktionen.
 * Welche Seite gezeigt wird, bestimmt der URL-Parameter ?action.
 */

require_once __DIR__ . '/../helpers/session.php';
startSession();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/logging.php';

$action = $_GET['action'] ?? 'topics.index';

switch ($action) {

    // Authentifizierung
    case 'register':
        require_once __DIR__ . '/../app/controllers/AuthController.php';
        AuthController::register();
        break;

    case 'login':
        require_once __DIR__ . '/../app/controllers/AuthController.php';
        AuthController::login();
        break;

    case 'logout':
        require_once __DIR__ . '/../app/controllers/AuthController.php';
        AuthController::logout();
        break;

    // --- Topics (CRUD) ---
    case 'topics.index':
        require_once __DIR__ . '/../app/controllers/TopicController.php';
        TopicController::index();
        break;

    case 'topics.create':
        require_once __DIR__ . '/../app/controllers/TopicController.php';
        TopicController::create();
        break;

    case 'topics.show':
        require_once __DIR__ . '/../app/controllers/TopicController.php';
        TopicController::show();
        break;

    case 'topics.edit':
        require_once __DIR__ . '/../app/controllers/TopicController.php';
        TopicController::edit();
        break;

    case 'topics.delete':
        require_once __DIR__ . '/../app/controllers/TopicController.php';
        TopicController::delete();
        break;

    // Posts werden in topics.show angezeigt, daher kein eigener Index.
    case 'posts.create':
        require_once __DIR__ . '/../app/controllers/PostController.php';
        PostController::create();
        break;

    case 'posts.edit':
        require_once __DIR__ . '/../app/controllers/PostController.php';
        PostController::edit();
        break;

    case 'posts.delete':
        require_once __DIR__ . '/../app/controllers/PostController.php';
        PostController::delete();
        break;

    // --- Administration ---
    case 'admin.users':
        require_once __DIR__ . '/../app/controllers/AdminController.php';
        AdminController::users();
        break;

    case 'admin.updateRole':
        require_once __DIR__ . '/../app/controllers/AdminController.php';
        AdminController::updateRole();
        break;

    case 'admin.deleteUser':
        require_once __DIR__ . '/../app/controllers/AdminController.php';
        AdminController::deleteUser();
        break;

    default:
        http_response_code(404);
        echo '404 - Seite nicht gefunden';
        break;
}