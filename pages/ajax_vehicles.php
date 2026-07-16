<?php
// pages/ajax_vehicles.php — Returns vehicles for a customer as JSON
require_once __DIR__ . '/../classes/Vehicle.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Customer.php';
require_once __DIR__ . '/../includes/session.php';

requireLogin();
header('Content-Type: application/json');

$customerId = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;
if (!$customerId) {
    echo json_encode([]);
    exit();
}

$vehicleModel = new Vehicle();
$vehicles     = $vehicleModel->getByCustomer($customerId);

// Return only needed fields
$result = array_map(fn($v) => [
    'id'           => $v['id'],
    'plate_number' => $v['plate_number'] ?? '',
    'make'         => $v['make']         ?? '',
    'model'        => $v['model']        ?? '',
], $vehicles);

echo json_encode($result);
