<?php
// auth/logout.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';

// Determine where to redirect after logout.
$redirectUrl = BASE_URL . '/auth/login.php';
if (!empty($_SESSION['role']) && $_SESSION['role'] === 'customer') {
    $redirectUrl = BASE_URL . '/auth/customer_login.php';
}

// Destroy session completely
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}
session_destroy();

header('Location: ' . $redirectUrl);
exit();
