<?php
require_once __DIR__ . '/BaseModel.php';

class Category extends BaseModel {
    protected string $table = 'categories';
    protected array $encryptedFields = ['name', 'description'];

    public function __construct() {
        parent::__construct();
    }

    public function getAll(): array {
        $stmt = $this->db->query("SELECT * FROM categories ORDER BY created_at DESC");
        return $this->enc->decryptRows($stmt->fetchAll(), $this->encryptedFields);
    }

    public function create(array $data): bool {
        $encrypted = $this->enc->encryptFields($data, $this->encryptedFields);

        $stmt = $this->db->prepare(
            "INSERT INTO categories (name, description) VALUES (?, ?)"
        );
        return $stmt->execute([
            $encrypted['name'] ?? null,
            $encrypted['description'] ?? null,
        ]);
    }

    public function update(int $id, array $data): bool {
        $encrypted = $this->enc->encryptFields($data, $this->encryptedFields);

        $stmt = $this->db->prepare(
            "UPDATE categories SET name = ?, description = ? WHERE id = ?"
        );
        return $stmt->execute([
            $encrypted['name'] ?? null,
            $encrypted['description'] ?? null,
            $id,
        ]);
    }

    public function search(string $keyword): array {
        $all = $this->getAll();
        $keyword = strtolower(trim($keyword));
        return array_filter($all, function ($row) use ($keyword) {
            return str_contains(strtolower($row['name'] ?? ''), $keyword)
                || str_contains(strtolower($row['description'] ?? ''), $keyword);
        });
    }
}
