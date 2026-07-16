<?php
/**
 * seed_catalog.php — Run ONCE to populate categories, products (with images), and services.
 * Usage: php seed_catalog.php   OR open in browser: http://localhost/garage_system/seed_catalog.php
 * DELETE this file after running on production!
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Category.php';
require_once __DIR__ . '/classes/Product.php';
require_once __DIR__ . '/classes/Service.php';

$uploadDir = __DIR__ . '/uploads/products/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

function downloadImage(string $url, string $destPath): bool {
    if (is_file($destPath) && filesize($destPath) > 1000) {
        return true;
    }

    $ctx = stream_context_create([
        'http' => [
            'timeout' => 30,
            'header' => "User-Agent: GarageSystem-Seed/1.0\r\n",
        ],
        'ssl' => ['verify_peer' => true],
    ]);

    $data = @file_get_contents($url, false, $ctx);
    if ($data === false || strlen($data) < 1000) {
        return false;
    }

    $ext = strtolower(pathinfo($destPath, PATHINFO_EXTENSION));
    if ($ext === 'jpg' || $ext === 'jpeg') {
        $img = @imagecreatefromstring($data);
        if ($img) {
            $w = imagesx($img);
            $h = imagesy($img);
            $maxW = 800;
            if ($w > $maxW) {
                $newH = (int)($h * ($maxW / $w));
                $resized = imagecreatetruecolor($maxW, $newH);
                imagecopyresampled($resized, $img, 0, 0, 0, 0, $maxW, $newH, $w, $h);
                imagejpeg($resized, $destPath, 85);
                imagedestroy($resized);
            } else {
                imagejpeg($img, $destPath, 85);
            }
            imagedestroy($img);
            return is_file($destPath);
        }
    }

    return file_put_contents($destPath, $data) !== false;
}

$categories = [
    ['name' => 'Engine Parts', 'description' => 'Vipengele vya injini: filters, belts, plugs na zaidi.'],
    ['name' => 'Brake System', 'description' => 'Pads, discs, fluid na vipengele vya braking system.'],
    ['name' => 'Filters & Fluids', 'description' => 'Mafuta, coolant, air filters na fluids za gari.'],
    ['name' => 'Electrical', 'description' => 'Battery, alternator, starter na vipengele vya umeme.'],
    ['name' => 'Suspension', 'description' => 'Shocks, springs na vipengele vya suspension.'],
    ['name' => 'Body & Lighting', 'description' => 'Wipers, mirrors, taa na vipengele vya mwili wa gari.'],
];

$products = [
    [
        'file' => 'oil_filter.jpg',
        'url'  => 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/8a/Engine_oil_filter.JPG/640px-Engine_oil_filter.JPG',
        'cat'  => 'Engine Parts',
        'name' => 'Engine Oil Filter',
        'desc' => 'High-quality spin-on oil filter compatible with Toyota, Nissan and Honda engines. Removes contaminants and protects your engine. Replace every 5,000–10,000 km.',
        'price' => 25000, 'stock' => 45,
    ],
    [
        'file' => 'spark_plugs.jpg',
        'url'  => 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/52/Spark_plugs.jpg/640px-Spark_plugs.jpg',
        'cat'  => 'Engine Parts',
        'name' => 'Spark Plugs Set (4pc)',
        'desc' => 'Iridium-tipped spark plugs set of 4. Better fuel economy, smoother idle and reliable ignition for petrol engines.',
        'price' => 48000, 'stock' => 30,
    ],
    [
        'file' => 'timing_belt.jpg',
        'url'  => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/4d/Timing_belt.jpg/640px-Timing_belt.jpg',
        'cat'  => 'Engine Parts',
        'name' => 'Timing Belt Kit',
        'desc' => 'Complete timing belt kit with tensioner and idler pulley. Essential for engine timing — replace per manufacturer schedule (typically 80,000–100,000 km).',
        'price' => 185000, 'stock' => 12,
    ],
    [
        'file' => 'serpentine_belt.jpg',
        'url'  => 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/6e/Serpentine_belt.jpg/640px-Serpentine_belt.jpg',
        'cat'  => 'Engine Parts',
        'name' => 'Serpentine Drive Belt',
        'desc' => 'Multi-rib serpentine belt drives alternator, power steering and A/C compressor. Durable EPDM rubber for hot climates.',
        'price' => 35000, 'stock' => 28,
    ],
    [
        'file' => 'brake_pads.jpg',
        'url'  => 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/2a/Brake_pad.jpg/640px-Brake_pad.jpg',
        'cat'  => 'Brake System',
        'name' => 'Front Brake Pads Set',
        'desc' => 'Ceramic front brake pads with low dust and quiet operation. Fits most Japanese sedans and SUVs. Includes hardware kit.',
        'price' => 75000, 'stock' => 22,
    ],
    [
        'file' => 'brake_disc.jpg',
        'url'  => 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/1b/Disc_brake_dsc03682.jpg/640px-Disc_brake_dsc03682.jpg',
        'cat'  => 'Brake System',
        'name' => 'Rear Brake Disc (Pair)',
        'desc' => 'Ventilated rear brake discs sold as a pair. Precision machined for smooth braking and reduced vibration.',
        'price' => 120000, 'stock' => 15,
    ],
    [
        'file' => 'brake_fluid.jpg',
        'url'  => 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/9e/DOT_4_brake_fluid.jpg/640px-DOT_4_brake_fluid.jpg',
        'cat'  => 'Brake System',
        'name' => 'Brake Fluid DOT 4 (500ml)',
        'desc' => 'Premium DOT 4 brake fluid with high boiling point. Suitable for ABS and standard braking systems. Change every 2 years.',
        'price' => 18000, 'stock' => 50,
    ],
    [
        'file' => 'air_filter.jpg',
        'url'  => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/4e/Air_filter_for_car.jpg/640px-Air_filter_for_car.jpg',
        'cat'  => 'Filters & Fluids',
        'name' => 'Engine Air Filter',
        'desc' => 'Panel-type engine air filter improves airflow and fuel efficiency. Traps dust and pollen — replace every 15,000 km in dusty conditions.',
        'price' => 22000, 'stock' => 40,
    ],
    [
        'file' => 'cabin_filter.jpg',
        'url'  => 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/87/Cabin_air_filter.jpg/640px-Cabin_air_filter.jpg',
        'cat'  => 'Filters & Fluids',
        'name' => 'Cabin Air Filter',
        'desc' => 'Activated carbon cabin filter removes dust, pollen and odors from AC system. Keeps interior air fresh and clean.',
        'price' => 28000, 'stock' => 35,
    ],
    [
        'file' => 'engine_oil.jpg',
        'url'  => 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/66/Motor_oil.jpg/640px-Motor_oil.jpg',
        'cat'  => 'Filters & Fluids',
        'name' => 'Engine Oil 5W-30 (4L)',
        'desc' => 'Fully synthetic 5W-30 engine oil 4-litre bottle. Excellent protection in hot and cold conditions. API SN/CF certified.',
        'price' => 65000, 'stock' => 25,
    ],
    [
        'file' => 'coolant.jpg',
        'url'  => 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3a/Antifreeze.jpg/640px-Antifreeze.jpg',
        'cat'  => 'Filters & Fluids',
        'name' => 'Coolant Antifreeze (5L)',
        'desc' => 'Long-life ethylene glycol coolant concentrate. Prevents overheating and corrosion. Mix 50/50 with distilled water.',
        'price' => 42000, 'stock' => 20,
    ],
    [
        'file' => 'car_battery.jpg',
        'url'  => 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/9f/Car_battery.jpg/640px-Car_battery.jpg',
        'cat'  => 'Electrical',
        'name' => 'Car Battery 12V 60Ah',
        'desc' => 'Maintenance-free 12V 60Ah car battery with 540 CCA. Reliable starting power for sedans and small SUVs. 18-month warranty.',
        'price' => 280000, 'stock' => 18,
    ],
    [
        'file' => 'alternator.jpg',
        'url'  => 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/5a/Alternator.jpg/640px-Alternator.jpg',
        'cat'  => 'Electrical',
        'name' => 'Alternator 90A',
        'desc' => 'Remanufactured 90-amp alternator. Charges battery and powers electrical systems while engine runs. Direct fit replacement.',
        'price' => 195000, 'stock' => 10,
    ],
    [
        'file' => 'starter_motor.jpg',
        'url'  => 'https://upload.wikimedia.org/wikipedia/commons/thumb/d/d4/Starter_motor.jpg/640px-Starter_motor.jpg',
        'cat'  => 'Electrical',
        'name' => 'Starter Motor',
        'desc' => 'Heavy-duty starter motor for reliable cold starts. Compatible with common 4-cylinder petrol engines.',
        'price' => 165000, 'stock' => 8,
    ],
    [
        'file' => 'headlight_bulb.jpg',
        'url'  => 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/27/H4_bulb.jpg/640px-H4_bulb.jpg',
        'cat'  => 'Electrical',
        'name' => 'Headlight Bulb H4 (Pair)',
        'desc' => 'Halogen H4 headlight bulbs — pair. Brighter beam for night driving. Easy plug-and-play installation.',
        'price' => 15000, 'stock' => 60,
    ],
    [
        'file' => 'shock_absorber.jpg',
        'url'  => 'https://upload.wikimedia.org/wikipedia/commons/thumb/0/0c/Shock_absorber.jpg/640px-Shock_absorber.jpg',
        'cat'  => 'Suspension',
        'name' => 'Front Shock Absorber',
        'desc' => 'Gas-charged front shock absorber for improved ride comfort and handling. Reduces body roll and absorbs road bumps.',
        'price' => 85000, 'stock' => 16,
    ],
    [
        'file' => 'coil_spring.jpg',
        'url'  => 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/1a/Coil_spring.jpg/640px-Coil_spring.jpg',
        'cat'  => 'Suspension',
        'name' => 'Coil Spring (Front)',
        'desc' => 'Heavy-duty coil spring restores proper ride height and suspension geometry. Powder-coated for corrosion resistance.',
        'price' => 55000, 'stock' => 14,
    ],
    [
        'file' => 'wiper_blades.jpg',
        'url'  => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/4b/Windshield_wiper.jpg/640px-Windshield_wiper.jpg',
        'cat'  => 'Body & Lighting',
        'name' => 'Windshield Wipers (Pair)',
        'desc' => 'All-weather windshield wiper blades — 24" + 18" pair. Streak-free wiping in rain. Universal adapter included.',
        'price' => 32000, 'stock' => 38,
    ],
    [
        'file' => 'side_mirror.jpg',
        'url'  => 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/62/Side-view_mirror.jpg/640px-Side-view_mirror.jpg',
        'cat'  => 'Body & Lighting',
        'name' => 'Side Mirror (Left)',
        'desc' => 'Electric adjustable side mirror with heated glass option. Direct replacement for common sedan models.',
        'price' => 95000, 'stock' => 9,
    ],
    [
        'file' => 'tail_light.jpg',
        'url'  => 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/8d/Tail_lamp.jpg/640px-Tail_lamp.jpg',
        'cat'  => 'Body & Lighting',
        'name' => 'Tail Light Assembly',
        'desc' => 'Complete rear tail light assembly with bulbs. DOT approved lens for visibility and safety compliance.',
        'price' => 78000, 'stock' => 11,
    ],
];

$services = [
    ['service_name' => 'Mobile Diagnostics', 'base_price' => '50000', 'description' => 'On-site vehicle inspection and fault code reading at your location.'],
    ['service_name' => 'Oil Change Service', 'base_price' => '80000', 'description' => 'Full oil and filter change with disposal of used oil.'],
    ['service_name' => 'Brake Inspection & Repair', 'base_price' => '120000', 'description' => 'Brake pad replacement, disc inspection and fluid top-up.'],
    ['service_name' => 'Battery Replacement', 'base_price' => '35000', 'description' => 'Mobile battery testing, removal and installation of new battery.'],
    ['service_name' => 'AC System Service', 'base_price' => '150000', 'description' => 'AC gas refill, leak check and compressor inspection.'],
    ['service_name' => 'Emergency Breakdown', 'base_price' => '75000', 'description' => '24/7 roadside assistance for breakdowns anywhere in Dar es Salaam.'],
];

$categoryModel = new Category();
$productModel  = new Product();
$serviceModel  = new Service();

$existingProducts = $productModel->count();
$force = in_array('--force', $argv ?? [], true) || isset($_GET['force']);

$log = [];
$log[] = '<h2>Garage Catalog Seed</h2>';

if ($existingProducts > 0 && !$force) {
    $log[] = "<p>⚠️ Database already has {$existingProducts} product(s). ";
    $log[] = "Add <code>?force=1</code> to URL or run <code>php seed_catalog.php --force</code> to re-seed.</p>";
    echo implode('', $log);
    exit;
}

if ($force && $existingProducts > 0) {
    $db = DatabaseConnection::getInstance()->getConnection();
    $db->exec('DELETE FROM order_items');
    $db->exec('DELETE FROM orders');
    $db->exec('DELETE FROM products');
    $db->exec('DELETE FROM categories');
    $log[] = '<p>🔄 Cleared existing products and categories.</p>';
}

$catIds = [];
foreach ($categories as $cat) {
    $categoryModel->create($cat);
    $all = $categoryModel->getAll();
    foreach ($all as $c) {
        if ($c['name'] === $cat['name']) {
            $catIds[$cat['name']] = $c['id'];
            break;
        }
    }
}
$log[] = '<p>✅ Created ' . count($categories) . ' categories.</p>';

$downloaded = 0;
$failed = 0;
$created = 0;

foreach ($products as $p) {
    $dest = $uploadDir . $p['file'];
    $imagePath = null;

    if (downloadImage($p['url'], $dest)) {
        $imagePath = BASE_URL . '/uploads/products/' . $p['file'];
        $downloaded++;
    } else {
        $failed++;
        $log[] = "<p>⚠️ Could not download image for {$p['name']} — product saved without image.</p>";
    }

    $productModel->create([
        'category_id' => $catIds[$p['cat']] ?? null,
        'name'        => $p['name'],
        'description' => $p['desc'],
        'image_path'  => $imagePath,
        'price'       => $p['price'],
        'stock'       => $p['stock'],
    ]);
    $created++;
}

$log[] = "<p>✅ Created {$created} products ({$downloaded} images downloaded, {$failed} failed).</p>";

$serviceCount = $serviceModel->count();
if ($serviceCount === 0 || $force) {
    if ($force && $serviceCount > 0) {
        $db = DatabaseConnection::getInstance()->getConnection();
        $db->exec('DELETE FROM services');
    }
    foreach ($services as $svc) {
        $serviceModel->create($svc);
    }
    $log[] = '<p>✅ Created ' . count($services) . ' services.</p>';
} else {
    $log[] = "<p>ℹ️ Services already exist ({$serviceCount}), skipped.</p>";
}

$log[] = '<p><strong>Done!</strong> <a href="' . BASE_URL . '/pages/shop.php">View Shop</a> | ';
$log[] = '<a href="' . BASE_URL . '/pages/products.php">Admin Products</a></p>';
$log[] = '<p><strong>⚠️ Delete seed_catalog.php after use on production.</strong></p>';

echo implode('', $log);
