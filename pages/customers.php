<?php
// pages/customers.php — Full CRUD for Customers
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Customer.php';
require_once __DIR__ . '/../includes/header.php';

$model  = new Customer();
$action = $_GET['action'] ?? 'list';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$errors = [];

// ---- Handle POST (Create / Update) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $postAction = $_POST['action'] ?? '';

    // Basic validation
    if (empty($_POST['full_name'])) $errors[] = 'Full name is required.';
    if (empty($_POST['phone']))     $errors[] = 'Phone number is required.';

    if (empty($errors)) {
        $data = [
            'full_name'  => trim($_POST['full_name']),
            'phone'      => trim($_POST['phone']),
            'email'      => trim($_POST['email']    ?? ''),
            'address'    => trim($_POST['address']  ?? ''),
            'created_by' => currentUserId(),
        ];

        if ($postAction === 'create') {
            $model->create($data)
                ? redirectWith(BASE_URL . '/pages/customers.php', 'success', 'Customer added successfully.')
                : redirectWith(BASE_URL . '/pages/customers.php', 'danger',  'Failed to add customer.');
        } elseif ($postAction === 'update' && $id) {
            $model->update($id, $data)
                ? redirectWith(BASE_URL . '/pages/customers.php', 'success', 'Customer updated successfully.')
                : redirectWith(BASE_URL . '/pages/customers.php', 'danger',  'Failed to update customer.');
        }
    }
}

// ---- Handle DELETE ----
if ($action === 'delete' && $id) {
    requireAdmin();
    $model->delete($id)
        ? redirectWith(BASE_URL . '/pages/customers.php', 'success', 'Customer deleted.')
        : redirectWith(BASE_URL . '/pages/customers.php', 'danger',  'Failed to delete.');
}

// ---- Load data for edit form ----
$editData = ($action === 'edit' && $id) ? $model->findById($id) : null;

// ---- Search ----
$search    = trim($_GET['search'] ?? '');
$customers = $search ? $model->search($search) : $model->getAll();
?>

<div class="page-header">
    <h2>👥 Customers</h2>
    <button onclick="toggleForm('add-form')" class="btn-primary">+ Add Customer</button>
</div>

<!-- Add Form (hidden by default) -->
<div id="add-form" class="card form-card" style="display:none;">
    <h3><?= $action === 'edit' ? 'Edit Customer' : 'Add New Customer' ?></h3>
    <?php if ($errors): ?>
        <div class="alert alert-danger"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
    <?php endif; ?>
    <form method="POST" action="?<?= $action === 'edit' ? "action=edit&id=$id" : '' ?>">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="action" value="<?= $action === 'edit' ? 'update' : 'create' ?>">

        <div class="form-row">
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="full_name"
                       value="<?= htmlspecialchars($editData['full_name'] ?? $_POST['full_name'] ?? '') ?>"
                       placeholder="e.g. John Doe" required>
            </div>
            <div class="form-group">
                <label>Phone *</label>
                <input type="text" name="phone"
                       value="<?= htmlspecialchars($editData['phone'] ?? $_POST['phone'] ?? '') ?>"
                       placeholder="e.g. 0712345678" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email"
                       value="<?= htmlspecialchars($editData['email'] ?? $_POST['email'] ?? '') ?>"
                       placeholder="e.g. john@email.com">
            </div>
            <div class="form-group">
                <label>Address</label>
                <input type="text" name="address"
                       value="<?= htmlspecialchars($editData['address'] ?? $_POST['address'] ?? '') ?>"
                       placeholder="e.g. Dar es Salaam">
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn-primary">
                <?= $action === 'edit' ? 'Update Customer' : 'Save Customer' ?>
            </button>
            <a href="<?= BASE_URL ?>/pages/customers.php" class="btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<!-- Search -->
<div class="card">
    <form method="GET" action="" class="search-form">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
               placeholder="Search by name, phone or email...">
        <button type="submit" class="btn-primary">Search</button>
        <?php if ($search): ?>
            <a href="<?= BASE_URL ?>/pages/customers.php" class="btn-secondary">Clear</a>
        <?php endif; ?>
    </form>
</div>

<!-- Table -->
<div class="card">
    <div class="card-header">
        <h3>All Customers <?= $search ? "(Results for: \"$search\")" : '' ?></h3>
        <span class="badge"><?= count($customers) ?> records</span>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Full Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Address</th>
                    <th>Vehicles</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                <tr><td colspan="7" class="empty">No customers found.</td></tr>
                <?php else: ?>
                <?php foreach ($customers as $c): ?>
                <tr>
                    <td><?= $c['id'] ?></td>
                    <td><?= htmlspecialchars($c['full_name'] ?? '') ?></td>
                    <td><?= htmlspecialchars($c['phone']     ?? '') ?></td>
                    <td><?= htmlspecialchars($c['email']     ?? '') ?></td>
                    <td><?= htmlspecialchars($c['address']   ?? '') ?></td>
                    <td>
                        <a href="<?= BASE_URL ?>/pages/vehicles.php?customer_id=<?= $c['id'] ?>">
                            <?= $c['vehicle_count'] ?? 0 ?> vehicle(s)
                        </a>
                    </td>
                    <td class="actions">
                        <a href="?action=edit&id=<?= $c['id'] ?>" class="btn-sm btn-edit">Edit</a>
                        <?php if (isAdmin()): ?>
                        <a href="?action=delete&id=<?= $c['id'] ?>"
                           class="btn-sm btn-delete"
                           onclick="return confirm('Delete this customer and all their records?')">
                           Delete
                        </a>
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
document.getElementById('add-form').style.display = 'block';
window.scrollTo(0, 0);
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
