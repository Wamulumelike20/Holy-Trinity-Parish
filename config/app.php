<?php
/**
 * Holy Trinity Parish - Application Configuration
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Application constants
define('APP_NAME', 'Holy Trinity Parish');
define('APP_TAGLINE', 'A Community of Faith, Hope & Love');
define('APP_URL', 'http://localhost/holy-trinity');
define('APP_ROOT', dirname(__DIR__));
define('UPLOAD_DIR', APP_ROOT . '/uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB

// Include database
require_once APP_ROOT . '/config/database.php';

// CSRF Token
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Flash messages
function setFlash($type, $message) {
    $_SESSION['flash'][$type] = $message;
}

function getFlash($type) {
    if (isset($_SESSION['flash'][$type])) {
        $message = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        return $message;
    }
    return null;
}

// Sanitization
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function sanitizeArray($data) {
    return array_map('sanitize', $data);
}

// Authentication helpers
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        setFlash('error', 'Please log in to access this page.');
        header('Location: /holy-trinity/auth/login.php');
        exit;
    }
}

function requireRole($roles) {
    requireLogin();
    if (!is_array($roles)) $roles = [$roles];
    if (!in_array($_SESSION['user_role'], $roles)) {
        setFlash('error', 'You do not have permission to access this page.');
        header('Location: /holy-trinity/index.php');
        exit;
    }
}

function currentUser() {
    if (!isLoggedIn()) return null;
    $db = Database::getInstance();
    return $db->fetch("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
}

function isAdmin() {
    return isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'super_admin']);
}

// Generate reference numbers
function generateReference($prefix = 'HTP') {
    return $prefix . '-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
}

// Date formatting
function formatDate($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

function formatTime($time) {
    return date('g:i A', strtotime($time));
}

// Audit logging
function logAudit($action, $entityType = null, $entityId = null, $oldValues = null, $newValues = null) {
    $db = Database::getInstance();
    $db->insert('audit_logs', [
        'user_id' => $_SESSION['user_id'] ?? null,
        'action' => $action,
        'entity_type' => $entityType,
        'entity_id' => $entityId,
        'old_values' => $oldValues ? json_encode($oldValues) : null,
        'new_values' => $newValues ? json_encode($newValues) : null,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
    ]);
}

// Redirect helper
function redirect($url) {
    header("Location: {$url}");
    exit;
}

// JSON response helper
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Notification helpers
function sendNotification($title, $message, $type = 'info', $link = null, $userId = null, $departmentId = null, $roleTarget = null) {
    $db = Database::getInstance();
    $db->insert('notifications', [
        'user_id' => $userId,
        'department_id' => $departmentId,
        'role_target' => $roleTarget,
        'title' => $title,
        'message' => $message,
        'type' => $type,
        'link' => $link,
        'created_by' => $_SESSION['user_id'] ?? null,
    ]);
}

function getNotifications($limit = 20) {
    if (!isLoggedIn()) return [];
    $db = Database::getInstance();
    $userId = $_SESSION['user_id'];
    $role = $_SESSION['user_role'] ?? '';

    // Get user's department IDs
    $deptIds = $db->fetchAll("SELECT department_id FROM department_members WHERE user_id = ?", [$userId]);
    $deptIdList = array_column($deptIds, 'department_id');

    // Also get departments where user is head
    $headDepts = $db->fetchAll("SELECT id FROM departments WHERE head_user_id = ?", [$userId]);
    $deptIdList = array_merge($deptIdList, array_column($headDepts, 'id'));
    $deptIdList = array_unique($deptIdList);

    $where = "(n.user_id = ?";
    $params = [$userId];

    // Role-based notifications
    if ($role) {
        $where .= " OR n.role_target = ?";
        $params[] = $role;
    }

    // Priest sees all department notifications
    if (in_array($role, ['priest', 'super_admin'])) {
        $where .= " OR n.department_id IS NOT NULL";
    } elseif (!empty($deptIdList)) {
        $placeholders = implode(',', array_fill(0, count($deptIdList), '?'));
        $where .= " OR n.department_id IN ({$placeholders})";
        $params = array_merge($params, $deptIdList);
    }

    // Global notifications (no specific target)
    $where .= " OR (n.user_id IS NULL AND n.department_id IS NULL AND n.role_target IS NULL)";
    $where .= ")";

    return $db->fetchAll(
        "SELECT n.*, u.first_name as sender_first, u.last_name as sender_last
         FROM notifications n LEFT JOIN users u ON n.created_by = u.id
         WHERE {$where} ORDER BY n.created_at DESC LIMIT ?",
        array_merge($params, [$limit])
    );
}

function getUnreadNotificationCount() {
    if (!isLoggedIn()) return 0;
    $notifications = getNotifications(50);
    return count(array_filter($notifications, fn($n) => !$n['is_read']));
}

function getUserDepartments($userId = null) {
    $userId = $userId ?? ($_SESSION['user_id'] ?? 0);
    $db = Database::getInstance();
    $memberDepts = $db->fetchAll(
        "SELECT d.* FROM departments d
         INNER JOIN department_members dm ON dm.department_id = d.id
         WHERE dm.user_id = ? AND d.is_active = 1", [$userId]
    );
    $headDepts = $db->fetchAll(
        "SELECT d.* FROM departments d WHERE d.head_user_id = ? AND d.is_active = 1", [$userId]
    );
    $all = array_merge($memberDepts, $headDepts);
    $unique = [];
    foreach ($all as $d) $unique[$d['id']] = $d;
    return array_values($unique);
}

function isPriest() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'priest';
}

function isDepartmentHead($deptId = null) {
    if (!isLoggedIn()) return false;
    $db = Database::getInstance();
    if ($deptId) {
        $dept = $db->fetch("SELECT id FROM departments WHERE id = ? AND head_user_id = ?", [$deptId, $_SESSION['user_id']]);
        return !empty($dept);
    }
    return $_SESSION['user_role'] === 'department_head';
}
