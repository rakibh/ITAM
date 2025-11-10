<?php
// File: config/session.php
// Purpose: Session initialization and management with security features

// Prevent direct access
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . '/');
}

// Ensure config is loaded
if (!defined('SESSION_NAME')) {
    require_once __DIR__ . '/config.php';
}

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_lifetime', 0);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// Generate CSRF token if not exists
if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
    $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isLoggedIn() && $_SESSION['user_role'] === 'admin';
}

/**
 * Check if user is standard user
 */
function isUser() {
    return isLoggedIn() && $_SESSION['user_role'] === 'user';
}

/**
 * Get current user ID
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user name
 */
function getCurrentUserName() {
    return $_SESSION['user_name'] ?? 'Unknown';
}

/**
 * Get current user role
 */
function getCurrentUserRole() {
    return $_SESSION['user_role'] ?? null;
}

/**
 * Get current employee ID
 */
function getCurrentEmployeeId() {
    return $_SESSION['employee_id'] ?? null;
}

/**
 * Redirect if not logged in
 */
function requireLogin($redirectUrl = null) {
    if (!isLoggedIn()) {
        $redirect = $redirectUrl ?? (defined('BASE_URL') ? BASE_URL . 'login.php' : 'login.php');
        header('Location: ' . $redirect);
        exit();
    }
}

/**
 * Redirect if not admin
 */
function requireAdmin($redirectUrl = null) {
    requireLogin();
    if (!isAdmin()) {
        $redirect = $redirectUrl ?? (defined('BASE_URL') ? BASE_URL . 'pages/dashboard_user.php' : 'pages/dashboard_user.php');
        header('Location: ' . $redirect);
        exit();
    }
}

/**
 * Regenerate session ID for security
 */
function regenerateSession() {
    return session_regenerate_id(true);
}

/**
 * Destroy session and logout
 */
function destroySession() {
    $_SESSION = array();
    
    if (isset($_COOKIE[session_name()])) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    
    session_destroy();
}

/**
 * Verify CSRF token
 */
function verifyCsrfToken($token) {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        return false;
    }
    return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Get CSRF token
 */
function getCsrfToken() {
    return $_SESSION[CSRF_TOKEN_NAME] ?? '';
}

/**
 * Generate CSRF token input field
 */
function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . getCsrfToken() . '">';
}

/**
 * Prevent browser back button after logout
 */
function preventBackButton() {
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Expires: Sat, 01 Jan 2000 00:00:00 GMT");
}

/**
 * Set session flash message
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = ['type' => $type, 'message' => $message];
}

/**
 * Get and clear flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }
    return null;
}

/**
 * Update last activity timestamp
 */
function updateLastActivity() {
    $_SESSION['last_activity'] = time();
}

// Update last activity
updateLastActivity();