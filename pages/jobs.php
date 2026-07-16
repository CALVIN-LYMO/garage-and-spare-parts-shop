<?php
// pages/jobs.php — Repair Jobs CRUD
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/RepairJob.php';
require_once __DIR__ . '/../classes/Customer.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Vehicle.php';
require_once __DIR__ . '/../includes/header.php';

$jobModel      = new RepairJob();
$customerModel = new Customer();
$userModel     = new User();
$vehicleModel  = new Vehicle();
$action        = $_GET['action'] ?? 'list';
$id            = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$errors        = [];

// ---- Handle POST ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $postAction = $_POST['action'] ?? '';

    if (empty($_POST['vehicle_id']))      $errors[] = 'Vehicle is required.';
    if (empty($_POST['job_description'])) $errors[] = 'Job description is required.';

    if (empty($errors)) {
        $data = [
            'vehicle_id'      => (int)$_POST['vehicle_id'],
            'mechanic_id'     => !empty($_POST['mechanic_id']) ? (int)$_POST['mechanic_id'] : null,
            'job_description' => trim($_POST['job_description']),
            'diagnosis'       => trim($_POST['diagnosis']   ?? ''),
            'status'          => trim($_POST['status']      ?? 'pending'),
            'date_received'   => trim($_POST['date_received'] ?? date('Y-m-d')),
            'date_completed'  => trim($_POST['date_completed'] ?? ''),
            'total_cost'      => trim($_POST['total_cost']  ?? ''),
            'created_by'      => currentUserId(),
        ];

        if ($postAction === 'create') {
            $jobModel->create($data)
                ? redirectWith(BASE_URL . '/pages/jobs.php', 'success', 'Repair job created successfully.')
                : redirectWith(BASE_URL . '/pages/jobs.php', 'danger',  'Failed to create job.');
        } elseif ($postAction === 'update' && $id) {
            $jobModel->update($id, $data)
                ? redirectWith(BASE_URL . '/pages/jobs.php', 'success', 'Job updated successfully.')
                : redirectWith(BASE_URL . '/pages/jobs.php', 'danger',  'Failed to update job.');
        }
    }
}

if ($action === 'delete' && $id) {
    requireAdmin();
    $jobModel->delete($id);
    redirectWith(BASE_URL . '/pages/jobs.php', 'success', 'Job deleted.');
}

$editData  = ($action === 'edit' && $id) ? $jobModel->findById($id) : null;
$customers = $customerModel->getAll();
$mechanics = $userModel->getMechanics();
$search    = trim($_GET['search'] ?? '');
$jobs      = isAdmin()
    ? ($search ? $jobModel->search($search) : $jobModel->getAll())
    : $jobModel->getByMechanic(currentUserId());

$statusOptions = ['pending','in-progress','completed','cancelled'];
?>

<div class="page-header">
    <h2>🔧 Repair Jobs</h2>
    <button onclick="toggleForm('job-form')" class="btn-primary">+ New Job</button>
</div>

<!-- Form -->
<div id="job-form" class="card form-card" style="display:none;">
    <h3><?= $action === 'edit' ? 'Edit Repair Job' : 'New Repair Job' ?></h3>
    <?php if ($errors): ?>
        <div class="alert alert-danger"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
    <?php endif; ?>
    <form method="POST" action="?<?= $action === 'edit' ? "action=edit&id=$id" : '' ?>">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="action" value="<?= $action === 'edit' ? 'update' : 'create' ?>">

        <div class="form-row">
            <div class="form-group">
                <label>Customer *</label>
                <select name="customer_id" id="customer_select" onchange="loadVehicles(this.value)" required>
                    <option value="">-- Select Customer --</option>
                    <?php foreach ($customers as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Vehicle *</label>
                <select name="vehicle_id" id="vehicle_select" required>
                    <option value="">-- Select Customer First --</option>
                    <?php if ($editData): ?>
                    <option value="<?= $editData['vehicle_id'] ?>" selected>
                        <?= htmlspecialchars($editData['plate_number'] ?? '') ?>
                        (<?= htmlspecialchars($editData['make'] ?? '') ?>)
                    </option>
                    <?php endif; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Assign Mechanic</label>
                <select name="mechanic_id">
                    <option value="">-- Unassigned --</option>
                    <?php foreach ($mechanics as $m): ?>
                    <option value="<?= $m['id'] ?>"
                        <?= ($editData['mechanic_id'] ?? '') == $m['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($m['full_name'] ?? $m['username']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <?php foreach ($statusOptions as $s): ?>
                    <option value="<?= $s ?>"
                        <?= ($editData['status'] ?? 'pending') === $s ? 'selected' : '' ?>>
                        <?= ucfirst($s) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Job Description *</label>
            <textarea name="job_description" rows="3" placeholder="Describe the repair work needed..." required>
                <?= htmlspecialchars($editData['job_description'] ?? '') ?>
            </textarea>
        </div>

        <div class="form-group">
            <label>Diagnosis / Findings</label>
            <textarea name="diagnosis" rows="2" placeholder="Technical diagnosis...">
                <?= htmlspecialchars($editData['diagnosis'] ?? '') ?>
            </textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Date Received</label>
                <input type="date" name="date_received"
                       value="<?= htmlspecialchars($editData['date_received'] ?? date('Y-m-d')) ?>">
            </div>
            <div class="form-group">
                <label>Date Completed</label>
                <input type="date" name="date_completed"
                       value="<?= htmlspecialchars($editData['date_completed'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Total Cost (TZS)</label>
                <input type="number" name="total_cost" step="0.01"
                       value="<?= htmlspecialchars($editData['total_cost'] ?? '') ?>"
                       placeholder="0.00">
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary">
                <?= $action === 'edit' ? 'Update Job' : 'Create Job' ?>
            </button>
            <a href="<?= BASE_URL ?>/pages/jobs.php" class="btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<!-- Search (Admin only) -->
<?php if (isAdmin()): ?>
<div class="card">
    <form method="GET" action="" class="search-form">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
               placeholder="Search by customer, plate, status...">
        <button type="submit" class="btn-primary">Search</button>
        <?php if ($search): ?>
            <a href="<?= BASE_URL ?>/pages/jobs.php" class="btn-secondary">Clear</a>
        <?php endif; ?>
    </form>
</div>
<?php endif; ?>

<!-- Jobs Table -->
<div class="card">
    <div class="card-header">
        <h3>Repair Jobs <?= !isAdmin() ? '(My Assigned Jobs)' : '' ?></h3>
        <span class="badge"><?= count($jobs) ?> records</span>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Customer</th>
                    <th>Vehicle</th>
                    <th>Description</th>
                    <th>Mechanic</th>
                    <th>Status</th>
                    <th>Cost</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($jobs)): ?>
                <tr><td colspan="9" class="empty">No jobs found.</td></tr>
                <?php else: ?>
                <?php foreach ($jobs as $job): ?>
                <tr>
                    <td><?= $job['id'] ?></td>
                    <td><?= htmlspecialchars($job['customer_name'] ?? '') ?></td>
                    <td><?= htmlspecialchars($job['make'] ?? '') ?> <?= htmlspecialchars($job['model'] ?? '') ?>
                        <br><small><?= htmlspecialchars($job['plate_number'] ?? '') ?></small>
                    </td>
                    <td><?= htmlspecialchars(substr($job['job_description'] ?? '', 0, 40)) ?>...</td>
                    <td><?= htmlspecialchars($job['mechanic_name'] ?? 'Unassigned') ?></td>
                    <td><span class="badge badge-<?= htmlspecialchars($job['status'] ?? '') ?>">
                        <?= htmlspecialchars(ucfirst($job['status'] ?? '')) ?>
                    </span></td>
                    <td><?= $job['total_cost'] ? 'TZS ' . number_format((float)$job['total_cost'], 0) : '-' ?></td>
                    <td><?= htmlspecialchars($job['date_received'] ?? '') ?></td>
                    <td class="actions">
                        <a href="?action=edit&id=<?= $job['id'] ?>" class="btn-sm btn-edit">Edit</a>
                        <a href="<?= BASE_URL ?>/pages/payments.php?job_id=<?= $job['id'] ?>" class="btn-sm">Pay</a>
                        <?php if (isAdmin()): ?>
                        <a href="?action=delete&id=<?= $job['id'] ?>"
                           class="btn-sm btn-delete"
                           onclick="return confirm('Delete this job?')">Delete</a>
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
<?php if ($action === 'edit'): ?>
document.getElementById('job-form').style.display = 'block';
<?php endif; ?>

// Load vehicles via AJAX when customer changes
function loadVehicles(customerId) {
    const select = document.getElementById('vehicle_select');
    select.innerHTML = '<option value="">Loading...</option>';
    if (!customerId) {
        select.innerHTML = '<option value="">-- Select Customer First --</option>';
        return;
    }
    fetch('<?= BASE_URL ?>/pages/ajax_vehicles.php?customer_id=' + customerId)
        .then(r => r.json())
        .then(data => {
            select.innerHTML = '<option value="">-- Select Vehicle --</option>';
            data.forEach(v => {
                select.innerHTML += `<option value="${v.id}">${v.plate_number} (${v.make} ${v.model})</option>`;
            });
        });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
