<?php
require_once __DIR__ . '/BaseModel.php';

class MechanicsAssignment extends BaseModel {
    protected string $table = 'mechanics_assignments';
    protected array $encryptedFields = ['notes'];

    public function __construct() {
        parent::__construct();
    }

    public function getAll(): array {
        $stmt = $this->db->query(
            "SELECT ma.*, sr.issue_description, u.full_name AS mechanic_name
             FROM mechanics_assignments ma
             JOIN service_requests sr ON sr.id = ma.service_request_id
             JOIN users u ON u.id = ma.mechanic_id
             ORDER BY ma.assigned_at DESC"
        );

        $rows = $stmt->fetchAll();
        $rows = $this->enc->decryptRows($rows, $this->encryptedFields);
        return $rows;
    }

    public function create(array $data): bool {
        $encrypted = $this->enc->encryptFields($data, $this->encryptedFields);

        $stmt = $this->db->prepare(
            "INSERT INTO mechanics_assignments
             (service_request_id, mechanic_id, status, notes)
             VALUES (?, ?, ?, ?)"
        );

        return $stmt->execute([
            $data['service_request_id'],
            $data['mechanic_id'],
            $data['status'] ?? 'assigned',
            $encrypted['notes'] ?? null,
        ]);
    }

    public function update(int $id, array $data): bool {
        $encrypted = $this->enc->encryptFields($data, $this->encryptedFields);

        $stmt = $this->db->prepare(
            "UPDATE mechanics_assignments
             SET status = ?, notes = ?
             WHERE id = ?"
        );

        return $stmt->execute([
            $data['status'] ?? 'assigned',
            $encrypted['notes'] ?? null,
            $id,
        ]);
    }

    public function getByMechanic(int $mechanicId): array {
        $stmt = $this->db->prepare(
            "SELECT ma.*, sr.issue_description, u.full_name AS mechanic_name
             FROM mechanics_assignments ma
             JOIN service_requests sr ON sr.id = ma.service_request_id
             JOIN users u ON u.id = ma.mechanic_id
             WHERE ma.mechanic_id = ?
             ORDER BY ma.assigned_at DESC"
        );
        $stmt->execute([$mechanicId]);

        $rows = $stmt->fetchAll();
        return $this->enc->decryptRows($rows, $this->encryptedFields);
    }
}
