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
