<?php
// pages/dashboard.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Customer.php';
require_once __DIR__ . '/../classes/RepairJob.php';
require_once __DIR__ . '/../classes/ServiceRequest.php';
require_once __DIR__ . '/../classes/Order.php';
require_once __DIR__ . '/../includes/header.php';

$customerModel = new Customer();
$jobModel      = new RepairJob();
$userModel     = new User();
$serviceRequestModel = new ServiceRequest();
$paymentModel  = new Payment();
$orderModel    = new Order();

$totalCustomers = $customerModel->count();
$totalJobs      = $jobModel->count();
$jobsByStatus   = $jobModel->getCountByStatus();
$totalRevenue   = $paymentModel->getTotalRevenue();
$totalOrders    = isAdmin() ? $orderModel->count() : 0;
$orderRevenue   = isAdmin() ? $orderModel->getTotalRevenue() : 0;

// Mechanics see only their own jobs
$recentJobs = isAdmin()
    ? array_slice($jobModel->getAll(), 0, 5)
    : (isMechanic() ? array_slice($jobModel->getByMechanic(currentUserId()), 0, 5) : []);

$assignedServiceRequests = isMechanic()
    ? $serviceRequestModel->getByMechanic(currentUserId())
    : [];
?>

<div class="page-header">
    <h2>Dashboard</h2>
    <p>Welcome back, <strong><?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']) ?></strong></p>
</div>

<!-- Stats Cards -->
<div class="stats-grid">
    <div class="stat-card stat-blue">
        <div class="stat-icon">👥</div>
        <div class="stat-info">
            <h3><?= $totalCustomers ?></h3>
            <p>Total Customers</p>
        </div>
    </div>
    <div class="stat-card stat-orange">
        <div class="stat-icon">🔧</div>
        <div class="stat-info">
            <h3><?= $totalJobs ?></h3>
            <p>Total Jobs</p>
        </div>
    </div>
    <div class="stat-card stat-yellow">
        <div class="stat-icon">⏳</div>
        <div class="stat-info">
            <h3><?= $jobsByStatus['pending'] + $jobsByStatus['in-progress'] ?></h3>
            <p>Active Jobs</p>
        </div>
    </div>
    <?php if (isAdmin()): ?>
    <div class="stat-card stat-green">
        <div class="stat-icon">💰</div>
        <div class="stat-info">
            <h3>TZS <?= number_format($totalRevenue, 0) ?></h3>
            <p>Repair Revenue</p>
        </div>
    </div>
    <div class="stat-card stat-blue">
        <div class="stat-icon">📦</div>
        <div class="stat-info">
            <h3><?= $totalOrders ?></h3>
            <p>Shop Orders</p>
        </div>
    </div>
    <div class="stat-card stat-green">
        <div class="stat-icon">🛒</div>
        <div class="stat-info">
            <h3>TZS <?= number_format($orderRevenue, 0) ?></h3>
            <p>Shop Revenue (Paid)</p>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Job Status Breakdown -->
<div class="card">
    <div class="card-header">
        <h3>Jobs by Status</h3>
    </div>
    <div class="status-grid">
        <?php foreach ($jobsByStatus as $status => $count): ?>
        <div class="status-item status-<?= $status ?>">
            <span class="status-count"><?= $count ?></span>
            <span class="status-label"><?= ucfirst($status) ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Recent Jobs -->
<div class="card">
    <div class="card-header">
        <h3><?= isMechanic() ? 'My Recent Jobs' : 'Recent Repair Jobs' ?></h3>
        <a href="<?= BASE_URL ?>/pages/jobs.php" class="btn-sm">View All</a>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Customer</th>
                    <th>Vehicle</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentJobs)): ?>
                <tr><td colspan="6" class="empty">No repair jobs found.</td></tr>
                <?php else: ?>
                <?php foreach ($recentJobs as $job): ?>
                <tr>
                    <td><?= $job['id'] ?></td>
                    <td><?= htmlspecialchars($job['customer_name'] ?? '') ?></td>
                    <td><?= htmlspecialchars($job['make'] ?? '') ?> <?= htmlspecialchars($job['model'] ?? '') ?>
                        <small>(<?= htmlspecialchars($job['plate_number'] ?? '') ?>)</small>
                    </td>
                    <td><?= htmlspecialchars(substr($job['job_description'] ?? '', 0, 50)) ?>...</td>
                    <td><span class="badge badge-<?= htmlspecialchars($job['status'] ?? '') ?>">
                        <?= htmlspecialchars(ucfirst($job['status'] ?? '')) ?>
                    </span></td>
                    <td><?= htmlspecialchars($job['date_received'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (isMechanic()): ?>
<div class="card">
    <div class="card-header">
        <h3>Assigned Service Requests</h3>
        <span class="badge"><?= count($assignedServiceRequests) ?> requests</span>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Customer</th>
                    <th>Service</th>
                    <th>Issue</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th>Preferred Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($assignedServiceRequests)): ?>
                <tr><td colspan="7" class="empty">No assigned service requests yet.</td></tr>
                <?php else: ?>
                <?php foreach ($assignedServiceRequests as $req): ?>
                <tr>
                    <td><?= htmlspecialchars($req['id']) ?></td>
                    <td><?= htmlspecialchars($req['customer_name'] ?? '') ?></td>
                    <td><?= htmlspecialchars($req['service_name'] ?? 'General') ?></td>
                    <td><?= htmlspecialchars(substr($req['issue_description'] ?? '', 0, 40)) ?>...</td>
                    <td><?= htmlspecialchars($req['location'] ?? '') ?></td>
                    <td><span class="badge badge-<?= htmlspecialchars(str_replace(' ', '-', strtolower($req['status'] ?? 'pending'))) ?>">
                        <?= htmlspecialchars(ucfirst($req['status'] ?? 'pending')) ?>
                    </span></td>
                    <td><?= htmlspecialchars($req['preferred_date'] ?? 'N/A') ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
