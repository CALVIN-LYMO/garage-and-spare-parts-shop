<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../includes/session.php';

if (!empty($_SESSION['logged_in'])) {
    $redirect = ($_SESSION['role'] ?? '') === 'customer'
        ? BASE_URL . '/pages/customer_dashboard.php'
        : BASE_URL . '/pages/dashboard.php';
    header('Location: ' . $redirect);
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $userModel = new User();
        $user = $userModel->authenticate($username, $password);

        if ($user && ($user['role'] ?? '') === 'mechanic') {
            session_regenerate_id(true);
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];

            header('Location: ' . BASE_URL . '/pages/dashboard.php');
            exit();
        }

        $error = 'Invalid mechanic credentials.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mechanic Login — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="login-page">
<div class="login-wrapper">
    <div class="login-card">
        <div class="login-header">
            <div class="login-icon">🔧</div>
            <h1>Mechanic Access</h1>
            <p>View your assigned jobs and service requests</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" placeholder="Enter username" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter password" required>
            </div>
            <button type="submit" class="btn-primary full-width">Sign In as Mechanic</button>
        </form>

        <p class="login-hint">Demo mechanic: <strong>mechanic1</strong> / <strong>mech123</strong></p>
    </div>
</div>
</body>
</html>
