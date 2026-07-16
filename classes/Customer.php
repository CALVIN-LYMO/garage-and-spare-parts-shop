<?php
// ============================================================
// classes/Customer.php
// OOP Concepts: INHERITANCE, POLYMORPHISM (overrides getAll)
// ============================================================
require_once __DIR__ . '/BaseModel.php';

class Customer extends BaseModel {
    protected string $table          = 'customers';
    protected array  $encryptedFields = ['full_name','phone','email','address'];

    public function __construct() {
        parent::__construct();
    }

    private function ensureAuthColumns(): void {
        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM customers LIKE 'password'");
            if ($stmt->rowCount() === 0) {
                $this->db->exec("ALTER TABLE customers ADD COLUMN password VARCHAR(255) NULL AFTER email");
            }

            $stmt = $this->db->query("SHOW COLUMNS FROM customers LIKE 'is_active'");
            if ($stmt->rowCount() === 0) {
                $this->db->exec("ALTER TABLE customers ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER password");
            }
        } catch (PDOException $e) {
            // Ignore migration errors and continue; the app can still run with existing schema.
        }
    }

    public function authenticate(string $identifier, string $password): ?array {
        $this->ensureAuthColumns();

        // Encrypted email and phone cannot be matched directly in SQL.
        // Load active customers and compare decrypted values in PHP.
        $stmt = $this->db->query(
            "SELECT * FROM customers WHERE COALESCE(is_active, 1) = 1"
        );
        $customers = $stmt->fetchAll();

        foreach ($customers as $customer) {
            $customer = $this->enc->decryptFields($customer, $this->encryptedFields);

            if (!empty($customer['password']) && password_verify($password, $customer['password'])) {
                $identifierMatches = false;

                if (str_contains($identifier, '@')) {
                    $identifierMatches = isset($customer['email']) && $customer['email'] === $identifier;
                } else {
                    $identifierMatches = isset($customer['phone']) && $customer['phone'] === $identifier;
                }

                if ($identifierMatches) {
                    return $customer;
                }
            }
        }

        return null;
    }

    public function getAll(): array {
        $stmt = $this->db->query(
            "SELECT c.*, COUNT(v.id) AS vehicle_count
             FROM customers c
             LEFT JOIN vehicles v ON v.customer_id = c.id
             GROUP BY c.id ORDER BY c.created_at DESC"
        );
        $rows = $stmt->fetchAll();
        return $this->enc->decryptRows($rows, $this->encryptedFields);
    }

    public function create(array $data): bool {
        $this->ensureAuthColumns();

        $encrypted = $this->enc->encryptFields($data, $this->encryptedFields);
        $hashedPassword = !empty($data['password'])
            ? password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12])
            : null;

        $stmt = $this->db->prepare(
            "INSERT INTO customers (full_name, phone, email, address, password, is_active, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        return $stmt->execute([
            $encrypted['full_name'] ?? null,
            $encrypted['phone']     ?? null,
            $encrypted['email']     ?? null,
            $encrypted['address']   ?? null,
            $hashedPassword,
            $data['is_active'] ?? 1,
            $data['created_by']     ?? null
        ]);
    }

    public function update(int $id, array $data): bool {
        $encrypted = $this->enc->encryptFields($data, $this->encryptedFields);
        $stmt = $this->db->prepare(
            "UPDATE customers SET full_name=?, phone=?, email=?, address=? WHERE id=?"
        );
        return $stmt->execute([
            $encrypted['full_name'] ?? null,
            $encrypted['phone']     ?? null,
            $encrypted['email']     ?? null,
            $encrypted['address']   ?? null,
            $id
        ]);
    }

    // Polymorphism: different search behavior than Vehicle::search()
    public function search(string $keyword): array {
        // Must decrypt all rows and filter — encrypted data can't be SQL-searched directly
        $all     = $this->getAll();
        $keyword = strtolower(trim($keyword));
        return array_filter($all, function ($row) use ($keyword) {
            return str_contains(strtolower($row['full_name'] ?? ''), $keyword)
                || str_contains(strtolower($row['phone']     ?? ''), $keyword)
                || str_contains(strtolower($row['email']     ?? ''), $keyword);
        });
    }

    public function getLastInsertId(): int {
        return (int) $this->db->lastInsertId();
    }
}
