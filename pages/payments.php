<?php
// pages/payments.php — Payments CRUD
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/RepairJob.php';
require_once __DIR__ . '/../includes/header.php';

$paymentModel = new Payment();
$jobModel     = new RepairJob();
$action       = $_GET['action'] ?? 'list';
$id           = isset($_GET['id'])     ? (int)$_GET['id']     : 0;
$jobId        = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
$errors       = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $postAction = $_POST['action'] ?? '';

    if (empty($_POST['job_id']))  $errors[] = 'Job is required.';
    if (empty($_POST['amount']))  $errors[] = 'Amount is required.';
    if (empty($_POST['method']))  $errors[] = 'Payment method is required.';

    if (empty($errors)) {
        $data = [
            'job_id'      => (int)$_POST['job_id'],
            'amount'      => trim($_POST['amount']),
            'method'      => trim($_POST['method']),
            'reference'   => trim($_POST['reference'] ?? ''),
            'notes'       => trim($_POST['notes']     ?? ''),
            'paid_at'     => trim($_POST['paid_at']   ?? date('Y-m-d H:i:s')),
            'recorded_by' => currentUserId(),
        ];
        if ($postAction === 'create') {
            $paymentModel->create($data)
                ? redirectWith(BASE_URL . '/pages/payments.php', 'success', 'Payment recorded.')
                : redirectWith(BASE_URL . '/pages/payments.php', 'danger',  'Failed to record payment.');
        } elseif ($postAction === 'update' && $id) {
            $paymentModel->update($id, $data)
                ? redirectWith(BASE_URL . '/pages/payments.php', 'success', 'Payment updated.')
                : redirectWith(BASE_URL . '/pages/payments.php', 'danger',  'Failed to update.');
        }
    }
}

if ($action === 'delete' && $id) {
    requireAdmin();
    $paymentModel->delete($id);
    redirectWith(BASE_URL . '/pages/payments.php', 'success', 'Payment deleted.');
}

$editData = ($action === 'edit' && $id) ? $paymentModel->findById($id) : null;
$allJobs  = $jobModel->getAll();
$payments = $paymentModel->getAll();
$total    = $paymentModel->getTotalRevenue();

$methodOptions = ['Cash','M-Pesa','Bank Transfer','Cheque','Card'];
?>

<div class="page-header">
    <h2>💰 Payments</h2>
    <button onclick="toggleForm('pay-form')" class="btn-primary">+ Record Payment</button>
</div>

<div id="pay-form" class="card form-card" style="display:none;">
    <h3><?= $action === 'edit' ? 'Edit Payment' : 'Record New Payment' ?></h3>
    <?php if ($errors): ?>
        <div class="alert alert-danger"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
    <?php endif; ?>
    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="action" value="<?= $action === 'edit' ? 'update' : 'create' ?>">
        <div class="form-row">
            <div class="form-group">
                <label>Repair Job *</label>
                <select name="job_id" required>
                    <option value="">-- Select Job --</option>
                    <?php foreach ($allJobs as $job): ?>
                    <option value="<?= $job['id'] ?>"
                        <?= ($editData['job_id'] ?? $jobId) == $job['id'] ? 'selected' : '' ?>>
                        Job #<?= $job['id'] ?> — <?= htmlspecialchars($job['customer_name'] ?? '') ?>
                        (<?= htmlspecialchars($job['plate_number'] ?? '') ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Amount (TZS) *</label>
                <input type="number" name="amount" step="0.01" min="0"
                       value="<?= htmlspecialchars($editData['amount'] ?? '') ?>"
                       placeholder="e.g. 150000" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Payment Method *</label>
                <select name="method" required>
                    <?php foreach ($methodOptions as $m): ?>
                    <option value="<?= $m ?>" <?= ($editData['method'] ?? '') === $m ? 'selected' : '' ?>><?= $m ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Reference / Receipt No.</label>
                <input type="text" name="reference"
                       value="<?= htmlspecialchars($editData['reference'] ?? '') ?>"
                       placeholder="e.g. MPESA TXN ID">
            </div>
            <div class="form-group">
                <label>Date Paid</label>
                <input type="datetime-local" name="paid_at"
                       value="<?= htmlspecialchars(str_replace(' ', 'T', $editData['paid_at'] ?? date('Y-m-d H:i'))) ?>">
            </div>
        </div>
        <div class="form-group">
            <label>Notes</label>
            <textarea name="notes" rows="2" placeholder="Any additional notes..."><?= htmlspecialchars($editData['notes'] ?? '') ?></textarea>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn-primary"><?= $action === 'edit' ? 'Update Payment' : 'Save Payment' ?></button>
            <a href="<?= BASE_URL ?>/pages/payments.php" class="btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<!-- Total Revenue -->
<div class="stats-grid" style="margin-bottom:20px;">
    <div class="stat-card stat-green">
        <div class="stat-icon">💰</div>
        <div class="stat-info">
            <h3>TZS <?= number_format($total, 0) ?></h3>
            <p>Total Revenue Collected</p>
        </div>
    </div>
    <div class="stat-card stat-blue">
        <div class="stat-icon">📋</div>
        <div class="stat-info">
            <h3><?= count($payments) ?></h3>
            <p>Total Transactions</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Payment Records</h3>
        <span class="badge"><?= count($payments) ?> records</span>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr><th>#</th><th>Job</th><th>Customer</th><th>Amount</th><th>Method</th><th>Reference</th><th>Date</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php if (empty($payments)): ?>
            <tr><td colspan="8" class="empty">No payments recorded yet.</td></tr>
            <?php else: ?>
            <?php foreach ($payments as $p): ?>
            <tr>
                <td><?= $p['id'] ?></td>
                <td>Job #<?= $p['job_id'] ?></td>
                <td><?= htmlspecialchars($p['customer_name'] ?? '') ?></td>
                <td><strong>TZS <?= number_format((float)($p['amount'] ?? 0), 0) ?></strong></td>
                <td><?= htmlspecialchars($p['method']    ?? '') ?></td>
                <td><?= htmlspecialchars($p['reference'] ?? '-') ?></td>
                <td><?= htmlspecialchars($p['paid_at']   ?? '') ?></td>
                <td class="actions">
                    <a href="?action=edit&id=<?= $p['id'] ?>" class="btn-sm btn-edit">Edit</a>
                    <?php if (isAdmin()): ?>
                    <a href="?action=delete&id=<?= $p['id'] ?>" class="btn-sm btn-delete"
                       onclick="return confirm('Delete this payment?')">Delete</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function toggleForm(id) {
    const el = document.getElementById(id);
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
<?php if ($action === 'edit' || $jobId || !empty($errors)): ?>
document.getElementById('pay-form').style.display = 'block';
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
