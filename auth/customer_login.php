<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Customer.php';
require_once __DIR__ . '/../includes/session.php';

if (!empty($_SESSION['logged_in'])) {
    $redirect = ($_SESSION['role'] ?? '') === 'customer'
        ? BASE_URL . '/pages/customer_dashboard.php'
        : BASE_URL . '/pages/dashboard.php';
    header('Location: ' . $redirect);
    exit();
}

$customerError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $identifier = trim($_POST['identifier'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($identifier === '' || $password === '') {
        $customerError = 'Please enter your phone/email and password.';
    } elseif (str_contains($identifier, '@')) {
        if (!filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $customerError = 'Please enter a valid email address.';
        }
    } else {
        $phonePattern = '/^(?:0\d{9}|255\d{9}|\+255\d{9})$/';
        if (!preg_match($phonePattern, $identifier)) {
            $customerError = 'Please enter a valid phone number (0XXXXXXXXX, 255XXXXXXXXX, or +255XXXXXXXXX).';
        }
    }

    if ($customerError === '') {
        $customerModel = new Customer();
        $customer = $customerModel->authenticate($identifier, $password);

        if ($customer) {
            session_regenerate_id(true);
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $customer['id'];
            $_SESSION['username'] = $customer['full_name'] ?? $customer['email'] ?? $identifier;
            $_SESSION['role'] = 'customer';
            $_SESSION['full_name'] = $customer['full_name'] ?? '';

            header('Location: ' . BASE_URL . '/pages/customer_dashboard.php');
            exit();
        }

        $customerError = 'Invalid customer credentials.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Login — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="login-page">
<div class="login-wrapper">
    <div class="login-card">
        <div class="login-header">
            <div class="login-icon">👤</div>
            <h1>Customer Access</h1>
            <p>Login or create a new customer account</p>
        </div>

        <?php if ($customerError): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($customerError) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="form" value="customer_login">
            <div class="form-group">
                <label for="identifier">Phone or Email</label>
                <input type="text" id="identifier" name="identifier" value="<?= htmlspecialchars($_POST['identifier'] ?? '') ?>" placeholder="Enter phone or email" required autofocus autocomplete="username">
            </div>
            <div class="form-group">
                <label for="customer_password">Password</label>
                <input type="password" id="customer_password" name="password" placeholder="Enter password" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn-primary full-width">Sign In as Customer</button>
        </form>

        <hr style="margin:20px 0;">

        <p class="login-hint">
            Huna account? <a href="<?= BASE_URL ?>/auth/customer_register.php">Customer register</a>
        </p>
    </div>
</div>
</body>
</html>
