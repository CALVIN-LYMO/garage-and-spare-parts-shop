<?php
// pages/orders.php — Admin: Manage Spare Parts Orders
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Order.php';
require_once __DIR__ . '/../classes/Customer.php';
require_once __DIR__ . '/../includes/header.php';

requireAdmin();

$orderModel    = new Order();
$customerModel = new Customer();
$action        = $_GET['action'] ?? 'list';
$id            = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$errors        = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'update_status' && $id) {
        $status = $_POST['payment_status'] ?? 'pending';
        if (!in_array($status, ['pending', 'paid', 'failed'], true)) {
            $errors[] = 'Invalid payment status.';
        } elseif ($orderModel->updatePaymentStatus($id, $status)) {
            redirectWith(BASE_URL . '/pages/orders.php?action=view&id=' . $id, 'success', 'Payment status updated.');
        } else {
            $errors[] = 'Failed to update payment status.';
        }
    }

    if ($postAction === 'update' && $id) {
        if (empty($_POST['shipping_address'])) {
            $errors[] = 'Shipping address is required.';
        }
        if (empty($errors)) {
            $existing = $orderModel->findWithDetails($id);
            $data = [
                'total_amount'     => (float)($existing['total_amount'] ?? 0),
                'payment_status'   => $_POST['payment_status'] ?? 'pending',
                'shipping_address' => trim($_POST['shipping_address']),
                'notes'            => trim($_POST['notes'] ?? ''),
            ];
            if ($orderModel->update($id, $data)) {
                redirectWith(BASE_URL . '/pages/orders.php?action=view&id=' . $id, 'success', 'Order updated successfully.');
            }
            $errors[] = 'Failed to update order.';
        }
    }
}

if ($action === 'delete' && $id) {
    $orderModel->delete($id);
    redirectWith(BASE_URL . '/pages/orders.php', 'success', 'Order deleted.');
}

$statusFilter = $_GET['status'] ?? '';
$allOrders    = $orderModel->getAll();

if ($statusFilter && in_array($statusFilter, ['pending', 'paid', 'failed'], true)) {
    $orders = array_filter($allOrders, fn($o) => ($o['payment_status'] ?? '') === $statusFilter);
} else {
    $orders = $allOrders;
}

$totalOrders   = count($allOrders);
$pendingCount  = count(array_filter($allOrders, fn($o) => ($o['payment_status'] ?? '') === 'pending'));
$paidCount     = count(array_filter($allOrders, fn($o) => ($o['payment_status'] ?? '') === 'paid'));
$orderRevenue  = $orderModel->getTotalRevenue();
$viewOrder     = ($action === 'view' && $id) ? $orderModel->findWithDetails($id) : null;

if ($action === 'view' && $id && !$viewOrder) {
    redirectWith(BASE_URL . '/pages/orders.php', 'danger', 'Order not found.');
}

$statusBadge = [
    'pending' => 'badge-pending',
    'paid'    => 'badge-completed',
    'failed'  => 'badge-cancelled',
];
?>

<div class="page-header">
    <h2>📦 Spare Parts Orders</h2>
    <a href="<?= BASE_URL ?>/pages/shop.php" class="btn-secondary" target="_blank">View Shop</a>
</div>

<?php if ($action === 'view' && $viewOrder): ?>

<div class="page-header">
    <h2>Order #<?= (int)$viewOrder['id'] ?></h2>
    <a href="<?= BASE_URL ?>/pages/orders.php" class="btn-secondary">← Back to Orders</a>
</div>

<?php if ($errors): ?>
    <div class="alert alert-danger"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
<?php endif; ?>

<div class="stats-grid" style="margin-bottom:20px;">
    <div class="stat-card stat-blue">
        <div class="stat-icon">💰</div>
        <div class="stat-info">
            <h3>TZS <?= number_format((float)$viewOrder['total_amount'], 0) ?></h3>
            <p>Order Total</p>
        </div>
    </div>
    <div class="stat-card stat-<?= ($viewOrder['payment_status'] ?? '') === 'paid' ? 'green' : 'yellow' ?>">
        <div class="stat-icon">💳</div>
        <div class="stat-info">
            <h3><?= ucfirst(htmlspecialchars($viewOrder['payment_status'] ?? 'pending')) ?></h3>
            <p>Payment Status</p>
        </div>
    </div>
    <div class="stat-card stat-orange">
        <div class="stat-icon">📅</div>
        <div class="stat-info">
            <h3><?= htmlspecialchars(date('d M Y', strtotime($viewOrder['created_at'] ?? 'now'))) ?></h3>
            <p>Order Date</p>
        </div>
    </div>
</div>

<div class="report-grid">
    <div class="card">
        <div class="card-header"><h3>Customer Details</h3></div>
        <p><strong>Name:</strong> <?= htmlspecialchars($viewOrder['customer_name'] ?? '') ?></p>
        <p><strong>Phone:</strong> <?= htmlspecialchars($viewOrder['customer_phone'] ?? '—') ?></p>
        <p><strong>Shipping Address:</strong><br><?= nl2br(htmlspecialchars($viewOrder['shipping_address'] ?? '—')) ?></p>
        <?php if (!empty($viewOrder['notes'])): ?>
        <p><strong>Notes:</strong><br><?= nl2br(htmlspecialchars($viewOrder['notes'])) ?></p>
        <?php endif; ?>
    </div>

    <div class="card form-card">
        <h3>Update Order</h3>
        <form method="POST" action="?action=view&id=<?= $id ?>">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="update">
            <div class="form-group">
                <label>Payment Status</label>
                <select name="payment_status">
                    <?php foreach (['pending', 'paid', 'failed'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($viewOrder['payment_status'] ?? '') === $s ? 'selected' : '' ?>>
                        <?= ucfirst($s) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Shipping Address *</label>
                <textarea name="shipping_address" rows="3" required><?= htmlspecialchars($viewOrder['shipping_address'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>Notes</label>
                <textarea name="notes" rows="2"><?= htmlspecialchars($viewOrder['notes'] ?? '') ?></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-primary">Save Changes</button>
            </div>
        </form>
        <form method="POST" action="?action=view&id=<?= $id ?>" style="margin-top:12px;">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="payment_status" value="paid">
            <?php if (($viewOrder['payment_status'] ?? '') !== 'paid'): ?>
            <button type="submit" class="btn-primary" onclick="return confirm('Mark this order as paid?')">✓ Mark as Paid</button>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Order Items</h3>
        <span class="badge"><?= count($viewOrder['items'] ?? []) ?> items</span>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th>Unit Price</th>
                    <th>Qty</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($viewOrder['items'])): ?>
                <tr><td colspan="5" class="empty">No items in this order.</td></tr>
                <?php else: ?>
                <?php foreach ($viewOrder['items'] as $item): ?>
                <tr>
                    <td><?= (int)$item['id'] ?></td>
                    <td><?= htmlspecialchars($item['product_name'] ?? '') ?></td>
                    <td>TZS <?= number_format((float)($item['unit_price'] ?? 0), 0) ?></td>
                    <td><?= (int)($item['quantity'] ?? 0) ?></td>
                    <td><strong>TZS <?= number_format((float)($item['total_price'] ?? 0), 0) ?></strong></td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="4" style="text-align:right;"><strong>Grand Total</strong></td>
                    <td><strong>TZS <?= number_format((float)$viewOrder['total_amount'], 0) ?></strong></td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php else: ?>

<div class="stats-grid" style="margin-bottom:20px;">
    <div class="stat-card stat-blue">
        <div class="stat-icon">📦</div>
        <div class="stat-info">
            <h3><?= $totalOrders ?></h3>
            <p>Total Orders</p>
        </div>
    </div>
    <div class="stat-card stat-yellow">
        <div class="stat-icon">⏳</div>
        <div class="stat-info">
            <h3><?= $pendingCount ?></h3>
            <p>Pending Payment</p>
        </div>
    </div>
    <div class="stat-card stat-green">
        <div class="stat-icon">✅</div>
        <div class="stat-info">
            <h3><?= $paidCount ?></h3>
            <p>Paid Orders</p>
        </div>
    </div>
    <div class="stat-card stat-green">
        <div class="stat-icon">💰</div>
        <div class="stat-info">
            <h3>TZS <?= number_format($orderRevenue, 0) ?></h3>
            <p>Shop Revenue (Paid)</p>
        </div>
    </div>
</div>

<div class="card">
    <form method="GET" action="" class="search-form">
        <select name="status">
            <option value="">All statuses</option>
            <?php foreach (['pending', 'paid', 'failed'] as $s): ?>
            <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-primary">Filter</button>
        <?php if ($statusFilter): ?>
        <a href="<?= BASE_URL ?>/pages/orders.php" class="btn-secondary">Clear</a>
        <?php endif; ?>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h3>All Orders <?= $statusFilter ? '(' . ucfirst($statusFilter) . ')' : '' ?></h3>
        <span class="badge"><?= count($orders) ?> records</span>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Customer</th>
                    <th>Items</th>
                    <th>Total (TZS)</th>
                    <th>Payment</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                <tr><td colspan="7" class="empty">No orders found. Customers can place orders via the Shop → Cart.</td></tr>
                <?php else: ?>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?= (int)$order['id'] ?></td>
                    <td><?= htmlspecialchars($order['customer_name'] ?? '') ?></td>
                    <td><?= (int)($order['item_count'] ?? 0) ?></td>
                    <td><strong><?= number_format((float)($order['total_amount'] ?? 0), 0) ?></strong></td>
                    <td>
                        <span class="badge <?= $statusBadge[$order['payment_status'] ?? 'pending'] ?? 'badge-pending' ?>">
                            <?= ucfirst(htmlspecialchars($order['payment_status'] ?? 'pending')) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($order['created_at'] ?? '') ?></td>
                    <td class="actions">
                        <a href="?action=view&id=<?= $order['id'] ?>" class="btn-sm btn-edit">View</a>
                        <a href="?action=delete&id=<?= $order['id'] ?>" class="btn-sm btn-delete"
                           onclick="return confirm('Delete order #<?= $order['id'] ?>? Stock will NOT be restored.')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
