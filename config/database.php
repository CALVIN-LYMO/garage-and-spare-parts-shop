<?php
// ============================================================
// config/database.php
// PDO Database Connection — Singleton Pattern
// ============================================================
require_once __DIR__ . '/config.php';

class DatabaseConnection {
    private static $instance = null;
    private $connection;

    // Private constructor — prevents direct instantiation
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST .
                   ";dbname=" . DB_NAME .
                   ";charset=" . DB_CHARSET;

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false, // Prevents SQL injection
            ];

            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Don't expose DB errors to users
            error_log("DB Connection Error: " . $e->getMessage());
            die(json_encode(['error' => 'Database connection failed. Please contact admin.']));
        }
    }

    // Singleton: returns one shared instance
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO {
        return $this->connection;
    }

    // Prevent cloning of instance
    private function __clone() {}
}
