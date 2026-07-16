<?php
// pages/users.php — Admin: Manage Users
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../includes/header.php';

requireAdmin();

$userModel = new User();
$action    = $_GET['action'] ?? 'list';
$id        = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$errors    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $postAction = $_POST['action'] ?? '';

    if (empty($_POST['username']))  $errors[] = 'Username is required.';
    if ($postAction === 'create' && empty($_POST['password'])) $errors[] = 'Password is required.';
    if (!empty($_POST['password']) && strlen($_POST['password']) < 6) $errors[] = 'Password must be at least 6 characters.';

    if (empty($errors)) {
        $data = [
            'username'  => trim($_POST['username']),
            'password'  => trim($_POST['password'] ?? ''),
            'full_name' => trim($_POST['full_name'] ?? ''),
            'email'     => trim($_POST['email']     ?? ''),
            'phone'     => trim($_POST['phone']     ?? ''),
            'location'  => trim($_POST['location']  ?? ''),
            'role'      => $_POST['role']            ?? 'mechanic',
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];
        if ($postAction === 'create') {
            if ($userModel->usernameExists($data['username'])) {
                $errors[] = 'Username already exists.';
            } else {
                $userModel->create($data)
                    ? redirectWith(BASE_URL . '/pages/users.php', 'success', 'User created.')
                    : redirectWith(BASE_URL . '/pages/users.php', 'danger',  'Failed to create user.');
            }
        } elseif ($postAction === 'update' && $id) {
            $userModel->update($id, $data)
                ? redirectWith(BASE_URL . '/pages/users.php', 'success', 'User updated.')
                : redirectWith(BASE_URL . '/pages/users.php', 'danger',  'Failed to update.');
            if (!empty($data['password'])) {
                $userModel->changePassword($id, $data['password']);
            }
        }
    }
}

if ($action === 'delete' && $id && $id !== currentUserId()) {
    $userModel->delete($id);
    redirectWith(BASE_URL . '/pages/users.php', 'success', 'User deleted.');
}

$editData = ($action === 'edit' && $id) ? $userModel->findById($id) : null;
$users    = $userModel->getAll();
?>

<div class="page-header">
    <h2>👤 Users</h2>
    <button onclick="toggleForm('user-form')" class="btn-primary">+ Add User</button>
</div>

<div id="user-form" class="card form-card" style="display:none;">
    <h3><?= $action === 'edit' ? 'Edit User' : 'Add New User' ?></h3>
    <?php if ($errors): ?>
        <div class="alert alert-danger"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
    <?php endif; ?>
    <form method="POST" action="?<?= $action === 'edit' ? "action=edit&id=$id" : '' ?>">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="action" value="<?= $action === 'edit' ? 'update' : 'create' ?>">
        <div class="form-row">
            <div class="form-group">
                <label>Username *</label>
                <input type="text" name="username"
                       value="<?= htmlspecialchars($editData['username'] ?? '') ?>"
                       <?= $action === 'edit' ? 'readonly' : '' ?> required>
            </div>
            <div class="form-group">
                <label>Password <?= $action === 'edit' ? '(leave blank to keep)' : '*' ?></label>
                <input type="password" name="password" placeholder="Min 6 characters"
                       <?= $action !== 'edit' ? 'required' : '' ?>>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name"
                       value="<?= htmlspecialchars($editData['full_name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email"
                       value="<?= htmlspecialchars($editData['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone"
                       value="<?= htmlspecialchars($editData['phone'] ?? '') ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Location</label>
                <input type="text" name="location"
                       value="<?= htmlspecialchars($editData['location'] ?? '') ?>"
                       placeholder="Dar es Salaam, Kinondoni, etc.">
            </div>
            <div class="form-group">
                <label>Role</label>
                <select name="role">
                    <option value="mechanic" <?= ($editData['role'] ?? '') === 'mechanic' ? 'selected' : '' ?>>Mechanic</option>
                    <option value="admin"    <?= ($editData['role'] ?? '') === 'admin'    ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Status</label>
                <label style="margin-top:10px;display:flex;align-items:center;gap:8px;cursor:pointer;">
                    <input type="checkbox" name="is_active" value="1"
                           <?= ($editData['is_active'] ?? 1) ? 'checked' : '' ?>>
                    Active Account
                </label>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn-primary"><?= $action === 'edit' ? 'Update User' : 'Create User' ?></button>
            <a href="<?= BASE_URL ?>/pages/users.php" class="btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h3>System Users</h3>
        <span class="badge"><?= count($users) ?> users</span>
    </div>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>#</th><th>Username</th><th>Full Name</th><th>Email</th><th>Phone</th><th>Location</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><?= $u['id'] ?></td>
                <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                <td><?= htmlspecialchars($u['full_name'] ?? '') ?></td>
                <td><?= htmlspecialchars($u['email']     ?? '') ?></td>
                <td><?= htmlspecialchars($u['phone']     ?? '') ?></td>
                <td><?= htmlspecialchars($u['location']  ?? '') ?></td>
                <td><span class="badge badge-<?= $u['role'] === 'admin' ? 'completed' : 'in-progress' ?>"><?= ucfirst($u['role']) ?></span></td>
                <td><span class="badge badge-<?= $u['is_active'] ? 'completed' : 'cancelled' ?>"><?= $u['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                <td class="actions">
                    <a href="?action=edit&id=<?= $u['id'] ?>" class="btn-sm btn-edit">Edit</a>
                    <?php if ($u['id'] !== currentUserId()): ?>
                    <a href="?action=delete&id=<?= $u['id'] ?>" class="btn-sm btn-delete"
                       onclick="return confirm('Delete this user?')">Delete</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
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
document.getElementById('user-form').style.display = 'block';
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
