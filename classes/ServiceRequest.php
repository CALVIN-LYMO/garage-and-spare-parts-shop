<?php
require_once __DIR__ . '/BaseModel.php';

class ServiceRequest extends BaseModel {
    protected string $table = 'service_requests';
    protected array $encryptedFields = ['issue_description', 'location', 'notes'];

    public function __construct() {
        parent::__construct();
    }

    public function getAll(): array {
        $stmt = $this->db->query(
            "SELECT sr.*, c.full_name AS customer_name, c.phone AS customer_phone,
                    v.plate_number, v.make, v.model,
                    s.service_name, u.full_name AS mechanic_name
             FROM service_requests sr
             JOIN customers c ON c.id = sr.customer_id
             LEFT JOIN vehicles v ON v.id = sr.vehicle_id
             LEFT JOIN services s ON s.id = sr.service_id
             LEFT JOIN users u ON u.id = sr.assigned_mechanic_id
             ORDER BY sr.created_at DESC"
        );

        $rows = $stmt->fetchAll();
        $rows = $this->enc->decryptRows($rows, $this->encryptedFields);
        return $this->enc->decryptRows($rows, ['customer_name', 'customer_phone', 'plate_number', 'make', 'model', 'service_name', 'mechanic_name']);
    }

    public function create(array $data): bool {
        $encrypted = $this->enc->encryptFields($data, $this->encryptedFields);

        $stmt = $this->db->prepare(
            "INSERT INTO service_requests
             (customer_id, vehicle_id, service_id, issue_description, location, preferred_date,
              assigned_mechanic_id, status, notes, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        return $stmt->execute([
            $data['customer_id'],
            $data['vehicle_id'] ?? null,
            $data['service_id'] ?? null,
            $encrypted['issue_description'] ?? null,
            $encrypted['location'] ?? null,
            $data['preferred_date'] ?? null,
            $data['assigned_mechanic_id'] ?? null,
            $data['status'] ?? 'pending',
            $encrypted['notes'] ?? null,
            $data['created_by'] ?? null,
        ]);
    }

    public function update(int $id, array $data): bool {
        $encrypted = $this->enc->encryptFields($data, $this->encryptedFields);

        $stmt = $this->db->prepare(
            "UPDATE service_requests
             SET vehicle_id = ?, service_id = ?, issue_description = ?, location = ?,
                 preferred_date = ?, assigned_mechanic_id = ?, status = ?, notes = ?
             WHERE id = ?"
        );

        return $stmt->execute([
            $data['vehicle_id'] ?? null,
            $data['service_id'] ?? null,
            $encrypted['issue_description'] ?? null,
            $encrypted['location'] ?? null,
            $data['preferred_date'] ?? null,
            $data['assigned_mechanic_id'] ?? null,
            $data['status'] ?? 'pending',
            $encrypted['notes'] ?? null,
            $id,
        ]);
    }

    public function assignMechanic(int $id, int $mechanicId, string $status = 'in-progress', ?string $notes = null): bool {
        $encrypted = $this->enc->encryptFields(['notes' => $notes], ['notes']);

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare(
                "UPDATE service_requests
                 SET assigned_mechanic_id = ?, status = ?, notes = CONCAT(COALESCE(notes, ''), ?)
                 WHERE id = ?"
            );
            if (!$stmt->execute([
                $mechanicId,
                $status,
                $encrypted['notes'] ? "\n" . $encrypted['notes'] : null,
                $id,
            ])) {
                throw new Exception('Failed to update service request.');
            }

            require_once __DIR__ . '/MechanicsAssignment.php';
            $assignmentModel = new MechanicsAssignment();
            $assignmentModel->create([
                'service_request_id' => $id,
                'mechanic_id'        => $mechanicId,
                'status'             => 'assigned',
                'notes'              => $notes,
            ]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function getPendingRequests(): array {
        $stmt = $this->db->query(
            "SELECT sr.*, c.full_name AS customer_name, c.phone AS customer_phone,
                    v.plate_number, v.make, v.model,
                    s.service_name, u.full_name AS mechanic_name
             FROM service_requests sr
             JOIN customers c ON c.id = sr.customer_id
             LEFT JOIN vehicles v ON v.id = sr.vehicle_id
             LEFT JOIN services s ON s.id = sr.service_id
             LEFT JOIN users u ON u.id = sr.assigned_mechanic_id
             WHERE sr.status = 'pending'
             ORDER BY sr.created_at DESC"
        );

        $rows = $stmt->fetchAll();
        $rows = $this->enc->decryptRows($rows, $this->encryptedFields);
        return $this->enc->decryptRows($rows, ['customer_name', 'customer_phone', 'plate_number', 'make', 'model', 'service_name', 'mechanic_name']);
    }

    public function getByMechanic(int $mechanicId): array {
        $stmt = $this->db->prepare(
            "SELECT sr.*, c.full_name AS customer_name, c.phone AS customer_phone,
                    v.plate_number, v.make, v.model,
                    s.service_name, u.full_name AS mechanic_name
             FROM service_requests sr
             JOIN customers c ON c.id = sr.customer_id
             LEFT JOIN vehicles v ON v.id = sr.vehicle_id
             LEFT JOIN services s ON s.id = sr.service_id
             LEFT JOIN users u ON u.id = sr.assigned_mechanic_id
             WHERE sr.assigned_mechanic_id = ?
             ORDER BY sr.created_at DESC"
        );
        $stmt->execute([$mechanicId]);

        $rows = $stmt->fetchAll();
        $rows = $this->enc->decryptRows($rows, $this->encryptedFields);
        return $this->enc->decryptRows($rows, ['customer_name', 'customer_phone', 'plate_number', 'make', 'model', 'service_name', 'mechanic_name']);
    }

    private function tableExists(): bool {
        $quotedTable = $this->db->quote($this->table);
        $stmt = $this->db->query("SHOW TABLES LIKE {$quotedTable}");
        return $stmt && $stmt->fetch() !== false;
    }

    public function search(string $keyword): array {
        $all = $this->getAll();
        $keyword = strtolower(trim($keyword));
        return array_filter($all, function ($row) use ($keyword) {
            return str_contains(strtolower($row['customer_name'] ?? ''), $keyword)
                || str_contains(strtolower($row['service_name'] ?? ''), $keyword)
                || str_contains(strtolower($row['issue_description'] ?? ''), $keyword)
                || str_contains(strtolower($row['location'] ?? ''), $keyword)
                || str_contains(strtolower($row['status'] ?? ''), $keyword);
        });
    }

    public function getByCustomer(int $customerId): array {
        if (!$this->tableExists()) {
            return [];
        }

        $stmt = $this->db->prepare(
            "SELECT sr.*, c.full_name AS customer_name, c.phone AS customer_phone,
                    v.plate_number, v.make, v.model,
                    s.service_name, u.full_name AS mechanic_name
             FROM service_requests sr
             JOIN customers c ON c.id = sr.customer_id
             LEFT JOIN vehicles v ON v.id = sr.vehicle_id
             LEFT JOIN services s ON s.id = sr.service_id
             LEFT JOIN users u ON u.id = sr.assigned_mechanic_id
             WHERE sr.customer_id = ?
             ORDER BY sr.created_at DESC"
        );
        $stmt->execute([$customerId]);

        $rows = $stmt->fetchAll();
        $rows = $this->enc->decryptRows($rows, $this->encryptedFields);
        return $this->enc->decryptRows($rows, ['customer_name', 'customer_phone', 'plate_number', 'make', 'model', 'service_name', 'mechanic_name']);
    }

    public function getCountByStatus(): array {
        $counts = ['pending' => 0, 'in-progress' => 0, 'completed' => 0, 'cancelled' => 0];
        foreach ($this->getAll() as $request) {
            $status = strtolower($request['status'] ?? 'pending');
            if (isset($counts[$status])) {
                $counts[$status]++;
            }
        }
        return $counts;
    }
}
