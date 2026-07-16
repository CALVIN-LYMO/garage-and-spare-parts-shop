<?php
require_once __DIR__ . '/BaseModel.php';

class Product extends BaseModel {
    protected string $table = 'products';
    protected array $encryptedFields = ['name', 'description', 'image_path'];

    public function __construct() {
        parent::__construct();
    }

    public function getAll(): array {
        $stmt = $this->db->query(
            "SELECT p.*, c.name AS category_name
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             ORDER BY p.created_at DESC"
        );

        $rows = $stmt->fetchAll();
        $rows = $this->enc->decryptRows($rows, $this->encryptedFields);
        return $this->enc->decryptRows($rows, ['category_name']);
    }

    public function create(array $data): bool {
        $encrypted = $this->enc->encryptFields($data, $this->encryptedFields);

        $stmt = $this->db->prepare(
            "INSERT INTO products (category_id, name, description, image_path, price, stock)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        return $stmt->execute([
            $data['category_id'] ?? null,
            $encrypted['name'] ?? null,
            $encrypted['description'] ?? null,
            $encrypted['image_path'] ?? null,
            $data['price'] ?? 0.00,
            $data['stock'] ?? 0,
        ]);
    }

    public function update(int $id, array $data): bool {
        $encrypted = $this->enc->encryptFields($data, $this->encryptedFields);

        $stmt = $this->db->prepare(
            "UPDATE products SET category_id = ?, name = ?, description = ?, image_path = ?, price = ?, stock = ?
             WHERE id = ?"
        );

        return $stmt->execute([
            $data['category_id'] ?? null,
            $encrypted['name'] ?? null,
            $encrypted['description'] ?? null,
            $encrypted['image_path'] ?? null,
            $data['price'] ?? 0.00,
            $data['stock'] ?? 0,
            $id,
        ]);
    }

    public function getByCategory(int $categoryId): array {
        $stmt = $this->db->prepare(
            "SELECT p.*, c.name AS category_name
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.category_id = ?
             ORDER BY p.created_at DESC"
        );
        $stmt->execute([$categoryId]);

        $rows = $stmt->fetchAll();
        $rows = $this->enc->decryptRows($rows, $this->encryptedFields);
        return $this->enc->decryptRows($rows, ['category_name']);
    }

    public function search(string $keyword): array {
        $all = $this->getAll();
        $keyword = strtolower(trim($keyword));
        return array_filter($all, function ($row) use ($keyword) {
            return str_contains(strtolower($row['name'] ?? ''), $keyword)
                || str_contains(strtolower($row['description'] ?? ''), $keyword)
                || str_contains(strtolower($row['category_name'] ?? ''), $keyword);
        });
    }

    public function getAvailable(): array {
        return array_filter($this->getAll(), fn($row) => isset($row['stock']) && (int)$row['stock'] > 0);
    }

    public function hasStock(int $productId, int $quantity): bool {
        $product = $this->findById($productId);
        return $product !== null && (int)($product['stock'] ?? 0) >= $quantity;
    }

    public function decrementStock(int $productId, int $quantity): bool {
        $stmt = $this->db->prepare(
            "UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?"
        );
        $stmt->execute([$quantity, $productId, $quantity]);
        return $stmt->rowCount() > 0;
    }
}
