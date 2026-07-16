<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Product.php';
require_once __DIR__ . '/../classes/Category.php';
require_once __DIR__ . '/../includes/public_header.php';

$productModel = new Product();
$categoryModel = new Category();
$products = $productModel->getAll();
$categories = $categoryModel->getAll();

$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
if ($categoryId) {
    $products = $productModel->getByCategory($categoryId);
}
?>

<section class="page-hero">
    <div class="hero-text">
        <h1>Spare Parts Store</h1>
        <p>Browse quality parts, add to cart, and checkout for fast delivery.</p>
        <a href="<?= BASE_URL ?>/pages/cart.php" class="btn-primary">View Cart</a>
    </div>
</section>

<section class="section">
    <div class="section-header">
        <h2>Shop Categories</h2>
        <div class="category-list">
            <a href="<?= BASE_URL ?>/pages/shop.php" class="category-pill<?= !$categoryId ? ' active' : '' ?>">All</a>
            <?php foreach ($categories as $category): ?>
                <a href="<?= BASE_URL ?>/pages/shop.php?category=<?= $category['id'] ?>" class="category-pill<?= $categoryId === $category['id'] ? ' active' : '' ?>">
                    <?= htmlspecialchars($category['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="grid-cards">
        <?php if (empty($products)): ?>
            <div class="empty-state">No products found.</div>
        <?php endif; ?>
        <?php foreach ($products as $product): ?>
        <article class="product-card">
            <?php if (!empty($product['image_path'])): ?>
                <img src="<?= htmlspecialchars($product['image_path']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
            <?php else: ?>
                <div class="product-image-placeholder">No image</div>
            <?php endif; ?>
            <div class="product-body">
                <h3><?= htmlspecialchars($product['name']) ?></h3>
                <p><?= htmlspecialchars($product['description'] ?? '') ?></p>
                <div class="product-meta">
                    <span>TZS <?= number_format((float)$product['price'], 0) ?></span>
                    <span class="stock">Stock: <?= (int)$product['stock'] ?></span>
                </div>
                <form method="POST" action="<?= BASE_URL ?>/pages/cart.php">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    <input type="hidden" name="quantity" value="1">
                    <button type="submit" class="btn-primary"<?= (int)$product['stock'] === 0 ? ' disabled' : '' ?>>Add to Cart</button>
                </form>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/public_footer.php'; ?>
