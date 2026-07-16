<?php
// pages/products.php — Admin: Manage Products / Spare Parts
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Product.php';
require_once __DIR__ . '/../classes/Category.php';
require_once __DIR__ . '/../includes/header.php';

requireAdmin();

$model         = new Product();
$categoryModel = new Category();
$action        = $_GET['action'] ?? 'list';
$id            = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$errors        = [];
$categories    = $categoryModel->getAll();

function productUploadDir(): string {
    return __DIR__ . '/../uploads/products/';
}

function handleProductImageUpload(?string $existingPath = null): string|false|null {
    if (!isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
        return $existingPath;
    }

    if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext     = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed, true)) {
        return false;
    }

    if ($_FILES['image']['size'] > 2 * 1024 * 1024) {
        return false;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $_FILES['image']['tmp_name']);
    finfo_close($finfo);

    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($mime, $allowedMimes, true)) {
        return false;
    }

    $uploadDir = productUploadDir();
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = 'product_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
        return false;
    }

    if ($existingPath && str_contains($existingPath, '/uploads/products/')) {
        $oldFile = $uploadDir . basename($existingPath);
        if (is_file($oldFile)) {
            unlink($oldFile);
        }
    }

    return BASE_URL . '/uploads/products/' . $filename;
}

function deleteProductImage(?string $imagePath): void {
    if (!$imagePath || !str_contains($imagePath, '/uploads/products/')) {
        return;
    }
    $file = productUploadDir() . basename($imagePath);
    if (is_file($file)) {
        unlink($file);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $postAction = $_POST['action'] ?? '';

    if (empty($_POST['name']))  $errors[] = 'Product name is required.';
    if (!isset($_POST['price']) || $_POST['price'] === '') $errors[] = 'Price is required.';
    if (!isset($_POST['stock']) || $_POST['stock'] === '') $errors[] = 'Stock quantity is required.';

    $existingImage = null;
    if ($postAction === 'update' && $id) {
        $existing = $model->findById($id);
        $existingImage = $existing['image_path'] ?? null;
    }

    $imagePath = null;
    if (empty($errors)) {
        $uploadResult = handleProductImageUpload($existingImage);
        if ($uploadResult === false) {
            $errors[] = 'Invalid image. Use JPG, PNG, GIF or WEBP (max 2MB).';
        } else {
            $imagePath = $uploadResult;
        }
    }

    if (empty($errors)) {
        $data = [
            'category_id' => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
            'name'        => trim($_POST['name']),
            'description' => trim($_POST['description'] ?? ''),
            'image_path'  => $imagePath,
            'price'       => (float)$_POST['price'],
            'stock'       => (int)$_POST['stock'],
        ];

        if ($postAction === 'create') {
            $model->create($data)
                ? redirectWith(BASE_URL . '/pages/products.php', 'success', 'Product added successfully.')
                : redirectWith(BASE_URL . '/pages/products.php', 'danger',  'Failed to add product.');
        } elseif ($postAction === 'update' && $id) {
            $model->update($id, $data)
                ? redirectWith(BASE_URL . '/pages/products.php', 'success', 'Product updated successfully.')
                : redirectWith(BASE_URL . '/pages/products.php', 'danger',  'Failed to update product.');
        }
    }
}

if ($action === 'delete' && $id) {
    $product = $model->findById($id);
    if ($product) {
        deleteProductImage($product['image_path'] ?? null);
    }
    $model->delete($id)
        ? redirectWith(BASE_URL . '/pages/products.php', 'success', 'Product deleted.')
        : redirectWith(BASE_URL . '/pages/products.php', 'danger',  'Failed to delete product.');
}

$editData = ($action === 'edit' && $id) ? $model->findById($id) : null;
$search   = trim($_GET['search'] ?? '');
$filter   = isset($_GET['category']) ? (int)$_GET['category'] : 0;

if ($search) {
    $products = $model->search($search);
} elseif ($filter) {
    $products = $model->getByCategory($filter);
} else {
    $products = $model->getAll();
}
?>

<div class="page-header">
    <h2>🛒 Products / Spare Parts</h2>
    <div>
        <a href="<?= BASE_URL ?>/pages/categories.php" class="btn-secondary">Categories</a>
        <button onclick="toggleForm('add-form')" class="btn-primary">+ Add Product</button>
    </div>
</div>

<div id="add-form" class="card form-card" style="display:none;">
    <h3><?= $action === 'edit' ? 'Edit Product' : 'Add New Product' ?></h3>
    <?php if ($errors): ?>
        <div class="alert alert-danger"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
    <?php endif; ?>
    <form method="POST" action="?<?= $action === 'edit' ? "action=edit&id=$id" : '' ?>" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="action" value="<?= $action === 'edit' ? 'update' : 'create' ?>">

        <div class="form-row">
            <div class="form-group">
                <label>Product Name *</label>
                <input type="text" name="name"
                       value="<?= htmlspecialchars($editData['name'] ?? $_POST['name'] ?? '') ?>"
                       placeholder="e.g. Oil Filter" required>
            </div>
            <div class="form-group">
                <label>Category</label>
                <select name="category_id">
                    <option value="">— No category —</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>"
                        <?= (string)($editData['category_id'] ?? $_POST['category_id'] ?? '') === (string)$cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Description</label>
            <textarea name="description" rows="3"
                      placeholder="Product details, compatibility, etc."><?= htmlspecialchars($editData['description'] ?? $_POST['description'] ?? '') ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Price (TZS) *</label>
                <input type="number" name="price" min="0" step="0.01"
                       value="<?= htmlspecialchars($editData['price'] ?? $_POST['price'] ?? '0') ?>" required>
            </div>
            <div class="form-group">
                <label>Stock *</label>
                <input type="number" name="stock" min="0"
                       value="<?= htmlspecialchars($editData['stock'] ?? $_POST['stock'] ?? '0') ?>" required>
            </div>
            <div class="form-group">
                <label>Product Image</label>
                <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
                <small>JPG, PNG, GIF or WEBP — max 2MB</small>
                <?php if (!empty($editData['image_path'])): ?>
                <div style="margin-top:8px;">
                    <img src="<?= htmlspecialchars($editData['image_path']) ?>" alt="Current image"
                         style="max-width:120px;max-height:120px;border-radius:6px;">
                    <small>Current image (upload new to replace)</small>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary">
                <?= $action === 'edit' ? 'Update Product' : 'Save Product' ?>
            </button>
            <a href="<?= BASE_URL ?>/pages/products.php" class="btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<div class="card">
    <form method="GET" action="" class="search-form">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
               placeholder="Search products...">
        <select name="category">
            <option value="">All categories</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= $filter === (int)$cat['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-primary">Filter</button>
        <?php if ($search || $filter): ?>
            <a href="<?= BASE_URL ?>/pages/products.php" class="btn-secondary">Clear</a>
        <?php endif; ?>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h3>All Products</h3>
        <span class="badge"><?= count($products) ?> records</span>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price (TZS)</th>
                    <th>Stock</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                <tr><td colspan="7" class="empty">No products found. Add categories first, then products.</td></tr>
                <?php else: ?>
                <?php foreach ($products as $p): ?>
                <tr>
                    <td><?= $p['id'] ?></td>
                    <td>
                        <?php if (!empty($p['image_path'])): ?>
                            <img src="<?= htmlspecialchars($p['image_path']) ?>" alt=""
                                 style="width:48px;height:48px;object-fit:cover;border-radius:4px;">
                        <?php else: ?>
                            <span class="empty">—</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($p['name'] ?? '') ?></td>
                    <td><?= htmlspecialchars($p['category_name'] ?? '—') ?></td>
                    <td><?= number_format((float)($p['price'] ?? 0), 0) ?></td>
                    <td>
                        <?php $stock = (int)($p['stock'] ?? 0); ?>
                        <span style="color:<?= $stock === 0 ? 'var(--danger)' : 'inherit' ?>"><?= $stock ?></span>
                    </td>
                    <td class="actions">
                        <a href="?action=edit&id=<?= $p['id'] ?>" class="btn-sm btn-edit">Edit</a>
                        <a href="?action=delete&id=<?= $p['id'] ?>"
                           class="btn-sm btn-delete"
                           onclick="return confirm('Delete this product permanently?')">
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
<?php if ($action === 'edit' || $errors): ?>
document.getElementById('add-form').style.display = 'block';
window.scrollTo(0, 0);
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
