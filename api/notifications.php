<?php
require_once __DIR__ . '/../config/app.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/holy-trinity/index.php');
}

if (!isLoggedIn() || !verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'Invalid request.');
    redirect('/holy-trinity/index.php');
}

$db = Database::getInstance();
$action = sanitize($_POST['action'] ?? '');

if ($action === 'mark_all_read') {
    $userId = $_SESSION['user_id'];
    $role = $_SESSION['user_role'] ?? '';

    // Mark direct notifications as read
    $db->query("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0", [$userId]);

    // Mark role-targeted notifications as read
    if ($role) {
        $db->query("UPDATE notifications SET is_read = 1 WHERE role_target = ? AND is_read = 0", [$role]);
    }

    // Mark global notifications as read
    $db->query("UPDATE notifications SET is_read = 1 WHERE user_id IS NULL AND department_id IS NULL AND role_target IS NULL AND is_read = 0");

    setFlash('success', 'All notifications marked as read.');
} elseif ($action === 'mark_read') {
    $notifId = intval($_POST['notification_id'] ?? 0);
    if ($notifId) {
        $db->update('notifications', ['is_read' => 1], 'id = ?', [$notifId]);
    }
}

$referer = $_SERVER['HTTP_REFERER'] ?? '/holy-trinity/index.php';
redirect($referer);
