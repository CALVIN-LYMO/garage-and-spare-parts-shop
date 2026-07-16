<?php
// pages/vehicles.php — Vehicles CRUD
require_once __DIR__ . '/../classes/Vehicle.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Customer.php';
require_once __DIR__ . '/../includes/header.php';

$vehicleModel  = new Vehicle();
$customerModel = new Customer();
$action        = $_GET['action']      ?? 'list';
$id            = isset($_GET['id'])   ? (int)$_GET['id']   : 0;
$customerId    = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;
$errors        = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $postAction = $_POST['action'] ?? '';

    if (empty($_POST['customer_id']))   $errors[] = 'Customer is required.';
    if (empty($_POST['plate_number']))  $errors[] = 'Plate number is required.';
    if (empty($_POST['make']))          $errors[] = 'Vehicle make is required.';

    if (empty($errors)) {
        $data = [
            'customer_id'   => (int)$_POST['customer_id'],
            'plate_number'  => strtoupper(trim($_POST['plate_number'])),
            'make'          => trim($_POST['make']),
            'model'         => trim($_POST['model']          ?? ''),
            'year'          => trim($_POST['year']           ?? ''),
            'color'         => trim($_POST['color']          ?? ''),
            'engine_number' => trim($_POST['engine_number']  ?? ''),
        ];
        if ($postAction === 'create') {
            $vehicleModel->create($data)
                ? redirectWith(BASE_URL . '/pages/vehicles.php', 'success', 'Vehicle added.')
                : redirectWith(BASE_URL . '/pages/vehicles.php', 'danger',  'Failed to add vehicle.');
        } elseif ($postAction === 'update' && $id) {
            $vehicleModel->update($id, $data)
                ? redirectWith(BASE_URL . '/pages/vehicles.php', 'success', 'Vehicle updated.')
                : redirectWith(BASE_URL . '/pages/vehicles.php', 'danger',  'Failed to update vehicle.');
        }
    }
}

if ($action === 'delete' && $id) {
    requireAdmin();
    $vehicleModel->delete($id);
    redirectWith(BASE_URL . '/pages/vehicles.php', 'success', 'Vehicle deleted.');
}

$editData  = ($action === 'edit' && $id) ? $vehicleModel->findById($id) : null;
$customers = $customerModel->getAll();
$search    = trim($_GET['search'] ?? '');
$vehicles  = $search ? $vehicleModel->search($search) : $vehicleModel->getAll();
?>

<div class="page-header">
    <h2>🚗 Vehicles</h2>
    <button onclick="toggleForm('v-form')" class="btn-primary">+ Add Vehicle</button>
</div>

<div id="v-form" class="card form-card" style="display:none;">
    <h3><?= $action === 'edit' ? 'Edit Vehicle' : 'Add Vehicle' ?></h3>
    <?php if ($errors): ?>
        <div class="alert alert-danger"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
    <?php endif; ?>
    <form method="POST" action="?<?= $action === 'edit' ? "action=edit&id=$id" : '' ?>">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="action" value="<?= $action === 'edit' ? 'update' : 'create' ?>">
        <div class="form-row">
            <div class="form-group">
                <label>Customer *</label>
                <select name="customer_id" required>
                    <option value="">-- Select Customer --</option>
                    <?php foreach ($customers as $c): ?>
                    <option value="<?= $c['id'] ?>"
                        <?= ($editData['customer_id'] ?? $customerId) == $c['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['full_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Plate Number *</label>
                <input type="text" name="plate_number"
                       value="<?= htmlspecialchars($editData['plate_number'] ?? '') ?>"
                       placeholder="e.g. T123 ABC" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Make *</label>
                <input type="text" name="make" value="<?= htmlspecialchars($editData['make'] ?? '') ?>" placeholder="e.g. Toyota" required>
            </div>
            <div class="form-group">
                <label>Model</label>
                <input type="text" name="model" value="<?= htmlspecialchars($editData['model'] ?? '') ?>" placeholder="e.g. Corolla">
            </div>
            <div class="form-group">
                <label>Year</label>
                <input type="text" name="year" value="<?= htmlspecialchars($editData['year'] ?? '') ?>" placeholder="e.g. 2018">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Color</label>
                <input type="text" name="color" value="<?= htmlspecialchars($editData['color'] ?? '') ?>" placeholder="e.g. White">
            </div>
            <div class="form-group">
                <label>Engine Number</label>
                <input type="text" name="engine_number" value="<?= htmlspecialchars($editData['engine_number'] ?? '') ?>" placeholder="Engine No.">
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn-primary"><?= $action === 'edit' ? 'Update' : 'Save Vehicle' ?></button>
            <a href="<?= BASE_URL ?>/pages/vehicles.php" class="btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<div class="card">
    <form method="GET" action="" class="search-form">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by plate, make, model...">
        <button type="submit" class="btn-primary">Search</button>
        <?php if ($search): ?><a href="<?= BASE_URL ?>/pages/vehicles.php" class="btn-secondary">Clear</a><?php endif; ?>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h3>All Vehicles</h3>
        <span class="badge"><?= count($vehicles) ?> records</span>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr><th>#</th><th>Plate</th><th>Make</th><th>Model</th><th>Year</th><th>Color</th><th>Owner</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php if (empty($vehicles)): ?>
            <tr><td colspan="8" class="empty">No vehicles found.</td></tr>
            <?php else: ?>
            <?php foreach ($vehicles as $v): ?>
            <tr>
                <td><?= $v['id'] ?></td>
                <td><strong><?= htmlspecialchars($v['plate_number'] ?? '') ?></strong></td>
                <td><?= htmlspecialchars($v['make']  ?? '') ?></td>
                <td><?= htmlspecialchars($v['model'] ?? '') ?></td>
                <td><?= htmlspecialchars($v['year']  ?? '') ?></td>
                <td><?= htmlspecialchars($v['color'] ?? '') ?></td>
                <td><?= htmlspecialchars($v['customer_name'] ?? '') ?></td>
                <td class="actions">
                    <a href="?action=edit&id=<?= $v['id'] ?>" class="btn-sm btn-edit">Edit</a>
                    <?php if (isAdmin()): ?>
                    <a href="?action=delete&id=<?= $v['id'] ?>" class="btn-sm btn-delete"
                       onclick="return confirm('Delete this vehicle?')">Delete</a>
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
<?php if ($action === 'edit' || !empty($errors)): ?>
document.getElementById('v-form').style.display = 'block';
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
