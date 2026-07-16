<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/session.php';
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
        <a href="<?= BASE_URL ?>">Home</a>
        <a href="<?= BASE_URL ?>/pages/services.php">Services</a>
        <a href="<?= BASE_URL ?>/pages/shop.php">Shop</a>
        <a href="<?= BASE_URL ?>/pages/request_service.php">Request Fundi</a>
        <a href="<?= BASE_URL ?>/pages/about.php">About</a>
    </div>
    <div class="nav-user">
        <?php if (!empty($_SESSION['logged_in'])): ?>
            <?php if (isCustomer()): ?>
                <a href="<?= BASE_URL ?>/pages/customer_dashboard.php" class="btn-sm">My Account</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/pages/dashboard.php" class="btn-sm">Dashboard</a>
            <?php endif; ?>
        <?php else: ?>
            <a href="<?= BASE_URL ?>/auth/customer_login.php" class="btn-sm">Customer Login</a>
            <a href="<?= BASE_URL ?>/auth/mechanic_login.php" class="btn-sm">Mechanic Login</a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/pages/cart.php" class="btn-sm">Cart (<?= array_sum($_SESSION['cart'] ?? []) ?>)</a>
    </div>
</nav>
<main class="container">
<?php if (isset($_SESSION['flash'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash']['message']) ?></div>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>
