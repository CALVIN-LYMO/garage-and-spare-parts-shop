<?php
// pages/manage_services.php — Admin: Manage Repair Services
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Service.php';
require_once __DIR__ . '/../includes/header.php';

requireAdmin();

$model  = new Service();
$action = $_GET['action'] ?? 'list';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $postAction = $_POST['action'] ?? '';

    if (empty($_POST['service_name'])) $errors[] = 'Service name is required.';
    if (!isset($_POST['base_price']) || $_POST['base_price'] === '') $errors[] = 'Base price is required.';

    if (empty($errors)) {
        $data = [
            'service_name' => trim($_POST['service_name']),
            'base_price'   => trim($_POST['base_price']),
            'description'  => trim($_POST['description'] ?? ''),
        ];

        if ($postAction === 'create') {
            $model->create($data)
                ? redirectWith(BASE_URL . '/pages/manage_services.php', 'success', 'Service added successfully.')
                : redirectWith(BASE_URL . '/pages/manage_services.php', 'danger',  'Failed to add service.');
        } elseif ($postAction === 'update' && $id) {
            $model->update($id, $data)
                ? redirectWith(BASE_URL . '/pages/manage_services.php', 'success', 'Service updated successfully.')
                : redirectWith(BASE_URL . '/pages/manage_services.php', 'danger',  'Failed to update service.');
        }
    }
}

if ($action === 'delete' && $id) {
    $model->delete($id)
        ? redirectWith(BASE_URL . '/pages/manage_services.php', 'success', 'Service deleted.')
        : redirectWith(BASE_URL . '/pages/manage_services.php', 'danger',  'Failed to delete service.');
}

$editData = ($action === 'edit' && $id) ? $model->findById($id) : null;
$search   = trim($_GET['search'] ?? '');
$services = $search ? $model->search($search) : $model->getAll();
?>

<div class="page-header">
    <h2>🛠 Manage Services</h2>
    <button onclick="toggleForm('add-form')" class="btn-primary">+ Add Service</button>
</div>

<div id="add-form" class="card form-card" style="display:none;">
    <h3><?= $action === 'edit' ? 'Edit Service' : 'Add New Service' ?></h3>
    <?php if ($errors): ?>
        <div class="alert alert-danger"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
    <?php endif; ?>
    <form method="POST" action="?<?= $action === 'edit' ? "action=edit&id=$id" : '' ?>">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="action" value="<?= $action === 'edit' ? 'update' : 'create' ?>">

        <div class="form-row">
            <div class="form-group">
                <label>Service Name *</label>
                <input type="text" name="service_name"
                       value="<?= htmlspecialchars($editData['service_name'] ?? $_POST['service_name'] ?? '') ?>"
                       placeholder="e.g. Engine Diagnostics" required>
            </div>
            <div class="form-group">
                <label>Base Price (TZS) *</label>
                <input type="number" name="base_price" min="0" step="0.01"
                       value="<?= htmlspecialchars($editData['base_price'] ?? $_POST['base_price'] ?? '0') ?>" required>
            </div>
        </div>
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" rows="3"
                      placeholder="What does this service include?"><?= htmlspecialchars($editData['description'] ?? $_POST['description'] ?? '') ?></textarea>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn-primary">
                <?= $action === 'edit' ? 'Update Service' : 'Save Service' ?>
            </button>
            <a href="<?= BASE_URL ?>/pages/manage_services.php" class="btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<div class="card">
    <form method="GET" action="" class="search-form">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
               placeholder="Search services...">
        <button type="submit" class="btn-primary">Search</button>
        <?php if ($search): ?>
            <a href="<?= BASE_URL ?>/pages/manage_services.php" class="btn-secondary">Clear</a>
        <?php endif; ?>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h3>All Services</h3>
        <span class="badge"><?= count($services) ?> records</span>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Service Name</th>
                    <th>Base Price (TZS)</th>
                    <th>Description</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($services)): ?>
                <tr><td colspan="5" class="empty">No services found. Add services for customers to request.</td></tr>
                <?php else: ?>
                <?php foreach ($services as $svc): ?>
                <tr>
                    <td><?= $svc['id'] ?></td>
                    <td><?= htmlspecialchars($svc['service_name'] ?? '') ?></td>
                    <td><?= number_format((float)($svc['base_price'] ?? 0), 0) ?></td>
                    <td><?= htmlspecialchars(substr($svc['description'] ?? '', 0, 60)) ?><?= strlen($svc['description'] ?? '') > 60 ? '...' : '' ?></td>
                    <td class="actions">
                        <a href="?action=edit&id=<?= $svc['id'] ?>" class="btn-sm btn-edit">Edit</a>
                        <a href="?action=delete&id=<?= $svc['id'] ?>"
                           class="btn-sm btn-delete"
                           onclick="return confirm('Delete this service?')">Delete</a>
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
<?php if ($action === 'edit' || $errors): ?>
document.getElementById('add-form').style.display = 'block';
window.scrollTo(0, 0);
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
