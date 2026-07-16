<?php
// pages/categories.php — Admin: Manage Product Categories
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Category.php';
require_once __DIR__ . '/../includes/header.php';

requireAdmin();

$model  = new Category();
$action = $_GET['action'] ?? 'list';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $postAction = $_POST['action'] ?? '';

    if (empty($_POST['name'])) {
        $errors[] = 'Category name is required.';
    }

    if (empty($errors)) {
        $data = [
            'name'        => trim($_POST['name']),
            'description' => trim($_POST['description'] ?? ''),
        ];

        if ($postAction === 'create') {
            $model->create($data)
                ? redirectWith(BASE_URL . '/pages/categories.php', 'success', 'Category added successfully.')
                : redirectWith(BASE_URL . '/pages/categories.php', 'danger',  'Failed to add category.');
        } elseif ($postAction === 'update' && $id) {
            $model->update($id, $data)
                ? redirectWith(BASE_URL . '/pages/categories.php', 'success', 'Category updated successfully.')
                : redirectWith(BASE_URL . '/pages/categories.php', 'danger',  'Failed to update category.');
        }
    }
}

if ($action === 'delete' && $id) {
    $model->delete($id)
        ? redirectWith(BASE_URL . '/pages/categories.php', 'success', 'Category deleted.')
        : redirectWith(BASE_URL . '/pages/categories.php', 'danger',  'Failed to delete category.');
}

$editData   = ($action === 'edit' && $id) ? $model->findById($id) : null;
$search     = trim($_GET['search'] ?? '');
$categories = $search ? $model->search($search) : $model->getAll();
?>

<div class="page-header">
    <h2>📂 Categories</h2>
    <div>
        <a href="<?= BASE_URL ?>/pages/products.php" class="btn-secondary">← Products</a>
        <button onclick="toggleForm('add-form')" class="btn-primary">+ Add Category</button>
    </div>
</div>

<div id="add-form" class="card form-card" style="display:none;">
    <h3><?= $action === 'edit' ? 'Edit Category' : 'Add New Category' ?></h3>
    <?php if ($errors): ?>
        <div class="alert alert-danger"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
    <?php endif; ?>
    <form method="POST" action="?<?= $action === 'edit' ? "action=edit&id=$id" : '' ?>">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="action" value="<?= $action === 'edit' ? 'update' : 'create' ?>">

        <div class="form-row">
            <div class="form-group">
                <label>Category Name *</label>
                <input type="text" name="name"
                       value="<?= htmlspecialchars($editData['name'] ?? $_POST['name'] ?? '') ?>"
                       placeholder="e.g. Engine Parts" required>
            </div>
        </div>
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" rows="3"
                      placeholder="Brief description of this category"><?= htmlspecialchars($editData['description'] ?? $_POST['description'] ?? '') ?></textarea>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn-primary">
                <?= $action === 'edit' ? 'Update Category' : 'Save Category' ?>
            </button>
            <a href="<?= BASE_URL ?>/pages/categories.php" class="btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<div class="card">
    <form method="GET" action="" class="search-form">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
               placeholder="Search categories...">
        <button type="submit" class="btn-primary">Search</button>
        <?php if ($search): ?>
            <a href="<?= BASE_URL ?>/pages/categories.php" class="btn-secondary">Clear</a>
        <?php endif; ?>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h3>All Categories <?= $search ? "(Results for: \"$search\")" : '' ?></h3>
        <span class="badge"><?= count($categories) ?> records</span>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categories)): ?>
                <tr><td colspan="5" class="empty">No categories found.</td></tr>
                <?php else: ?>
                <?php foreach ($categories as $cat): ?>
                <tr>
                    <td><?= $cat['id'] ?></td>
                    <td><?= htmlspecialchars($cat['name'] ?? '') ?></td>
                    <td><?= htmlspecialchars($cat['description'] ?? '') ?></td>
                    <td><?= htmlspecialchars($cat['created_at'] ?? '') ?></td>
                    <td class="actions">
                        <a href="?action=edit&id=<?= $cat['id'] ?>" class="btn-sm btn-edit">Edit</a>
                        <a href="?action=delete&id=<?= $cat['id'] ?>"
                           class="btn-sm btn-delete"
                           onclick="return confirm('Delete this category? Products in it will become uncategorized.')">
                           Delete
                        </a>
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
