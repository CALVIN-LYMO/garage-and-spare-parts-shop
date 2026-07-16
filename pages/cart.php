<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Product.php';
require_once __DIR__ . '/../classes/Order.php';
require_once __DIR__ . '/../classes/Customer.php';
require_once __DIR__ . '/../includes/public_header.php';

$productModel  = new Product();
$orderModel    = new Order();
$customerModel = new Customer();

$cart    = $_SESSION['cart'] ?? [];
$products = [];
$total   = 0.00;
$errors  = [];

$loggedInCustomerId = isCustomer() ? currentUserId() : 0;
$loggedInCustomer   = $loggedInCustomerId ? $customerModel->findById($loggedInCustomerId) : null;
$customers          = $loggedInCustomerId ? [] : $customerModel->getAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['product_id']) && !isset($_POST['checkout']) && !isset($_POST['update_cart']) && !isset($_POST['remove_item'])) {
        $productId = (int)$_POST['product_id'];
        $quantity  = max(1, (int)($_POST['quantity'] ?? 1));
        $product   = $productModel->findById($productId);

        if (!$product) {
            $errors[] = 'Product not found.';
        } elseif ((int)($product['stock'] ?? 0) < $quantity) {
            $errors[] = 'Not enough stock for ' . ($product['name'] ?? 'this product') . '.';
        } else {
            if (!isset($cart[$productId])) {
                $cart[$productId] = 0;
            }
            $newQty = $cart[$productId] + $quantity;
            if ((int)$product['stock'] < $newQty) {
                $errors[] = 'Cannot add more — only ' . (int)$product['stock'] . ' in stock.';
            } else {
                $cart[$productId] = $newQty;
                $_SESSION['cart'] = $cart;
                $_SESSION['flash'] = ['message' => 'Product added to cart.'];
                header('Location: ' . BASE_URL . '/pages/cart.php');
                exit();
            }
        }
    }

    if (isset($_POST['remove_item'])) {
        $productId = (int)($_POST['product_id'] ?? 0);
        unset($cart[$productId]);
        $_SESSION['cart'] = $cart;
        $_SESSION['flash'] = ['message' => 'Item removed from cart.'];
        header('Location: ' . BASE_URL . '/pages/cart.php');
        exit();
    }

    if (isset($_POST['update_cart'])) {
        $quantities = $_POST['quantities'] ?? [];
        foreach ($quantities as $productId => $qty) {
            $productId = (int)$productId;
            $qty       = max(0, (int)$qty);
            if ($qty === 0) {
                unset($cart[$productId]);
                continue;
            }
            $product = $productModel->findById($productId);
            if ($product && (int)$product['stock'] >= $qty) {
                $cart[$productId] = $qty;
            } elseif ($product) {
                $cart[$productId] = (int)$product['stock'];
            }
        }
        $_SESSION['cart'] = $cart;
        $_SESSION['flash'] = ['message' => 'Cart updated.'];
        header('Location: ' . BASE_URL . '/pages/cart.php');
        exit();
    }

    if (isset($_POST['checkout'])) {
        $customerId = $loggedInCustomerId ?: (int)($_POST['customer_id'] ?? 0);

        if (!$customerId) {
            $errors[] = 'Customer must be selected for checkout.';
        }
        if (empty(trim($_POST['shipping_address'] ?? ''))) {
            $errors[] = 'Shipping address is required.';
        }
        if (empty($cart)) {
            $errors[] = 'Your cart is empty.';
        }

        if (!$errors) {
            $items = [];
            foreach ($cart as $productId => $quantity) {
                $product = $productModel->findById($productId);
                if (!$product) {
                    continue;
                }
                $items[] = [
                    'product_id' => $productId,
                    'quantity'   => $quantity,
                    'unit_price' => (float)$product['price'],
                ];
            }

            if (empty($items)) {
                $errors[] = 'No valid products were found in your cart.';
            } else {
                $orderData = [
                    'customer_id'      => $customerId,
                    'total_amount'     => 0,
                    'payment_status'   => 'pending',
                    'shipping_address' => trim($_POST['shipping_address']),
                    'notes'            => trim($_POST['notes'] ?? ''),
                ];

                $result = $orderModel->createWithItems($orderData, $items);
                if ($result === true) {
                    $_SESSION['cart'] = [];
                    $_SESSION['flash'] = ['message' => 'Order placed successfully! We will contact you for payment and delivery.'];
                    if (isCustomer()) {
                        header('Location: ' . BASE_URL . '/pages/customer_dashboard.php');
                    } else {
                        header('Location: ' . BASE_URL . '/pages/cart.php');
                    }
                    exit();
                }

                $errors[] = is_string($result) ? $result : 'Unable to place the order. Please try again.';
            }
        }
    }
}

if (!empty($cart)) {
    $allProducts = $productModel->getAll();
    foreach ($allProducts as $product) {
        if (isset($cart[$product['id']])) {
            $quantity  = $cart[$product['id']];
            $lineTotal = $quantity * (float)$product['price'];
            $products[] = array_merge($product, [
                'quantity'  => $quantity,
                'line_total' => $lineTotal,
            ]);
            $total += $lineTotal;
        }
    }
}

$defaultAddress = $loggedInCustomer['address'] ?? '';
?>

<section class="section">
    <div class="section-header">
        <h2>Shopping Cart</h2>
        <p>Review your selected spare parts and proceed to checkout.</p>
    </div>

    <?php if ($errors): ?>
        <div class="alert alert-danger"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
    <?php endif; ?>

    <div class="card">
        <form method="POST" action="">
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Qty</th>
                            <th>Total</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr><td colspan="5" class="empty">Your cart is empty. <a href="<?= BASE_URL ?>/pages/shop.php">Browse the shop</a></td></tr>
                        <?php else: ?>
                            <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?= htmlspecialchars($product['name']) ?></td>
                                <td>TZS <?= number_format((float)$product['price'], 0) ?></td>
                                <td>
                                    <input type="number" name="quantities[<?= $product['id'] ?>]"
                                           value="<?= (int)$product['quantity'] ?>" min="1"
                                           max="<?= (int)$product['stock'] ?>" style="width:70px;">
                                </td>
                                <td>TZS <?= number_format($product['line_total'], 0) ?></td>
                                <td>
                                    <button type="submit" name="remove_item" value="1"
                                            formaction="" class="btn-sm btn-delete"
                                            onclick="this.form.product_id.value=<?= $product['id'] ?>">Remove</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <tr>
                                <td colspan="3" style="text-align:right;"><strong>Cart Total</strong></td>
                                <td colspan="2"><strong>TZS <?= number_format($total, 0) ?></strong></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <input type="hidden" name="product_id" value="">
            <?php if (!empty($products)): ?>
            <div class="form-actions" style="margin-top:12px;">
                <button type="submit" name="update_cart" class="btn-secondary">Update Quantities</button>
            </div>
            <?php endif; ?>
        </form>
    </div>

    <?php if (!empty($products)): ?>
    <div class="card form-card">
        <h3>Checkout</h3>
        <?php if ($loggedInCustomer): ?>
            <p>Ordering as: <strong><?= htmlspecialchars($loggedInCustomer['full_name'] ?? '') ?></strong></p>
        <?php endif; ?>
        <form method="POST" action="">
            <?php if (!$loggedInCustomerId): ?>
            <div class="form-group">
                <label>Customer Profile *</label>
                <select name="customer_id" required>
                    <option value="">-- Select Customer --</option>
                    <?php foreach ($customers as $customer): ?>
                        <option value="<?= $customer['id'] ?>"><?= htmlspecialchars($customer['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <small>Or <a href="<?= BASE_URL ?>/auth/customer_login.php">login</a> to checkout faster.</small>
            </div>
            <?php endif; ?>
            <div class="form-group">
                <label>Shipping Address *</label>
                <textarea name="shipping_address" required placeholder="Full delivery address including area and phone contact"><?= htmlspecialchars($defaultAddress) ?></textarea>
            </div>
            <div class="form-group">
                <label>Order Notes</label>
                <textarea name="notes" placeholder="Any special delivery instructions..."></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" name="checkout" class="btn-primary">Place Order (TZS <?= number_format($total, 0) ?>)</button>
                <a href="<?= BASE_URL ?>/pages/shop.php" class="btn-secondary">Continue Shopping</a>
            </div>
        </form>
    </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../includes/public_footer.php'; ?>
