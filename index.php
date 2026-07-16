<?php
// index.php — Entry point
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/session.php';

if (!empty($_SESSION['logged_in'])) {
    if (($_SESSION['role'] ?? '') === 'customer') {
        header('Location: ' . BASE_URL . '/pages/customer_dashboard.php');
    } else {
        header('Location: ' . BASE_URL . '/pages/dashboard.php');
    }
} else {
    header('Location: ' . BASE_URL . '/pages/home.php');
}
exit();
