<?php
// ============================================================
// classes/Vehicle.php
// OOP Concepts: INHERITANCE, POLYMORPHISM
// ============================================================
require_once __DIR__ . '/BaseModel.php';

class Vehicle extends BaseModel {
    protected string $table          = 'vehicles';
    protected array  $encryptedFields = ['plate_number','make','model','year','color','engine_number'];

    public function __construct() {
        parent::__construct();
    }

    public function getAll(): array {
        $stmt = $this->db->query(
            "SELECT v.*, c.full_name AS customer_name, c.phone AS customer_phone
             FROM vehicles v
             JOIN customers c ON c.id = v.customer_id
             ORDER BY v.created_at DESC"
        );
        $rows = $stmt->fetchAll();
        $rows = $this->enc->decryptRows($rows, $this->encryptedFields);
        return $this->enc->decryptRows($rows, ['customer_name', 'customer_phone']);
    }

    public function getByCustomer(int $customerId): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM vehicles WHERE customer_id = ? ORDER BY created_at DESC"
        );
        $stmt->execute([$customerId]);
        $rows = $stmt->fetchAll();
        return $this->enc->decryptRows($rows, $this->encryptedFields);
    }

    public function create(array $data): bool {
        $encrypted = $this->enc->encryptFields($data, $this->encryptedFields);
        $stmt = $this->db->prepare(
            "INSERT INTO vehicles (customer_id, plate_number, make, model, year, color, engine_number)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        return $stmt->execute([
            $data['customer_id'],
            $encrypted['plate_number']  ?? null,
            $encrypted['make']          ?? null,
            $encrypted['model']         ?? null,
            $encrypted['year']          ?? null,
            $encrypted['color']         ?? null,
            $encrypted['engine_number'] ?? null,
        ]);
    }

    public function update(int $id, array $data): bool {
        $encrypted = $this->enc->encryptFields($data, $this->encryptedFields);
        $stmt = $this->db->prepare(
            "UPDATE vehicles SET plate_number=?, make=?, model=?, year=?, color=?, engine_number=?
             WHERE id=?"
        );
        return $stmt->execute([
            $encrypted['plate_number']  ?? null,
            $encrypted['make']          ?? null,
            $encrypted['model']         ?? null,
            $encrypted['year']          ?? null,
            $encrypted['color']         ?? null,
            $encrypted['engine_number'] ?? null,
            $id
        ]);
    }

    public function search(string $keyword): array {
        $all     = $this->getAll();
        $keyword = strtolower(trim($keyword));
        return array_filter($all, function ($row) use ($keyword) {
            return str_contains(strtolower($row['plate_number'] ?? ''), $keyword)
                || str_contains(strtolower($row['make']         ?? ''), $keyword)
                || str_contains(strtolower($row['model']        ?? ''), $keyword);
        });
    }
}
