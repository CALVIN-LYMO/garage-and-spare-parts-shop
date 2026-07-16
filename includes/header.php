<?php
// includes/header.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/session.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<nav class="navbar">
    <div class="nav-brand">
        <span class="brand-icon">🔧</span>
        <strong><?= APP_NAME ?></strong>
    </div>
    <div class="nav-links">
        <?php if (isCustomer()): ?>
        <a href="<?= BASE_URL ?>/pages/customer_dashboard.php">My Account</a>
        <?php else: ?>
        <a href="<?= BASE_URL ?>/pages/dashboard.php">Dashboard</a>
        <?php if (isAdmin()): ?>
        <a href="<?= BASE_URL ?>/pages/customers.php">Customers</a>
        <a href="<?= BASE_URL ?>/pages/vehicles.php">Vehicles</a>
        <a href="<?= BASE_URL ?>/pages/products.php">Products</a>
        <a href="<?= BASE_URL ?>/pages/categories.php">Categories</a>
        <a href="<?= BASE_URL ?>/pages/orders.php">Orders</a>
        <a href="<?= BASE_URL ?>/pages/manage_services.php">Services</a>
        <a href="<?= BASE_URL ?>/pages/jobs.php">Repair Jobs</a>
        <a href="<?= BASE_URL ?>/pages/payments.php">Payments</a>
        <a href="<?= BASE_URL ?>/pages/service_requests.php">Service Requests</a>
        <a href="<?= BASE_URL ?>/pages/users.php">Users</a>
        <a href="<?= BASE_URL ?>/pages/reports.php">Reports</a>
        <?php elseif (isMechanic()): ?>
        <a href="<?= BASE_URL ?>/pages/jobs.php">My Jobs</a>
        <a href="<?= BASE_URL ?>/pages/service_requests.php">My Requests</a>
        <?php endif; ?>
        <?php endif; ?>
    </div>
    <div class="nav-user">
        <span>👤 <?= htmlspecialchars($_SESSION['username'] ?? '') ?>
              <small>(<?= htmlspecialchars(currentRole()) ?>)</small>
        </span>
        <a href="<?= BASE_URL ?>/auth/logout.php" class="btn-logout">Logout</a>
    </div>
</nav>
<main class="container">
<?= showFlash() ?>
