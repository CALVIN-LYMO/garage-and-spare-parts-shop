<?php
// ============================================================
// classes/RepairJob.php
// OOP Concepts: INHERITANCE, ENCAPSULATION, POLYMORPHISM
// ============================================================
require_once __DIR__ . '/BaseModel.php';

class RepairJob extends BaseModel {
    protected string $table          = 'repair_jobs';
    protected array  $encryptedFields = [
        'job_description','diagnosis','status',
        'date_received','date_completed','total_cost'
    ];

    public function __construct() {
        parent::__construct();
    }

    public function getAll(): array {
        $stmt = $this->db->query(
            "SELECT rj.*,
                    v.plate_number, v.make, v.model,
                    c.full_name AS customer_name,
                    u.full_name AS mechanic_name
             FROM repair_jobs rj
             JOIN vehicles v  ON v.id  = rj.vehicle_id
             JOIN customers c ON c.id  = v.customer_id
             LEFT JOIN users u ON u.id = rj.mechanic_id
             ORDER BY rj.created_at DESC"
        );
        $rows = $stmt->fetchAll();
        $rows = $this->enc->decryptRows($rows, $this->encryptedFields);
        $rows = $this->enc->decryptRows($rows, ['plate_number','make','model']);
        $rows = $this->enc->decryptRows($rows, ['customer_name','mechanic_name']);
        return $rows;
    }

    public function create(array $data): bool {
        $encrypted = $this->enc->encryptFields($data, $this->encryptedFields);
        $stmt = $this->db->prepare(
            "INSERT INTO repair_jobs
             (vehicle_id, mechanic_id, job_description, diagnosis, status, date_received, total_cost, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        return $stmt->execute([
            $data['vehicle_id'],
            $data['mechanic_id']          ?? null,
            $encrypted['job_description'] ?? null,
            $encrypted['diagnosis']       ?? null,
            $encrypted['status']          ?? $this->enc->encrypt('pending'),
            $encrypted['date_received']   ?? $this->enc->encrypt(date('Y-m-d')),
            $encrypted['total_cost']      ?? null,
            $data['created_by']           ?? null,
        ]);
    }

    public function update(int $id, array $data): bool {
        $encrypted = $this->enc->encryptFields($data, $this->encryptedFields);
        $stmt = $this->db->prepare(
            "UPDATE repair_jobs
             SET mechanic_id=?, job_description=?, diagnosis=?, status=?,
                 date_received=?, date_completed=?, total_cost=?
             WHERE id=?"
        );
        return $stmt->execute([
            $data['mechanic_id']           ?? null,
            $encrypted['job_description']  ?? null,
            $encrypted['diagnosis']        ?? null,
            $encrypted['status']           ?? null,
            $encrypted['date_received']    ?? null,
            $encrypted['date_completed']   ?? null,
            $encrypted['total_cost']       ?? null,
            $id
        ]);
    }

    public function getByMechanic(int $mechanicId): array {
        $stmt = $this->db->prepare(
            "SELECT rj.*, v.plate_number, v.make, c.full_name AS customer_name
             FROM repair_jobs rj
             JOIN vehicles v  ON v.id = rj.vehicle_id
             JOIN customers c ON c.id = v.customer_id
             WHERE rj.mechanic_id = ?
             ORDER BY rj.created_at DESC"
        );
        $stmt->execute([$mechanicId]);
        $rows = $stmt->fetchAll();
        $rows = $this->enc->decryptRows($rows, $this->encryptedFields);
        $rows = $this->enc->decryptRows($rows, ['plate_number','make','customer_name']);
        return $rows;
    }

    // Search by status or description
    public function search(string $keyword): array {
        $all     = $this->getAll();
        $keyword = strtolower(trim($keyword));
        return array_filter($all, function ($row) use ($keyword) {
            return str_contains(strtolower($row['status']           ?? ''), $keyword)
                || str_contains(strtolower($row['job_description']  ?? ''), $keyword)
                || str_contains(strtolower($row['customer_name']    ?? ''), $keyword)
                || str_contains(strtolower($row['plate_number']     ?? ''), $keyword);
        });
    }

    public function getCountByStatus(): array {
        $all    = $this->getAll();
        $counts = ['pending' => 0, 'in-progress' => 0, 'completed' => 0, 'cancelled' => 0];
        foreach ($all as $job) {
            $status = strtolower($job['status'] ?? 'pending');
            if (isset($counts[$status])) $counts[$status]++;
        }
        return $counts;
    }

    public function getLastInsertId(): int {
        return (int) $this->db->lastInsertId();
    }
}


// ============================================================
// classes/Payment.php
// OOP Concepts: INHERITANCE
// ============================================================
class Payment extends BaseModel {
    protected string $table          = 'payments';
    protected array  $encryptedFields = ['amount','method','reference','notes','paid_at'];

    public function __construct() {
        parent::__construct();
    }

    public function getAll(): array {
        $stmt = $this->db->query(
            "SELECT p.*,
                    rj.id AS job_number,
                    v.plate_number,
                    c.full_name AS customer_name,
                    u.full_name AS recorded_by_name
             FROM payments p
             JOIN repair_jobs rj ON rj.id = p.job_id
             JOIN vehicles v     ON v.id  = rj.vehicle_id
             JOIN customers c    ON c.id  = v.customer_id
             LEFT JOIN users u   ON u.id  = p.recorded_by
             ORDER BY p.created_at DESC"
        );
        $rows = $stmt->fetchAll();
        $rows = $this->enc->decryptRows($rows, $this->encryptedFields);
        $rows = $this->enc->decryptRows($rows, ['plate_number','customer_name','recorded_by_name']);
        return $rows;
    }

    public function create(array $data): bool {
        $encrypted = $this->enc->encryptFields($data, $this->encryptedFields);
        $stmt = $this->db->prepare(
            "INSERT INTO payments (job_id, amount, method, reference, notes, paid_at, recorded_by)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        return $stmt->execute([
            $data['job_id'],
            $encrypted['amount']      ?? null,
            $encrypted['method']      ?? null,
            $encrypted['reference']   ?? null,
            $encrypted['notes']       ?? null,
            $encrypted['paid_at']     ?? $this->enc->encrypt(date('Y-m-d H:i:s')),
            $data['recorded_by']      ?? null,
        ]);
    }

    public function update(int $id, array $data): bool {
        $encrypted = $this->enc->encryptFields($data, $this->encryptedFields);
        $stmt = $this->db->prepare(
            "UPDATE payments SET amount=?, method=?, reference=?, notes=? WHERE id=?"
        );
        return $stmt->execute([
            $encrypted['amount']    ?? null,
            $encrypted['method']    ?? null,
            $encrypted['reference'] ?? null,
            $encrypted['notes']     ?? null,
            $id
        ]);
    }

    public function getTotalRevenue(): float {
        $all   = $this->getAll();
        $total = 0.0;
        foreach ($all as $p) {
            $total += (float) ($p['amount'] ?? 0);
        }
        return $total;
    }
}
