<?php
// pages/reports.php — Admin Reports
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/RepairJob.php';
require_once __DIR__ . '/../classes/Customer.php';
require_once __DIR__ . '/../classes/Order.php';
require_once __DIR__ . '/../includes/header.php';

requireAdmin();

$jobModel     = new RepairJob();
$paymentModel = new Payment();
$customerModel= new Customer();
$orderModel   = new Order();

$allJobs      = $jobModel->getAll();
$allPayments  = $paymentModel->getAll();
$allOrders    = $orderModel->getAll();
$statusCounts = $jobModel->getCountByStatus();
$totalRevenue = $paymentModel->getTotalRevenue();
$orderRevenue = $orderModel->getTotalRevenue();
$paidOrders   = count(array_filter($allOrders, fn($o) => ($o['payment_status'] ?? '') === 'paid'));

// Monthly revenue summary
$monthlyRevenue = [];
foreach ($allPayments as $p) {
    $month = substr($p['paid_at'] ?? date('Y-m'), 0, 7);
    if (!isset($monthlyRevenue[$month])) $monthlyRevenue[$month] = 0;
    $monthlyRevenue[$month] += (float)($p['amount'] ?? 0);
}
krsort($monthlyRevenue);

// Top mechanics by jobs
$mechanicJobs = [];
foreach ($allJobs as $job) {
    $name = $job['mechanic_name'] ?? 'Unassigned';
    if (!isset($mechanicJobs[$name])) $mechanicJobs[$name] = 0;
    $mechanicJobs[$name]++;
}
arsort($mechanicJobs);
?>

<div class="page-header">
    <h2>📊 Reports</h2>
    <button onclick="window.print()" class="btn-secondary">🖨 Print Report</button>
</div>

<!-- Summary Cards -->
<div class="stats-grid">
    <div class="stat-card stat-blue">
        <div class="stat-icon">📋</div>
        <div class="stat-info">
            <h3><?= count($allJobs) ?></h3>
            <p>Total Repair Jobs</p>
        </div>
    </div>
    <div class="stat-card stat-green">
        <div class="stat-icon">💰</div>
        <div class="stat-info">
            <h3>TZS <?= number_format($totalRevenue, 0) ?></h3>
            <p>Total Revenue</p>
        </div>
    </div>
    <div class="stat-card stat-orange">
        <div class="stat-icon">✅</div>
        <div class="stat-info">
            <h3><?= $statusCounts['completed'] ?></h3>
            <p>Completed Jobs</p>
        </div>
    </div>
    <div class="stat-card stat-yellow">
        <div class="stat-icon">⏳</div>
        <div class="stat-info">
            <h3><?= $statusCounts['pending'] ?></h3>
            <p>Pending Jobs</p>
        </div>
    </div>
    <div class="stat-card stat-blue">
        <div class="stat-icon">📦</div>
        <div class="stat-info">
            <h3><?= count($allOrders) ?></h3>
            <p>Shop Orders</p>
        </div>
    </div>
    <div class="stat-card stat-green">
        <div class="stat-icon">🛒</div>
        <div class="stat-info">
            <h3>TZS <?= number_format($orderRevenue, 0) ?></h3>
            <p>Shop Revenue (<?= $paidOrders ?> paid)</p>
        </div>
    </div>
</div>

<!-- Jobs by Status -->
<div class="report-grid">
    <div class="card">
        <div class="card-header"><h3>Jobs by Status</h3></div>
        <table>
            <thead><tr><th>Status</th><th>Count</th><th>%</th></tr></thead>
            <tbody>
            <?php $total = max(1, count($allJobs)); ?>
            <?php foreach ($statusCounts as $status => $count): ?>
            <tr>
                <td><span class="badge badge-<?= $status ?>"><?= ucfirst($status) ?></span></td>
                <td><?= $count ?></td>
                <td><?= round($count / $total * 100, 1) ?>%</td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Mechanics Performance -->
    <div class="card">
        <div class="card-header"><h3>Jobs per Mechanic</h3></div>
        <table>
            <thead><tr><th>Mechanic</th><th>Jobs</th></tr></thead>
            <tbody>
            <?php foreach ($mechanicJobs as $name => $count): ?>
            <tr>
                <td><?= htmlspecialchars($name) ?></td>
                <td><?= $count ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Monthly Revenue -->
<div class="report-grid">
<div class="card">
    <div class="card-header"><h3>Monthly Repair Revenue</h3></div>
    <table>
        <thead><tr><th>Month</th><th>Revenue (TZS)</th></tr></thead>
        <tbody>
        <?php if (empty($monthlyRevenue)): ?>
        <tr><td colspan="2" class="empty">No payment data yet.</td></tr>
        <?php else: ?>
        <?php foreach ($monthlyRevenue as $month => $amount): ?>
        <tr>
            <td><?= htmlspecialchars($month) ?></td>
            <td><?= number_format($amount, 0) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="card">
    <div class="card-header"><h3>Shop Orders Summary</h3></div>
    <table>
        <thead><tr><th>Payment Status</th><th>Count</th><th>Total (TZS)</th></tr></thead>
        <tbody>
        <?php
        $orderSummary = ['pending' => ['count' => 0, 'total' => 0], 'paid' => ['count' => 0, 'total' => 0], 'failed' => ['count' => 0, 'total' => 0]];
        foreach ($allOrders as $o) {
            $st = $o['payment_status'] ?? 'pending';
            if (isset($orderSummary[$st])) {
                $orderSummary[$st]['count']++;
                $orderSummary[$st]['total'] += (float)($o['total_amount'] ?? 0);
            }
        }
        foreach ($orderSummary as $st => $data):
        ?>
        <tr>
            <td><span class="badge badge-<?= $st === 'paid' ? 'completed' : ($st === 'failed' ? 'cancelled' : 'pending') ?>"><?= ucfirst($st) ?></span></td>
            <td><?= $data['count'] ?></td>
            <td><?= number_format($data['total'], 0) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
</div>

<!-- All Jobs Report -->
<div class="card">
    <div class="card-header">
        <h3>All Repair Jobs</h3>
        <span class="badge"><?= count($allJobs) ?> total</span>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th><th>Customer</th><th>Vehicle</th>
                    <th>Description</th><th>Mechanic</th>
                    <th>Status</th><th>Cost</th><th>Date</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($allJobs as $job): ?>
            <tr>
                <td><?= $job['id'] ?></td>
                <td><?= htmlspecialchars($job['customer_name'] ?? '') ?></td>
                <td><?= htmlspecialchars($job['plate_number']  ?? '') ?></td>
                <td><?= htmlspecialchars(substr($job['job_description'] ?? '', 0, 40)) ?>...</td>
                <td><?= htmlspecialchars($job['mechanic_name'] ?? 'Unassigned') ?></td>
                <td><span class="badge badge-<?= $job['status'] ?>"><?= ucfirst($job['status'] ?? '') ?></span></td>
                <td><?= $job['total_cost'] ? 'TZS ' . number_format((float)$job['total_cost'], 0) : '-' ?></td>
                <td><?= htmlspecialchars($job['date_received'] ?? '') ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
