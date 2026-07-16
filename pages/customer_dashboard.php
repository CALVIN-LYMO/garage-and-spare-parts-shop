<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Customer.php';
require_once __DIR__ . '/../classes/ServiceRequest.php';
require_once __DIR__ . '/../classes/Order.php';
require_once __DIR__ . '/../includes/header.php';

requireCustomer();

$customerId = currentUserId();
$customerModel = new Customer();
$requestModel = new ServiceRequest();
$orderModel = new Order();

$customer = $customerModel->findById($customerId);
$serviceRequests = $requestModel->getByCustomer($customerId);
$orders = $orderModel->getByCustomer($customerId);

$viewOrderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$viewOrder = null;
if ($viewOrderId) {
    $candidate = $orderModel->findWithDetails($viewOrderId);
    if ($candidate && (int)($candidate['customer_id'] ?? 0) === $customerId) {
        $viewOrder = $candidate;
    }
}
?>

<div class="page-header">
    <h2>👤 My Account</h2>
</div>

<div class="card">
    <h3>Welcome, <?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Customer') ?></h3>
    <p><strong>Name:</strong> <?= htmlspecialchars($customer['full_name'] ?? '') ?></p>
    <p><strong>Phone:</strong> <?= htmlspecialchars($customer['phone'] ?? '') ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($customer['email'] ?? '') ?></p>
    <p><strong>Address:</strong> <?= htmlspecialchars($customer['address'] ?? '') ?></p>
    <p>
        <a href="<?= BASE_URL ?>/pages/request_service.php" class="btn-primary">Request a Mobile Mechanic</a>
        <a href="<?= BASE_URL ?>/pages/shop.php" class="btn-secondary">Browse Spare Parts</a>
        <a href="<?= BASE_URL ?>/pages/cart.php" class="btn-secondary">View Cart</a>
    </p>
</div>

<?php if ($viewOrder): ?>
<div class="card">
    <div class="card-header">
        <h3>Order #<?= (int)$viewOrder['id'] ?> Details</h3>
        <a href="<?= BASE_URL ?>/pages/customer_dashboard.php" class="btn-sm">← Back</a>
    </div>
    <p><strong>Date:</strong> <?= htmlspecialchars($viewOrder['created_at'] ?? '') ?></p>
    <p><strong>Status:</strong> <?= ucfirst(htmlspecialchars($viewOrder['payment_status'] ?? 'pending')) ?></p>
    <p><strong>Shipping:</strong> <?= nl2br(htmlspecialchars($viewOrder['shipping_address'] ?? '')) ?></p>
    <?php if (!empty($viewOrder['notes'])): ?>
    <p><strong>Notes:</strong> <?= nl2br(htmlspecialchars($viewOrder['notes'])) ?></p>
    <?php endif; ?>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr><th>Product</th><th>Price</th><th>Qty</th><th>Total</th></tr>
            </thead>
            <tbody>
                <?php foreach ($viewOrder['items'] ?? [] as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['product_name'] ?? '') ?></td>
                    <td>TZS <?= number_format((float)($item['unit_price'] ?? 0), 0) ?></td>
                    <td><?= (int)($item['quantity'] ?? 0) ?></td>
                    <td>TZS <?= number_format((float)($item['total_price'] ?? 0), 0) ?></td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="3" style="text-align:right;"><strong>Total</strong></td>
                    <td><strong>TZS <?= number_format((float)$viewOrder['total_amount'], 0) ?></strong></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3>My Service Requests</h3>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Service</th>
                    <th>Issue</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th>Assigned Mechanic</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($serviceRequests)): ?>
                <tr><td colspan="6" class="empty">No service requests found.</td></tr>
                <?php else: ?>
                    <?php foreach ($serviceRequests as $req): ?>
                    <tr>
                        <td><?= htmlspecialchars($req['id']) ?></td>
                        <td><?= htmlspecialchars($req['service_name'] ?? 'General Service') ?></td>
                        <td><?= htmlspecialchars(substr($req['issue_description'] ?? '', 0, 40)) ?>...</td>
                        <td><?= htmlspecialchars($req['location'] ?? '') ?></td>
                        <td><span class="badge badge-<?= htmlspecialchars(str_replace(' ', '-', strtolower($req['status'] ?? 'pending'))) ?>">
                            <?= htmlspecialchars(ucfirst($req['status'] ?? 'pending')) ?>
                        </span></td>
                        <td><?= htmlspecialchars($req['mechanic_name'] ?? 'Unassigned') ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>My Orders</h3>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Items</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                <tr><td colspan="6" class="empty">No orders found. <a href="<?= BASE_URL ?>/pages/shop.php">Shop now</a></td></tr>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?= htmlspecialchars($order['id']) ?></td>
                        <td>TZS <?= number_format((float)$order['total_amount'], 0) ?></td>
                        <td><?= htmlspecialchars(ucfirst($order['payment_status'] ?? 'pending')) ?></td>
                        <td><?= htmlspecialchars($order['item_count'] ?? 0) ?></td>
                        <td><?= htmlspecialchars($order['created_at'] ?? '') ?></td>
                        <td><a href="?order_id=<?= $order['id'] ?>" class="btn-sm btn-edit">View</a></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
