<?php
require_once __DIR__ . '/../config/app.php';

if (isLoggedIn()) {
    logAudit('logout', 'user', $_SESSION['user_id']);
}

$_SESSION = [];
session_destroy();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

header('Location: /holy-trinity/index.php');
exit;
