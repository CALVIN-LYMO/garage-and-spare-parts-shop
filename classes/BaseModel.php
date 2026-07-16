<?php
// ============================================================
// classes/BaseModel.php
// OOP Concepts: ABSTRACTION, ENCAPSULATION
// All models inherit from this class
// ============================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Encryption.php';

abstract class BaseModel {
    protected PDO        $db;
    protected Encryption $enc;
    protected string     $table;       // Child class must set this
    protected array      $encryptedFields = []; // Fields to encrypt in child

    public function __construct() {
        $this->db  = DatabaseConnection::getInstance()->getConnection();
        $this->enc = new Encryption();
    }

    // ---- Abstract methods: child classes MUST implement ----
    abstract public function getAll(): array;
    abstract public function create(array $data): bool;
    abstract public function update(int $id, array $data): bool;

    // ---- Shared concrete methods (inherited by all) --------

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) return null;
        return $this->enc->decryptFields($row, $this->encryptedFields);
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare(
            "DELETE FROM {$this->table} WHERE id = ?"
        );
        return $stmt->execute([$id]);
    }

    public function count(): int {
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table}");
        return (int) $stmt->fetchColumn();
    }

    // Sanitize input to prevent XSS
    protected function sanitize(string $value): string {
        return htmlspecialchars(strip_tags(trim($value)));
    }
}
