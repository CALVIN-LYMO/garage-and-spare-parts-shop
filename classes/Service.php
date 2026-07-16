<?php
require_once __DIR__ . '/BaseModel.php';

class Service extends BaseModel {
    protected string $table = 'services';
    protected array $encryptedFields = ['service_name', 'base_price', 'description'];

    public function __construct() {
        parent::__construct();
    }

    public function getAll(): array {
        $stmt = $this->db->query("SELECT * FROM services ORDER BY created_at DESC");
        return $this->enc->decryptRows($stmt->fetchAll(), $this->encryptedFields);
    }

    public function create(array $data): bool {
        $encrypted = $this->enc->encryptFields($data, $this->encryptedFields);
        $stmt = $this->db->prepare("INSERT INTO services (service_name, base_price, description) VALUES (?, ?, ?)");
        return $stmt->execute([
            $encrypted['service_name'] ?? null,
            $encrypted['base_price'] ?? null,
            $encrypted['description'] ?? null,
        ]);
    }

    public function update(int $id, array $data): bool {
        $encrypted = $this->enc->encryptFields($data, $this->encryptedFields);
        $stmt = $this->db->prepare(
            "UPDATE services SET service_name = ?, base_price = ?, description = ? WHERE id = ?"
        );
        return $stmt->execute([
            $encrypted['service_name'] ?? null,
            $encrypted['base_price'] ?? null,
            $encrypted['description'] ?? null,
            $id,
        ]);
    }

    public function search(string $keyword): array {
        $all = $this->getAll();
        $keyword = strtolower(trim($keyword));
        return array_filter($all, function ($row) use ($keyword) {
            return str_contains(strtolower($row['service_name'] ?? ''), $keyword)
                || str_contains(strtolower($row['description'] ?? ''), $keyword);
        });
    }
}
