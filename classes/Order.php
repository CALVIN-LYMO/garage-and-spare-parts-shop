<?php
require_once __DIR__ . '/BaseModel.php';

class Order extends BaseModel {
    protected string $table = 'orders';
    protected array $encryptedFields = ['shipping_address', 'notes'];

    public function __construct() {
        parent::__construct();
    }

    public function getAll(): array {
        $stmt = $this->db->query(
            "SELECT o.*, c.full_name AS customer_name,
                    (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS item_count
             FROM orders o
             JOIN customers c ON c.id = o.customer_id
             ORDER BY o.created_at DESC"
        );

        $rows = $stmt->fetchAll();
        $rows = $this->enc->decryptRows($rows, $this->encryptedFields);
        return $this->enc->decryptRows($rows, ['customer_name']);
    }

    public function getByCustomer(int $customerId): array {
        $stmt = $this->db->prepare(
            "SELECT o.*, c.full_name AS customer_name,
                    (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS item_count
             FROM orders o
             JOIN customers c ON c.id = o.customer_id
             WHERE o.customer_id = ?
             ORDER BY o.created_at DESC"
        );
        $stmt->execute([$customerId]);

        $rows = $stmt->fetchAll();
        $rows = $this->enc->decryptRows($rows, $this->encryptedFields);
        return $this->enc->decryptRows($rows, ['customer_name']);
    }

    public function create(array $data): bool {
        $encrypted = $this->enc->encryptFields($data, $this->encryptedFields);

        $stmt = $this->db->prepare(
            "INSERT INTO orders (customer_id, total_amount, payment_status, shipping_address, notes)
             VALUES (?, ?, ?, ?, ?)"
        );

        return $stmt->execute([
            $data['customer_id'],
            $data['total_amount'] ?? 0.00,
            $data['payment_status'] ?? 'pending',
            $encrypted['shipping_address'] ?? null,
            $encrypted['notes'] ?? null,
        ]);
    }

    public function createWithItems(array $data, array $items, ?Product $productModel = null): bool|string {
        if (empty($items)) {
            return 'No items in order.';
        }

        $productModel ??= new Product();

        foreach ($items as $item) {
            $productId = (int)($item['product_id'] ?? 0);
            $quantity  = max(1, (int)($item['quantity'] ?? 1));
            $product   = $productModel->findById($productId);

            if (!$product) {
                return 'One or more products in your cart are no longer available.';
            }
            if ((int)($product['stock'] ?? 0) < $quantity) {
                return 'Insufficient stock for ' . ($product['name'] ?? 'a product') . '.';
            }
        }

        try {
            $this->db->beginTransaction();

            if (!$this->create($data)) {
                throw new Exception('Unable to create order.');
            }

            $orderId = $this->getLastInsertId();
            $stmt = $this->db->prepare(
                "INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price)
                 VALUES (?, ?, ?, ?, ?)"
            );

            $totalAmount = 0.00;
            foreach ($items as $item) {
                $productId = (int)$item['product_id'];
                $quantity  = max(1, (int)($item['quantity'] ?? 1));
                $unitPrice = (float)($item['unit_price'] ?? 0.00);
                $totalPrice = $quantity * $unitPrice;
                $totalAmount += $totalPrice;

                if (!$stmt->execute([
                    $orderId,
                    $productId,
                    $quantity,
                    $unitPrice,
                    $totalPrice,
                ])) {
                    throw new Exception('Unable to create order item.');
                }

                if (!$productModel->decrementStock($productId, $quantity)) {
                    throw new Exception('Unable to update product stock.');
                }
            }

            $updateStmt = $this->db->prepare("UPDATE orders SET total_amount = ? WHERE id = ?");
            if (!$updateStmt->execute([$totalAmount, $orderId])) {
                throw new Exception('Unable to update order total.');
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function getItems(int $orderId): array {
        $stmt = $this->db->prepare(
            "SELECT oi.*, p.name AS product_name
             FROM order_items oi
             JOIN products p ON p.id = oi.product_id
             WHERE oi.order_id = ?
             ORDER BY oi.id"
        );
        $stmt->execute([$orderId]);
        $rows = $stmt->fetchAll();
        return (new Encryption())->decryptRows($rows, ['product_name']);
    }

    public function findWithDetails(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT o.*, c.full_name AS customer_name, c.phone AS customer_phone
             FROM orders o
             JOIN customers c ON c.id = o.customer_id
             WHERE o.id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $row = $this->enc->decryptFields($row, $this->encryptedFields);
        $row = $this->enc->decryptFields($row, ['customer_name', 'customer_phone']);
        $row['items'] = $this->getItems($id);
        return $row;
    }

    public function updatePaymentStatus(int $id, string $status): bool {
        if (!in_array($status, ['pending', 'paid', 'failed'], true)) {
            return false;
        }
        $stmt = $this->db->prepare("UPDATE orders SET payment_status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    public function update(int $id, array $data): bool {
        $encrypted = $this->enc->encryptFields($data, $this->encryptedFields);

        $stmt = $this->db->prepare(
            "UPDATE orders
             SET total_amount = ?, payment_status = ?, shipping_address = ?, notes = ?
             WHERE id = ?"
        );

        return $stmt->execute([
            $data['total_amount'] ?? 0.00,
            $data['payment_status'] ?? 'pending',
            $encrypted['shipping_address'] ?? null,
            $encrypted['notes'] ?? null,
            $id,
        ]);
    }

    public function getTotalRevenue(): float {
        $stmt = $this->db->query("SELECT COALESCE(SUM(total_amount), 0) AS total FROM orders WHERE payment_status = 'paid'");
        return (float) $stmt->fetchColumn();
    }

    public function getLastInsertId(): int {
        return (int) $this->db->lastInsertId();
    }
}
