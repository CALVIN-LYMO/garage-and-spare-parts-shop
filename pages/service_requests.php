<?php
// pages/service_requests.php — Manage Service Requests and Mechanic Assignments
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/ServiceRequest.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../includes/header.php';

if (!isAdmin() && !isMechanic()) {
    header('Location: ' . BASE_URL . '/pages/dashboard.php?error=access_denied');
    exit();
}

$requestModel = new ServiceRequest();
$userModel = new User();
$mechanics = $userModel->getMechanics();
$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'assign' && $id) {
        $mechanicId = (int)($_POST['mechanic_id'] ?? 0);
        if (!$mechanicId) {
            $errors[] = 'Select a mechanic to assign.';
        } else {
            $success = $requestModel->assignMechanic($id, $mechanicId, 'in-progress', 'Assigned by admin.');
            if ($success) {
                redirectWith(BASE_URL . '/pages/service_requests.php', 'success', 'Mechanic assigned successfully.');
            }
            $errors[] = 'Failed to assign mechanic.';
        }
    }

    if ($postAction === 'update_status' && $id) {
        $status = $_POST['status'] ?? 'pending';
        if (!in_array($status, ['pending', 'in-progress', 'completed', 'cancelled'], true)) {
            $errors[] = 'Invalid status selected.';
        } else {
            $success = $requestModel->update($id, ['status' => $status]);
            if ($success) {
                redirectWith(BASE_URL . '/pages/service_requests.php', 'success', 'Service request status updated.');
            }
            $errors[] = 'Failed to update status.';
        }
    }
}

if ($action === 'delete' && $id) {
    $requestModel->delete($id);
    redirectWith(BASE_URL . '/pages/service_requests.php', 'success', 'Service request deleted.');
}

$serviceRequests = isAdmin()
    ? $requestModel->getAll()
    : $requestModel->getByMechanic(currentUserId());
?>

<div class="page-header">
    <h2>📄 Service Requests</h2>
    <p><?= isAdmin() ? 'Manage pending requests, assign mechanics, and track status.' : 'View your assigned service requests and update their progress.' ?></p>
</div>

<?php if ($errors): ?>
    <div class="alert alert-danger"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3><?= isAdmin() ? 'All Service Requests' : 'My Assigned Requests' ?></h3>
        <span class="badge"><?= count($serviceRequests) ?> requests</span>
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
                    <th>Preferred</th>
                    <th>Status</th>
                    <th>Mechanic</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($serviceRequests)): ?>
                    <tr><td colspan="9" class="empty">No service requests found.</td></tr>
                <?php else: ?>
                    <?php foreach ($serviceRequests as $req): ?>
                        <tr>
                            <td><?= htmlspecialchars($req['id']) ?></td>
                            <td><?= htmlspecialchars($req['customer_name'] ?? '') ?></td>
                            <td><?= htmlspecialchars($req['service_name'] ?? 'General Service') ?></td>
                            <td><?= htmlspecialchars(substr($req['issue_description'] ?? '', 0, 40)) ?>...</td>
                            <td><?= htmlspecialchars($req['location'] ?? '') ?></td>
                            <td><?= htmlspecialchars($req['preferred_date'] ?? 'N/A') ?></td>
                            <td><span class="badge badge-<?= htmlspecialchars(str_replace(' ', '-', strtolower($req['status'] ?? 'pending'))) ?>">
                                <?= htmlspecialchars(ucfirst($req['status'] ?? 'pending')) ?>
                            </span></td>
                            <td><?= htmlspecialchars($req['mechanic_name'] ?? 'Unassigned') ?></td>
                            <td class="actions">
                                <?php if (isAdmin() && $req['status'] === 'pending'): ?>
                                    <form method="POST" action="?id=<?= $req['id'] ?>" style="display:inline-block; margin-bottom: 6px;">
                                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                        <input type="hidden" name="action" value="assign">
                                        <select name="mechanic_id" required>
                                            <option value="">Assign mechanic</option>
                                            <?php foreach ($mechanics as $mechanic): ?>
                                                <option value="<?= $mechanic['id'] ?>"><?= htmlspecialchars($mechanic['full_name'] . ' — ' . ($mechanic['location'] ?? 'No location')) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn-sm btn-primary">Assign</button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" action="?id=<?= $req['id'] ?>" style="display:inline-block;">
                                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                    <input type="hidden" name="action" value="update_status">
                                    <select name="status" onchange="this.form.submit()">
                                        <?php foreach (['pending', 'in-progress', 'completed', 'cancelled'] as $status): ?>
                                            <option value="<?= $status ?>" <?= ($req['status'] ?? '') === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                                <?php if (isAdmin()): ?>
                                <a href="?action=delete&id=<?= $req['id'] ?>" class="btn-sm btn-delete" onclick="return confirm('Delete this request?')">Delete</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
