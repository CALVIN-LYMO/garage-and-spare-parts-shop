<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Service.php';
require_once __DIR__ . '/../classes/Product.php';
require_once __DIR__ . '/../includes/public_header.php';

$serviceModel = new Service();
$productModel = new Product();
$services = array_slice($serviceModel->getAll(), 0, 4);
$products = array_slice($productModel->getAvailable(), 0, 4);
?>

<section class="page-hero">
    <div class="hero-overlay"></div>
    <div class="hero-text">
        <h1>Fast, trusted mobile car service in Tanzania</h1>
        <p>From emergency breakdowns to routine maintenance, our certified mechanics come to you with genuine parts and transparent pricing.</p>
        <!-- hero actions removed per request -->
    </div>
</section>

<section class="section">
    <div class="section-header">
        <h2>Explore for customers</h2>
        <p>Quick links, tips, and featured items to help you get the most from our service.</p>
    </div>
    <div class="grid-cards">
        <article class="service-card">
            <div class="service-illustration">🔎</div>
            <h3>Find a mechanic nearby</h3>
            <p>Enter your location and we’ll connect you with certified mechanics available near you.</p>
            <a href="<?= BASE_URL ?>/pages/request_service.php" class="btn-sm">Request Now</a>
        </article>
        <article class="service-card">
            <div class="service-illustration">🛒</div>
            <h3>Shop spare parts</h3>
            <p>Browse high-quality parts with delivery to your doorstep.</p>
            <a href="<?= BASE_URL ?>/pages/shop.php" class="btn-sm">Shop</a>
        </article>
        <!-- 'Track service requests' card removed per request -->
        <article class="service-card">
            <div class="service-illustration">⭐</div>
            <h3>Top-rated mechanics</h3>
            <p>See feedback and ratings to choose the right mechanic for your needs.</p>
            <a href="<?= BASE_URL ?>/pages/services.php" class="btn-sm">See More</a>
        </article>
    </div>
</section>

<section class="section">
    <div class="section-header">
        <h2>Our services</h2>
        <p>Choose from convenient mobile repairs, diagnostics, and spare parts delivery.</p>
    </div>
    <div class="grid-cards">
        <article class="service-card">
            <div class="service-illustration">🚗</div>
            <h3>Mobile Diagnostics</h3>
            <p>We inspect your vehicle at your home, office, or roadside and give you a clear repair plan.</p>
        </article>
        <article class="service-card">
            <div class="service-illustration">🛠️</div>
            <h3>On-Site Repairs</h3>
            <p>Routine maintenance and minor repairs done quickly without you visiting the garage.</p>
        </article>
        <article class="service-card">
            <div class="service-illustration">🔋</div>
            <h3>Battery & Electrical</h3>
            <p>Fast support for battery, starter, alternator, wiring, and lighting issues.</p>
        </article>
        <article class="service-card">
            <div class="service-illustration">📦</div>
            <h3>Spare Parts Delivery</h3>
            <p>Order quality parts online and get them delivered straight to your location.</p>
        </article>
    </div>
</section>

<section class="section">
    <div class="section-header">
        <h2>Why choose TANZAMOTORS?</h2>
        <p>Fast, reliable, and convenient vehicle service straight to your home or office.</p>
    </div>
    <div class="grid-cards">
        <article class="service-card">
            <h3>Certified Mechanics</h3>
            <p>Skilled technicians with experience in diagnostics, maintenance, and repairs across many vehicle types.</p>
        </article>
        <article class="service-card">
            <h3>Easy Online Booking</h3>
            <p>Book a mechanic in minutes and track your request from submission to completion.</p>
        </article>
        <article class="service-card">
            <h3>Transparent Pricing</h3>
            <p>See service packages and parts prices clearly before you commit to a repair.</p>
        </article>
        <article class="service-card">
            <h3>Reliable Support</h3>
            <p>We focus on fast response times, clear communication, and dependable service quality.</p>
        </article>
    </div>
</section>

<section class="section">
    <div class="section-header">
        <h2>Popular Services</h2>
        <p>Choose one of our top mobile service packages.</p>
    </div>
    <div class="grid-cards">
        <?php foreach ($services as $service): ?>
        <article class="service-card">
            <h3><?= htmlspecialchars($service['service_name']) ?></h3>
            <p><?= htmlspecialchars($service['description'] ?? '') ?></p>
            <div class="service-meta">
                <span>From TZS <?= number_format((float)$service['base_price'], 0) ?></span>
                <a href="<?= BASE_URL ?>/pages/request_service.php" class="btn-sm">Book</a>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="section">
    <div class="section-header">
        <h2>Shop Preview</h2>
        <p>Discover spare parts ready for home delivery.</p>
    </div>
    <div class="grid-cards">
        <?php if (empty($products)): ?>
            <div class="empty-state">No spare parts are available at the moment.</div>
        <?php else: ?>
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
                    <a href="<?= BASE_URL ?>/pages/shop.php" class="btn-sm">View Product</a>
                </div>
            </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/public_footer.php'; ?>
