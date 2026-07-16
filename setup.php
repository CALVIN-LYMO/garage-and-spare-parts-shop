<?php
// setup.php — Run ONCE to create default admin user
// DELETE THIS FILE after running it on your server!
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/User.php';

$userModel = new User();

// Create default admin
$created = $userModel->create([
    'username'  => 'admin',
    'password'  => 'admin123',
    'full_name' => 'System Administrator',
    'email'     => 'admin@garage.com',
    'phone'     => '0700000000',
    'role'      => 'admin',
]);

// Create sample mechanic
$mech = $userModel->create([
    'username'  => 'mechanic1',
    'password'  => 'mech123',
    'full_name' => 'John Mechanic',
    'email'     => 'john@garage.com',
    'phone'     => '0711111111',
    'role'      => 'mechanic',
]);

echo "<h2>Setup Complete!</h2>";
echo $created ? "<p>✅ Admin created: username=<strong>admin</strong>, password=<strong>admin123</strong></p>" : "<p>⚠️ Admin may already exist.</p>";
echo $mech    ? "<p>✅ Mechanic created: username=<strong>mechanic1</strong>, password=<strong>mech123</strong></p>" : "<p>⚠️ Mechanic may already exist.</p>";
echo "<p><strong>⚠️ IMPORTANT: Delete this setup.php file now!</strong></p>";
echo "<p><a href='" . BASE_URL . "/auth/login.php'>Go to Login →</a></p>";
