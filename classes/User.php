<?php
// ============================================================
// classes/User.php
// OOP Concepts: INHERITANCE (extends BaseModel), ENCAPSULATION
// ============================================================
require_once __DIR__ . '/BaseModel.php';

class User extends BaseModel {
    // Private properties — Encapsulation
    private string $username;
    private string $role;

    protected string $table = 'users';
    protected array  $encryptedFields = ['username', 'full_name', 'email', 'phone', 'location'];

    public function __construct() {
        parent::__construct(); // Call BaseModel constructor
        $this->ensureLocationColumn();
    }

    private function ensureLocationColumn(): void {
        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM users LIKE 'location'");
            if ($stmt->rowCount() === 0) {
                $this->db->exec("ALTER TABLE users ADD COLUMN location VARCHAR(500) NULL AFTER phone");
            }
        } catch (PDOException $e) {
            // Ignore migration errors; this app can still run with existing schema.
        }
    }

    // ---- Getters (Encapsulation) ----------------------------
    public function getUsername(): string { return $this->username; }
    public function getRole(): string     { return $this->role; }

    // ---- Implement abstract methods from BaseModel ----------

    public function getAll(): array {
        $stmt = $this->db->query(
            "SELECT id, username, full_name, email, phone, location, role, is_active, created_at
             FROM users ORDER BY created_at DESC"
        );
        $rows = $stmt->fetchAll();
        return $this->enc->decryptRows($rows, $this->encryptedFields);
    }

    public function create(array $data): bool {
        // Validate required fields
        if (empty($data['username']) || empty($data['password'])) return false;

        // Sanitize
        $username = $this->sanitize($data['username']);
        $role     = in_array($data['role'], ['admin','mechanic']) ? $data['role'] : 'mechanic';

        // Hash password — NEVER store plain password
        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);

        // Encrypt personal data fields
        $encrypted = $this->enc->encryptFields($data, $this->encryptedFields);

        $stmt = $this->db->prepare(
            "INSERT INTO users (username, password, full_name, email, phone, location, role)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        return $stmt->execute([
            $username,
            $hashedPassword,
            $encrypted['full_name'] ?? null,
            $encrypted['email']     ?? null,
            $encrypted['phone']     ?? null,
            $encrypted['location']  ?? null,
            $role
        ]);
    }

    public function update(int $id, array $data): bool {
        $encrypted = $this->enc->encryptFields($data, $this->encryptedFields);
        $role      = in_array($data['role'] ?? '', ['admin','mechanic']) ? $data['role'] : 'mechanic';

        $stmt = $this->db->prepare(
            "UPDATE users SET full_name=?, email=?, phone=?, location=?, role=?, is_active=?
             WHERE id=?"
        );
        return $stmt->execute([
            $encrypted['full_name'] ?? null,
            $encrypted['email']     ?? null,
            $encrypted['phone']     ?? null,
            $encrypted['location']  ?? null,
            $role,
            $data['is_active'] ?? 1,
            $id
        ]);
    }

    // ---- Authentication Methods ----------------------------

    public function authenticate(string $username, string $password): ?array {
        $stmt = $this->db->query("SELECT * FROM users WHERE is_active = 1");
        $users = $stmt->fetchAll();

        foreach ($users as $user) {
            $decryptedUser = $this->enc->decryptFields($user, $this->encryptedFields);

            if (($decryptedUser['username'] ?? '') === $username && password_verify($password, $user['password'])) {
                return $decryptedUser;
            }
        }

        return null;
    }

    public function usernameExists(string $username): bool {
        $stmt = $this->db->query("SELECT * FROM users");
        $users = $stmt->fetchAll();

        foreach ($users as $user) {
            $decryptedUser = $this->enc->decryptFields($user, $this->encryptedFields);
            if (($decryptedUser['username'] ?? '') === $username) {
                return true;
            }
        }

        return false;
    }

    public function getMechanics(): array {
        $stmt = $this->db->query(
            "SELECT id, username, full_name, phone, location FROM users
             WHERE role='mechanic' AND is_active=1"
        );
        $rows = $stmt->fetchAll();
        return $this->enc->decryptRows($rows, $this->encryptedFields);
    }

    public function getMechanicsByLocation(string $location): array {
        $mechanics = $this->getMechanics();
        $keyword = strtolower(trim($location));
        if ($keyword === '') {
            return $mechanics;
        }
        return array_filter($mechanics, function ($mechanic) use ($keyword) {
            return str_contains(strtolower($mechanic['location'] ?? ''), $keyword)
                || str_contains(strtolower($mechanic['full_name'] ?? ''), $keyword);
        });
    }

    public function changePassword(int $id, string $newPassword): bool {
        $hashed = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt   = $this->db->prepare("UPDATE users SET password=? WHERE id=?");
        return $stmt->execute([$hashed, $id]);
    }
}
