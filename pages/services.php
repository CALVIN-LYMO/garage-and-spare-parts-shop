<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Service.php';
require_once __DIR__ . '/../includes/public_header.php';

$serviceModel = new Service();
$services = $serviceModel->getAll();
?>

<section class="page-hero">
    <div class="hero-text">
        <h1>Mobile Garage Service for Your Vehicle</h1>
        <p>From diagnostics to repairs, our mechanics come to your location and fix your car fast.</p>
        <a href="<?= BASE_URL ?>/pages/request_service.php" class="btn-primary">Request a Mechanic</a>
    </div>
</section>

<section class="section">
    <div class="section-header">
        <h2>Our Services</h2>
        <p>Choose from trusted repair services designed for convenience and reliability.</p>
    </div>
    <div class="grid-cards">
        <?php foreach ($services as $service): ?>
        <article class="service-card">
            <h3><?= htmlspecialchars($service['service_name'] ?? '') ?></h3>
            <p><?= htmlspecialchars($service['description'] ?? '') ?></p>
            <div class="service-meta">
                <span>From TZS <?= number_format((float)($service['base_price'] ?? 0), 0) ?></span>
                <a href="<?= BASE_URL ?>/pages/request_service.php" class="btn-sm">Request Now</a>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/public_footer.php'; ?>
