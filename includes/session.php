<?php
// ============================================================
// includes/session.php
// Session Management & Access Control
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    $cookieParams = session_get_cookie_params();
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $cookieParams['path'],
        'domain' => $cookieParams['domain'],
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

/**
 * Require the user to be logged in.
 * Redirects to login page if not authenticated.
 */
function requireLogin(): void {
    if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit();
    }
}

/**
 * Require the user to have Admin role.
 */
function requireAdmin(): void {
    requireLogin();
    if ($_SESSION['role'] !== 'admin') {
        header('Location: ' . BASE_URL . '/pages/dashboard.php?error=access_denied');
        exit();
    }
}

/**
 * Require the user to be a mechanic.
 */
function requireMechanic(): void {
    requireLogin();
    if ($_SESSION['role'] !== 'mechanic') {
        header('Location: ' . BASE_URL . '/pages/dashboard.php?error=access_denied');
        exit();
    }
}

/**
 * Require the user to be a customer.
 */
function requireCustomer(): void {
    requireLogin();
    if ($_SESSION['role'] !== 'customer') {
        header('Location: ' . BASE_URL . '/pages/dashboard.php?error=access_denied');
        exit();
    }
}

/**
 * Return the current logged-in user's ID.
 */
function currentUserId(): int {
    return (int) ($_SESSION['user_id'] ?? 0);
}

/**
 * Return the current logged-in user's role.
 */
function currentRole(): string {
    return $_SESSION['role'] ?? '';
}

/**
 * Check if the current user is admin.
 */
function isAdmin(): bool {
    return currentRole() === 'admin';
}

/**
 * Check if the current user is a customer.
 */
function isCustomer(): bool {
    return currentRole() === 'customer';
}

/**
 * Check if the current user is a mechanic.
 */
function isMechanic(): bool {
    return currentRole() === 'mechanic';
}

/**
 * Redirect with a flash message stored in session.
 */
function redirectWith(string $url, string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    header("Location: $url");
    exit();
}

/**
 * Display and clear the flash message.
 */
function showFlash(): string {
    if (!isset($_SESSION['flash'])) return '';
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $type    = htmlspecialchars($flash['type']);
    $message = htmlspecialchars($flash['message']);
    return "<div class='alert alert-{$type}'>{$message}</div>";
}

/**
 * Generate a CSRF token and store in session.
 */
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token from POST request.
 */
function verifyCsrf(): void {
    if (!isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        die('CSRF token validation failed. Please go back and try again.');
    }
}
