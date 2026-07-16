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

$registerError = '';
$registerSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    $phonePattern = '/^(?:0\d{9}|255\d{9}|\+255\d{9})$/';

    if ($name === '') {
        $registerError = 'Full name is required.';
    } elseif ($phone === '') {
        $registerError = 'Phone number is required.';
    } elseif (!preg_match($phonePattern, $phone)) {
        $registerError = 'Phone must be 10 digits starting with 0, or begin with 255 or +255 without spaces.';
    } elseif ($email === '') {
        $registerError = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $registerError = 'Please enter a valid email address.';
    } elseif ($password === '') {
        $registerError = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $registerError = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirmPassword) {
        $registerError = 'Passwords do not match.';
    } else {
        $customerModel = new Customer();
        $created = $customerModel->create([
            'full_name' => $name,
            'phone' => $phone,
            'email' => $email,
            'address' => $address,
            'password' => $password,
            'is_active' => 1,
            'created_by' => null,
        ]);

        if ($created) {
            $customer = $customerModel->authenticate($email, $password);
            if ($customer) {
                session_regenerate_id(true);
                $_SESSION['logged_in'] = true;
                $_SESSION['user_id'] = $customer['id'];
                $_SESSION['username'] = $customer['full_name'] ?? $customer['email'] ?? $email;
                $_SESSION['role'] = 'customer';
                $_SESSION['full_name'] = $customer['full_name'] ?? '';

                header('Location: ' . BASE_URL . '/pages/customer_dashboard.php');
                exit();
            }

            $registerSuccess = 'Account created successfully. You can now sign in.';
        } else {
            $registerError = 'Unable to create account. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Register — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="login-page">
<div class="login-wrapper">
    <div class="login-card">
        <div class="login-header">
            <div class="login-icon">📝</div>
            <h1>Customer Register</h1>
            <p>Create a new customer account</p>
        </div>

        <?php if ($registerError): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($registerError) ?></div>
        <?php endif; ?>
        <?php if ($registerSuccess): ?>
            <div class="alert alert-success"><?= htmlspecialchars($registerSuccess) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required autofocus>
            </div>
            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required maxlength="13" pattern="^(0\d{9}|255\d{9}|\+255\d{9})$" placeholder="0XXXXXXXXX or 255XXXXXXXXX or +255XXXXXXXXX" autocomplete="tel">
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autocomplete="email">
            </div>
            <div class="form-group">
                <label for="address">Address</label>
                <input type="text" id="address" name="address" value="<?= htmlspecialchars($_POST['address'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="new_password">Password</label>
                <input type="password" id="new_password" name="password" minlength="6" required autocomplete="new-password">
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" minlength="6" required autocomplete="new-password">
            </div>
            <button type="submit" class="btn-primary full-width">Create Customer Account</button>
        </form>

        <p class="login-hint">
            Already have an account? <a href="<?= BASE_URL ?>/auth/customer_login.php">Customer login</a>
        </p>
    </div>
</div>
</body>
</html>
