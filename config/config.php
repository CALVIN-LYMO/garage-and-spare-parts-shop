<?php
// ============================================================
// config/config.php
// Central configuration file
// NOTE: On AWS EC2, set ENCRYPTION_KEY as an environment
//       variable instead of hardcoding it here:
//       $ export ENCRYPTION_KEY="your-secret-key"
// ============================================================

// ---------- Database Credentials ----------
define('DB_HOST',     'localhost');
define('DB_NAME',     'garage_db');
define('DB_USER',     'root');        // Change to your MySQL user
define('DB_PASS',     '');            // Change to your MySQL password
define('DB_CHARSET',  'utf8mb4');

// ---------- Encryption Key ----------
// On AWS: use getenv('ENCRYPTION_KEY') instead
define('ENCRYPTION_KEY', getenv('ENCRYPTION_KEY') ?: 'GarageSystem@CBE2026#SecretKey!');

// ---------- App Settings ----------
define('APP_NAME',    'TANZAMOTORS & GARAGE');
define('APP_VERSION', '1.0.0');
define('BASE_URL',    '/garage_system'); // Change to your actual path

// ---------- Session Settings ----------
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 when using HTTPS on AWS
