<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Customer.php';
require_once __DIR__ . '/../classes/Vehicle.php';
require_once __DIR__ . '/../classes/Service.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/ServiceRequest.php';
require_once __DIR__ . '/../includes/public_header.php';

$serviceModel = new Service();
$customerModel = new Customer();
$vehicleModel = new Vehicle();
$mechanicModel = new User();
$requestModel = new ServiceRequest();

$services = $serviceModel->getAll();
$customers = $customerModel->getAll();
$vehicles = [];
$message = '';
$errors = [];
$customerId = 0;

if (isCustomer()) {
    $customerId = currentUserId();
    $customers = [];
} else {
    $customerId = (int)($_POST['customer_id'] ?? $_GET['customer_id'] ?? 0);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerId = isCustomer() ? currentUserId() : (int)($_POST['customer_id'] ?? 0);
    $serviceId = (int)($_POST['service_id'] ?? 0);
    $issue = trim($_POST['issue_description'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $preferredDate = trim($_POST['preferred_date'] ?? '');

    if (!$customerId) $errors[] = 'Choose your customer profile.';
    if (!$serviceId) $errors[] = 'Choose a service.';
    if ($issue === '') $errors[] = 'Describe the issue.';
    if ($location === '') $errors[] = 'Provide your location.';

    if (empty($errors)) {
        $matchingMechanics = $mechanicModel->getMechanicsByLocation($location);
        $assignedMechanicId = null;
        if (!empty($matchingMechanics)) {
            $firstMatch = reset($matchingMechanics);
            $assignedMechanicId = $firstMatch['id'] ?? null;
        }

        $data = [
            'customer_id' => $customerId,
            'vehicle_id' => !empty($_POST['vehicle_id']) ? (int)$_POST['vehicle_id'] : null,
            'service_id' => $serviceId,
            'issue_description' => $issue,
            'location' => $location,
            'preferred_date' => $preferredDate ?: null,
            'assigned_mechanic_id' => $assignedMechanicId,
            'status' => $assignedMechanicId ? 'in-progress' : 'pending',
            'created_by' => null,
        ];

        if ($requestModel->create($data)) {
            if ($assignedMechanicId) {
                $_SESSION['flash'] = ['message' => 'Service request submitted and assigned to a mechanic in your area.'];
            } else {
                $_SESSION['flash'] = ['message' => 'Service request submitted successfully! An available mechanic will be assigned soon.'];
            }
            header('Location: ' . BASE_URL . '/pages/request_service.php');
            exit();
        }

        $errors[] = 'Unable to submit request. Please try again.';
    }
}

if (!empty($_GET['customer_id']) && !isCustomer()) {
    $vehicles = $vehicleModel->getByCustomer((int)$_GET['customer_id']);
} elseif ($customerId) {
    $vehicles = $vehicleModel->getByCustomer($customerId);
}
?>

<section class="section">
    <div class="section-header">
        <h2>Request a Mobile Mechanic</h2>
        <p>Fill in your details and our mechanic will come to your location.</p>
    </div>
    <div class="card form-card">
        <?php if ($errors): ?>
            <div class="alert alert-danger"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-row">
                <?php if (!isCustomer()): ?>
                <div class="form-group">
                    <label>Customer Profile</label>
                    <select name="customer_id" onchange="this.form.submit()" required>
                        <option value="">-- Select Customer --</option>
                        <?php foreach ($customers as $customer): ?>
                            <option value="<?= $customer['id'] ?>" <?= isset($_POST['customer_id']) && $_POST['customer_id'] == $customer['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($customer['full_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php else: ?>
                <input type="hidden" name="customer_id" value="<?= (int)$customerId ?>">
                <?php endif; ?>
                <div class="form-group">
                    <label>Vehicle (optional)</label>
                    <select name="vehicle_id">
                        <option value="">-- Select Vehicle --</option>
                        <?php foreach ($vehicles as $vehicle): ?>
                        <option value="<?= $vehicle['id'] ?>" <?= isset($_POST['vehicle_id']) && $_POST['vehicle_id'] == $vehicle['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($vehicle['plate_number']) ?> (<?= htmlspecialchars($vehicle['make']) ?> <?= htmlspecialchars($vehicle['model']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Service Needed</label>
                    <select name="service_id" required>
                        <option value="">-- Select Service --</option>
                        <?php foreach ($services as $service): ?>
                            <option value="<?= $service['id'] ?>" <?= isset($_POST['service_id']) && $_POST['service_id'] == $service['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($service['service_name']) ?> - TZS <?= number_format((float)$service['base_price'], 0) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Preferred Date</label>
                    <input type="date" name="preferred_date" value="<?= htmlspecialchars($_POST['preferred_date'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Issue Description</label>
                <textarea name="issue_description" placeholder="What is wrong with the vehicle?" required><?= htmlspecialchars($_POST['issue_description'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>Location</label>
                <input type="text" name="location" value="<?= htmlspecialchars($_POST['location'] ?? '') ?>" placeholder="Enter full location" required>
            </div>

            <button type="submit" class="btn-primary">Submit Request</button>
        </form>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/public_footer.php'; ?>
