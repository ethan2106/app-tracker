<?php
// index.php - Main entry point for app-tracker

require_once 'config.php';
require_once 'functions.php';

session_start();

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['page'])) {
    if (!verify_csrf()) {
        $_SESSION['message'] = 'Jeton CSRF invalide.';
        header('Location: ?page=list');
        exit;
    }
    $page = $_GET['page'];
    $id = $_GET['id'] ?? 0;
    if ($page === 'delete' && $id) {
        deleteApp($id);
        header('Location: ?page=list');
        exit;
    }
}

// Simple routing
$page = $_GET['page'] ?? 'list';
$id = $_GET['id'] ?? 0;

switch ($page) {
    case 'list':
        include 'templates/list_apps.php';
        break;
    case 'logs':
        include 'templates/logs.php';
        break;
    case 'add':
        include 'templates/add_app.php';
        break;
    case 'edit':
        include 'templates/edit_app.php';
        break;
    case 'check_all':
        $apps = getApps();
        $checked = 0;
        foreach ($apps as $app) {
            $result = checkForUpdates($app['id'], true); // Force check
            if (strpos($result, 'ignorée') === false) {
                $checked++;
            }
        }
        $_SESSION['message'] = "Vérifications terminées : $checked app(s) vérifiée(s) sur " . count($apps) . ".";
        header('Location: ?page=list');
        exit;
        break;
    case 'check':
        if ($id) {
            $result = checkForUpdates($id, true);
            $_SESSION['message'] = $result;
        }
        header('Location: ?page=list');
        exit;
        break;
    default:
        include 'templates/list_apps.php';
        break;
}
?>
