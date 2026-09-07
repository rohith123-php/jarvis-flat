<?php
require_once '../includes/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

require_once 'includes/header.php';

$message = '';
$error = '';

// Helper function to resolve uploaded or chosen 4-slot images
function get_submitted_flat_photos($target = 'add') {
    $slots = ['exterior', 'interior', 'amenity', 'luxury'];
    $default_presets = [
        'exterior' => 'images/std_gated_community.jpg',
        'interior' => 'images/std_living_2bhk.jpg',
        'amenity'  => 'images/std_society_pool.jpg',
        'luxury'   => 'images/grand_villa_exterior.jpg'
    ];
    $result = [];

    foreach ($slots as $slot) {
        $img = $default_presets[$slot];

        // 1. Preset value
        $preset_key = $target . '_' . $slot . '_preset';
        if (!empty($_POST[$preset_key])) {
            $img = trim($_POST[$preset_key]);
        }

        // 2. Custom Web URL
        $url_key = $target . '_' . $slot . '_custom_url';
        if (!empty($_POST[$url_key])) {
            $img = trim($_POST[$url_key]);
        }

        // 3. File upload
        $file_key = $target . '_' . $slot . '_file';
        if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES[$file_key]['tmp_name'];
            $file_name = basename($_FILES[$file_key]['name']);
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
            if (in_array($file_ext, $allowed_exts)) {
                $new_name = 'flat_' . $slot . '_' . time() . '_' . rand(100, 999) . '.' . $file_ext;
                $upload_dir = '../images/';
                if (move_uploaded_file($file_tmp, $upload_dir . $new_name)) {
                    $img = 'images/' . $new_name;
                }
            }
        }

        $result[$slot] = $img;
    }

    return json_encode($result);
}

// Handle Add Flat
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $flat_no = trim($_POST['flat_no']);
    $block = trim($_POST['block']);
    $city = $_POST['city'];
    $bhk = intval($_POST['bhk']);
    $price = floatval($_POST['price']);
    $description = trim($_POST['description']);
    $status = $_POST['status'];
    
    $image_url = get_submitted_flat_photos('add');

    if (empty($flat_no) || empty($block) || empty($city) || empty($price)) {
        $error = 'Flat No, Block name, City, and Price are required fields.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO flats (flat_no, block, city, bhk, price, description, image_url, status) VALUES (:flat_no, :block, :city, :bhk, :price, :description, :image_url, :status)");
            $stmt->execute([
                'flat_no' => $flat_no,
                'block' => $block,
                'city' => $city,
                'bhk' => $bhk,
                'price' => $price,
                'description' => !empty($description) ? $description : null,
                'image_url' => $image_url,
                'status' => $status
            ]);
            $message = 'Apartment unit added to inventory successfully with 4 property photos!';
        } catch (PDOException $e) {
            $error = 'Error adding apartment: ' . $e->getMessage();
        }
    }
}

// Handle Edit Flat
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = intval($_POST['id']);
    $flat_no = trim($_POST['flat_no']);
    $block = trim($_POST['block']);
    $city = $_POST['city'];
    $bhk = intval($_POST['bhk']);
    $price = floatval($_POST['price']);
    $description = trim($_POST['description']);
    $status = $_POST['status'];
    
    $image_url = get_submitted_flat_photos('edit');

    if (empty($flat_no) || empty($block) || empty($city) || empty($price)) {
        $error = 'Flat No, Block, City, and Price are required fields.';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE flats SET flat_no = :flat_no, block = :block, city = :city, bhk = :bhk, price = :price, description = :description, status = :status, image_url = :image_url WHERE id = :id");
            $stmt->execute([
                'id' => $id,
                'flat_no' => $flat_no,
                'block' => $block,
                'city' => $city,
                'bhk' => $bhk,
                'price' => $price,
                'description' => !empty($description) ? $description : null,
                'status' => $status,
                'image_url' => $image_url
            ]);
            $message = 'Apartment unit updated successfully with 4 photos updated!';
        } catch (PDOException $e) {
            $error = 'Error updating apartment: ' . $e->getMessage();
        }
    }
}

// Handle Delete Flat
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    try {
        $stmt = $pdo->prepare("DELETE FROM flats WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $message = 'Apartment removed from inventory.';
    } catch (PDOException $e) {
        $error = 'Error deleting apartment: ' . $e->getMessage();
    }
}

// Fetch all flats
$stmt = $pdo->query("SELECT * FROM flats ORDER BY id DESC");
$flats = $stmt->fetchAll();

// Slot Preset Definition Arrays
$slot_presets = [
    'exterior' => [
        ['title' => 'Gated Community Flat', 'path' => 'images/std_gated_community.jpg'],
        ['title' => 'Modern Highrise Building', 'path' => 'images/std_apartment_building.jpg'],
        ['title' => 'Midrise Residential Block', 'path' => 'images/std_midrise_block.jpg'],
        ['title' => 'Skyline Balcony View', 'path' => 'images/std_balcony_view.jpg'],
        ['title' => 'Security Entrance Gate', 'path' => 'images/std_security_gate.jpg'],
        ['title' => 'Residential Township', 'path' => 'images/ventures_banner.png'],
    ],
    'interior' => [
        ['title' => '2 BHK Living Room', 'path' => 'images/std_living_2bhk.jpg'],
        ['title' => '3 BHK Spacious Lounge', 'path' => 'images/std_living_3bhk.jpg'],
        ['title' => '1 BHK Compact Hall', 'path' => 'images/std_compact_1bhk.jpg'],
        ['title' => 'Master Bedroom Suite', 'path' => 'images/std_master_bedroom.jpg'],
        ['title' => 'Guest Bedroom', 'path' => 'images/std_guest_bedroom.jpg'],
        ['title' => 'Standard Modular Kitchen', 'path' => 'images/std_modular_kitchen.jpg'],
        ['title' => 'Compact Modern Kitchen', 'path' => 'images/std_compact_kitchen.jpg'],
        ['title' => 'Family Dining Area', 'path' => 'images/std_dining_room.jpg'],
        ['title' => 'Standard Ceramic Bathroom', 'path' => 'images/std_modern_bathroom.jpg'],
    ],
    'amenity' => [
        ['title' => 'Children Play Park', 'path' => 'images/std_kids_play_area.jpg'],
        ['title' => 'Olympic Swimming Pool', 'path' => 'images/std_society_pool.jpg'],
        ['title' => 'Fitness Club & Gym', 'path' => 'images/std_society_gym.jpg'],
        ['title' => 'Community Hall', 'path' => 'images/std_community_hall.jpg'],
        ['title' => 'Floodlit Tennis Court', 'path' => 'images/championship_tennis_court.jpg'],
        ['title' => 'Botanical Garden', 'path' => 'images/private_botanical_garden.jpg'],
    ],
    'luxury' => [
        ['title' => 'Grand Villa Facade', 'path' => 'images/grand_villa_exterior.jpg'],
        ['title' => 'Waterfront Luxury Villa', 'path' => 'images/grand_waterfront_estate.jpg'],
        ['title' => 'Royal Palace Architecture', 'path' => 'images/royal_palace_villa.jpg'],
        ['title' => 'Futuristic Glass Skyscraper', 'path' => 'images/futuristic_glass_tower.jpg'],
        ['title' => 'Sky Bridge Residences', 'path' => 'images/sky_bridge_residences.jpg'],
        ['title' => 'Hillside Luxury Retreat', 'path' => 'images/hillside_luxury_retreat.jpg'],
        ['title' => 'Evening Mansion Facade', 'path' => 'images/evening_mansion_facade.jpg'],
        ['title' => 'Zen Courtyard Mansion', 'path' => 'images/zen_courtyard_villa.jpg'],
        ['title' => 'Private Island Villa', 'path' => 'images/private_island_villa.jpg'],
        ['title' => 'Double Height Grand Lounge', 'path' => 'images/double_height_grand_hall.jpg'],
        ['title' => 'Presidential Master Suite', 'path' => 'images/presidential_master_suite.jpg'],
        ['title' => 'Chef Gourmet Kitchen', 'path' => 'images/chef_gourmet_kitchen.jpg'],
        ['title' => 'Spa Italian Marble Bath', 'path' => 'images/spa_marble_bathroom.jpg'],
        ['title' => 'Private Acoustic Home Cinema', 'path' => 'images/private_home_cinema.jpg'],
        ['title' => 'Rooftop Sky Infinity Pool', 'path' => 'images/rooftop_infinity_skypool.jpg'],
    ]
];
?>

<div class="container-fluid px-4">
    <div class="row">
        <!-- Sidebar Navigation -->
        <div class="col-md-3 col-lg-2 d-print-none">
            <?php include 'includes/sidebar.php'; ?>
        </div>

        <!-- Main Content Area -->
        <div class="col-md-9 col-lg-10">
            <div class="row mb-4">
                <div class="col-md-8">
                    <h2 class="fw-bold" style="color:var(--blue-brand);">Property Catalog &amp; Inventory</h2>
                    <p class="text-muted">Manage real estate units with multi-angle photo gallery (Exterior, Interior, Amenities, Luxury).</p>
                </div>
                <div class="col-md-4 text-end">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFlatModal" style="border-radius:6px;">
                        <i class="fa-solid fa-plus me-1"></i> Add Property Unit
                    </button>
                </div>
            </div>

<?php if (!empty($message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-check-circle me-1"></i> <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-1"></i> <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Inventory Table Card -->
<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="border-radius:10px;">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 190px;">Property Photos (4)</th>
                                <th>City</th>
                                <th>Block / Building</th>
                                <th>Unit No</th>
                                <th>BHK</th>
                                <th>Price</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($flats) > 0): ?>
                                <?php foreach ($flats as $flat): 
                                    $photos = parse_flat_photos($flat['image_url'] ?? '');
                                    $ext_src = strpos($photos['exterior'], 'http') === 0 ? $photos['exterior'] : '../' . $photos['exterior'];
                                    $int_src = strpos($photos['interior'], 'http') === 0 ? $photos['interior'] : '../' . $photos['interior'];
                                    $amn_src = strpos($photos['amenity'], 'http') === 0 ? $photos['amenity'] : '../' . $photos['amenity'];
                                    $lux_src = strpos($photos['luxury'], 'http') === 0 ? $photos['luxury'] : '../' . $photos['luxury'];
                                ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-1">
                                                <img src="<?php echo htmlspecialchars($ext_src); ?>" title="1. Main Exterior" class="rounded border shadow-xs" style="width: 40px; height: 30px; object-fit: cover;">
                                                <img src="<?php echo htmlspecialchars($int_src); ?>" title="2. Standard Interior" class="rounded border shadow-xs" style="width: 40px; height: 30px; object-fit: cover;">
                                                <img src="<?php echo htmlspecialchars($amn_src); ?>" title="3. Society Amenity" class="rounded border shadow-xs" style="width: 40px; height: 30px; object-fit: cover;">
                                                <img src="<?php echo htmlspecialchars($lux_src); ?>" title="4. Grand Luxury" class="rounded border shadow-xs" style="width: 40px; height: 30px; object-fit: cover;">
                                            </div>
                                        </td>
                                        <td class="fw-bold text-dark"><?php echo htmlspecialchars($flat['city']); ?></td>
                                        <td><span class="fw-semibold text-dark"><?php echo htmlspecialchars($flat['block']); ?></span></td>
                                        <td class="fw-bold text-dark"><?php echo htmlspecialchars($flat['flat_no']); ?></td>
                                        <td><?php echo $flat['bhk']; ?> BHK</td>
                                        <td class="fw-bold text-dark">₹<?php echo number_format($flat['price'], 2); ?></td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-primary me-2 edit-btn" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editFlatModal"
                                                    data-id="<?php echo $flat['id']; ?>"
                                                    data-flat_no="<?php echo htmlspecialchars($flat['flat_no']); ?>"
                                                    data-block="<?php echo htmlspecialchars($flat['block']); ?>"
                                                    data-city="<?php echo htmlspecialchars($flat['city']); ?>"
                                                    data-bhk="<?php echo $flat['bhk']; ?>"
                                                    data-price="<?php echo $flat['price']; ?>"
                                                    data-description="<?php echo htmlspecialchars($flat['description'] ?? ''); ?>"
                                                    data-status="<?php echo $flat['status']; ?>"
                                                    data-exterior="<?php echo htmlspecialchars($photos['exterior']); ?>"
                                                    data-interior="<?php echo htmlspecialchars($photos['interior']); ?>"
                                                    data-amenity="<?php echo htmlspecialchars($photos['amenity']); ?>"
                                                    data-luxury="<?php echo htmlspecialchars($photos['luxury']); ?>"
                                                    style="border-radius:4px;">
                                                <i class="fa-solid fa-pen-to-square"></i> Edit
                                            </button>
                                            <a href="menu.php?delete=<?php echo $flat['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger" 
                                               onclick="return confirm('Are you sure you want to delete this property from inventory?');"
                                               style="border-radius:4px;">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No apartments registered in inventory catalog.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
        </div> <!-- End col-md-9 col-lg-10 -->
    </div> <!-- End row -->
</div> <!-- End container-fluid -->

<!-- ======================================================= -->
<!-- ADD FLAT MODAL (4-Slot Dedicated Image Selector)        -->
<!-- ======================================================= -->
<div class="modal fade" id="addFlatModal" tabindex="-1" aria-labelledby="addFlatModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content shadow-lg border-0 rounded-4 overflow-hidden">
            <div class="modal-header border-0 bg-dark text-white p-4">
                <h5 class="modal-title fw-bold" id="addFlatModalLabel" style="color:#fff;"><i class="fa-solid fa-building me-2 text-warning"></i>Add Property Unit (4 Photos)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="menu.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                
                <!-- Hidden inputs for active preset selection in each slot -->
                <input type="hidden" name="add_exterior_preset" id="add_exterior_preset" value="images/std_gated_community.jpg">
                <input type="hidden" name="add_interior_preset" id="add_interior_preset" value="images/std_living_2bhk.jpg">
                <input type="hidden" name="add_amenity_preset" id="add_amenity_preset" value="images/std_society_pool.jpg">
                <input type="hidden" name="add_luxury_preset" id="add_luxury_preset" value="images/grand_villa_exterior.jpg">
                
                <div class="modal-body p-4" style="max-height: 80vh; overflow-y: auto;">
                    <!-- Basic Property Fields -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label for="flat_no" class="form-label fw-semibold">Unit Number</label>
                            <input type="text" class="form-control form-control-custom" id="flat_no" name="flat_no" required placeholder="e.g. 104">
                        </div>
                        <div class="col-md-4">
                            <label for="block" class="form-label fw-semibold">Block/Tower Name</label>
                            <input type="text" class="form-control form-control-custom" id="block" name="block" required placeholder="e.g. Tower B (Zenith)">
                        </div>
                        <div class="col-md-4">
                            <label for="city" class="form-label fw-semibold">Location City</label>
                            <select class="form-select form-control-custom" id="city" name="city">
                                <option value="Chennai">Chennai</option>
                                <option value="Bengaluru">Bengaluru</option>
                                <option value="Coimbatore">Coimbatore</option>
                                <option value="Hyderabad">Hyderabad</option>
                                <option value="Dubai">Dubai</option>
                                <option value="Pune">Pune</option>
                                <option value="Delhi">Delhi</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="bhk" class="form-label fw-semibold">BHK Size</label>
                            <select class="form-select form-control-custom" id="bhk" name="bhk">
                                <option value="1">1 BHK</option>
                                <option value="2" selected>2 BHK</option>
                                <option value="3">3 BHK</option>
                                <option value="4">4 BHK</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="price" class="form-label fw-semibold">Price (₹)</label>
                            <input type="number" step="0.01" class="form-control form-control-custom" id="price" name="price" required placeholder="e.g. 145000.00">
                        </div>
                        <div class="col-md-4">
                            <label for="status" class="form-label fw-semibold">Status</label>
                            <select class="form-select form-control-custom" id="status" name="status">
                                <option value="Available">Available</option>
                                <option value="Booked">Booked</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="description" class="form-label fw-semibold">Description</label>
                            <textarea class="form-control form-control-custom" id="description" name="description" rows="2" placeholder="Describe key highlights, floor views, furnishing details..."></textarea>
                        </div>
                    </div>

                    <!-- 4 Dedicated Image Slots Section -->
                    <div class="p-3 border rounded-3 bg-light">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-images text-warning me-1"></i> 4 Property Photo Slots</h6>
                                <p class="text-muted small mb-0">Select presets or upload custom photos for each of the 4 dedicated property viewpoints.</p>
                            </div>
                            <span class="badge bg-warning text-dark fw-semibold">4 Views per Flat</span>
                        </div>

                        <!-- 4 Slot Navigation Pills -->
                        <ul class="nav nav-pills mb-3 gap-2" id="addSlotTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active px-3 py-1.5 fw-semibold" id="add-slot1-tab" data-bs-toggle="pill" data-bs-target="#add-slot1-pane" type="button" role="tab" style="border-radius: 20px; font-size: 0.85rem;">
                                    1. Main Exterior
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link px-3 py-1.5 fw-semibold" id="add-slot2-tab" data-bs-toggle="pill" data-bs-target="#add-slot2-pane" type="button" role="tab" style="border-radius: 20px; font-size: 0.85rem;">
                                    2. Standard Interior
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link px-3 py-1.5 fw-semibold" id="add-slot3-tab" data-bs-toggle="pill" data-bs-target="#add-slot3-pane" type="button" role="tab" style="border-radius: 20px; font-size: 0.85rem;">
                                    3. Society Amenities
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link px-3 py-1.5 fw-semibold" id="add-slot4-tab" data-bs-toggle="pill" data-bs-target="#add-slot4-pane" type="button" role="tab" style="border-radius: 20px; font-size: 0.85rem;">
                                    4. Grand Luxury
                                </button>
                            </li>
                        </ul>

                        <!-- Slot Tab Panes -->
                        <div class="tab-content" id="addSlotTabsContent">
                            
                            <!-- Slot 1: Exterior -->
                            <div class="tab-pane fade show active" id="add-slot1-pane" role="tabpanel">
                                <div class="mb-2 text-muted small fw-semibold">Click a preset below or enter a custom photo for Slot 1 (Exterior):</div>
                                <div class="row g-2 mb-3" style="max-height: 200px; overflow-y: auto;">
                                    <?php foreach ($slot_presets['exterior'] as $p): ?>
                                        <div class="col-6 col-sm-4 col-md-2">
                                            <div class="preset-slot-card p-1 border rounded bg-white text-center cursor-pointer shadow-xs" 
                                                 data-slot="exterior" data-target="add" data-img="<?php echo $p['path']; ?>"
                                                 style="cursor:pointer; <?php echo $p['path'] === 'images/std_gated_community.jpg' ? 'border: 2px solid var(--orange-brand) !important;' : ''; ?>">
                                                <img src="../<?php echo $p['path']; ?>" class="rounded w-100 mb-1" style="height: 50px; object-fit: cover;">
                                                <span class="d-block text-truncate fw-bold" style="font-size: 0.68rem;"><?php echo $p['title']; ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="row g-2 pt-2 border-top">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold mb-1">Custom Upload (Slot 1):</label>
                                        <input type="file" class="form-control form-control-sm" name="add_exterior_file" accept="image/*" onchange="previewSlotUpload(this, 'exterior', 'add')">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold mb-1">Web Image URL (Slot 1):</label>
                                        <input type="url" class="form-control form-control-sm" name="add_exterior_custom_url" placeholder="https://..." oninput="previewSlotUrl(this.value, 'exterior', 'add')">
                                    </div>
                                </div>
                            </div>

                            <!-- Slot 2: Interior -->
                            <div class="tab-pane fade" id="add-slot2-pane" role="tabpanel">
                                <div class="mb-2 text-muted small fw-semibold">Click a preset below or enter a custom photo for Slot 2 (Standard Interior):</div>
                                <div class="row g-2 mb-3" style="max-height: 200px; overflow-y: auto;">
                                    <?php foreach ($slot_presets['interior'] as $p): ?>
                                        <div class="col-6 col-sm-4 col-md-2">
                                            <div class="preset-slot-card p-1 border rounded bg-white text-center cursor-pointer shadow-xs" 
                                                 data-slot="interior" data-target="add" data-img="<?php echo $p['path']; ?>"
                                                 style="cursor:pointer; <?php echo $p['path'] === 'images/std_living_2bhk.jpg' ? 'border: 2px solid var(--orange-brand) !important;' : ''; ?>">
                                                <img src="../<?php echo $p['path']; ?>" class="rounded w-100 mb-1" style="height: 50px; object-fit: cover;">
                                                <span class="d-block text-truncate fw-bold" style="font-size: 0.68rem;"><?php echo $p['title']; ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="row g-2 pt-2 border-top">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold mb-1">Custom Upload (Slot 2):</label>
                                        <input type="file" class="form-control form-control-sm" name="add_interior_file" accept="image/*" onchange="previewSlotUpload(this, 'interior', 'add')">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold mb-1">Web Image URL (Slot 2):</label>
                                        <input type="url" class="form-control form-control-sm" name="add_interior_custom_url" placeholder="https://..." oninput="previewSlotUrl(this.value, 'interior', 'add')">
                                    </div>
                                </div>
                            </div>

                            <!-- Slot 3: Amenity -->
                            <div class="tab-pane fade" id="add-slot3-pane" role="tabpanel">
                                <div class="mb-2 text-muted small fw-semibold">Click a preset below or enter a custom photo for Slot 3 (Society Amenities):</div>
                                <div class="row g-2 mb-3" style="max-height: 200px; overflow-y: auto;">
                                    <?php foreach ($slot_presets['amenity'] as $p): ?>
                                        <div class="col-6 col-sm-4 col-md-2">
                                            <div class="preset-slot-card p-1 border rounded bg-white text-center cursor-pointer shadow-xs" 
                                                 data-slot="amenity" data-target="add" data-img="<?php echo $p['path']; ?>"
                                                 style="cursor:pointer; <?php echo $p['path'] === 'images/std_society_pool.jpg' ? 'border: 2px solid var(--orange-brand) !important;' : ''; ?>">
                                                <img src="../<?php echo $p['path']; ?>" class="rounded w-100 mb-1" style="height: 50px; object-fit: cover;">
                                                <span class="d-block text-truncate fw-bold" style="font-size: 0.68rem;"><?php echo $p['title']; ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="row g-2 pt-2 border-top">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold mb-1">Custom Upload (Slot 3):</label>
                                        <input type="file" class="form-control form-control-sm" name="add_amenity_file" accept="image/*" onchange="previewSlotUpload(this, 'amenity', 'add')">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold mb-1">Web Image URL (Slot 3):</label>
                                        <input type="url" class="form-control form-control-sm" name="add_amenity_custom_url" placeholder="https://..." oninput="previewSlotUrl(this.value, 'amenity', 'add')">
                                    </div>
                                </div>
                            </div>

                            <!-- Slot 4: Grand Luxury -->
                            <div class="tab-pane fade" id="add-slot4-pane" role="tabpanel">
                                <div class="mb-2 text-muted small fw-semibold">Click a preset below or enter a custom photo for Slot 4 (Grand Luxury):</div>
                                <div class="row g-2 mb-3" style="max-height: 200px; overflow-y: auto;">
                                    <?php foreach ($slot_presets['luxury'] as $p): ?>
                                        <div class="col-6 col-sm-4 col-md-2">
                                            <div class="preset-slot-card p-1 border rounded bg-white text-center cursor-pointer shadow-xs" 
                                                 data-slot="luxury" data-target="add" data-img="<?php echo $p['path']; ?>"
                                                 style="cursor:pointer; <?php echo $p['path'] === 'images/grand_villa_exterior.jpg' ? 'border: 2px solid var(--orange-brand) !important;' : ''; ?>">
                                                <img src="../<?php echo $p['path']; ?>" class="rounded w-100 mb-1" style="height: 50px; object-fit: cover;">
                                                <span class="d-block text-truncate fw-bold" style="font-size: 0.68rem;"><?php echo $p['title']; ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="row g-2 pt-2 border-top">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold mb-1">Custom Upload (Slot 4):</label>
                                        <input type="file" class="form-control form-control-sm" name="add_luxury_file" accept="image/*" onchange="previewSlotUpload(this, 'luxury', 'add')">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold mb-1">Web Image URL (Slot 4):</label>
                                        <input type="url" class="form-control form-control-sm" name="add_luxury_custom_url" placeholder="https://..." oninput="previewSlotUrl(this.value, 'luxury', 'add')">
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- 4-Slot Live Summary Deck -->
                        <div class="mt-3 pt-3 border-top bg-white p-3 rounded-3 shadow-xs">
                            <span class="fw-bold text-dark d-block mb-2 small"><i class="fa-solid fa-eye text-warning me-1"></i> Active 4-Photo Deck Summary:</span>
                            <div class="row g-2 text-center">
                                <div class="col-6 col-md-3">
                                    <div class="p-1 border rounded bg-light">
                                        <span class="badge bg-secondary mb-1" style="font-size:0.65rem;">1. Main Exterior</span>
                                        <img src="../images/std_gated_community.jpg" id="add_preview_exterior" class="rounded w-100" style="height: 65px; object-fit: cover;">
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="p-1 border rounded bg-light">
                                        <span class="badge bg-secondary mb-1" style="font-size:0.65rem;">2. Standard Interior</span>
                                        <img src="../images/std_living_2bhk.jpg" id="add_preview_interior" class="rounded w-100" style="height: 65px; object-fit: cover;">
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="p-1 border rounded bg-light">
                                        <span class="badge bg-secondary mb-1" style="font-size:0.65rem;">3. Society Amenities</span>
                                        <img src="../images/std_society_pool.jpg" id="add_preview_amenity" class="rounded w-100" style="height: 65px; object-fit: cover;">
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="p-1 border rounded bg-light">
                                        <span class="badge bg-secondary mb-1" style="font-size:0.65rem;">4. Grand Luxury</span>
                                        <img src="../images/grand_villa_exterior.jpg" id="add_preview_luxury" class="rounded w-100" style="height: 65px; object-fit: cover;">
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="modal-footer border-0 p-3 bg-light">
                    <button type="button" class="btn btn-secondary rounded-3 px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4 fw-bold">Save Flat to Inventory</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ======================================================= -->
<!-- EDIT FLAT MODAL (4-Slot Dedicated Image Selector)       -->
<!-- ======================================================= -->
<div class="modal fade" id="editFlatModal" tabindex="-1" aria-labelledby="editFlatModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content shadow-lg border-0 rounded-4 overflow-hidden">
            <div class="modal-header border-0 bg-dark text-white p-4">
                <h5 class="modal-title fw-bold" id="editFlatModalLabel" style="color:#fff;"><i class="fa-solid fa-pen-to-square me-2 text-warning"></i>Edit Property Unit (4 Photos)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="menu.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                
                <!-- Hidden inputs for active preset selection in each slot -->
                <input type="hidden" name="edit_exterior_preset" id="edit_exterior_preset" value="images/std_gated_community.jpg">
                <input type="hidden" name="edit_interior_preset" id="edit_interior_preset" value="images/std_living_2bhk.jpg">
                <input type="hidden" name="edit_amenity_preset" id="edit_amenity_preset" value="images/std_society_pool.jpg">
                <input type="hidden" name="edit_luxury_preset" id="edit_luxury_preset" value="images/grand_villa_exterior.jpg">
                
                <div class="modal-body p-4" style="max-height: 80vh; overflow-y: auto;">
                    <!-- Basic Property Fields -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label for="edit_flat_no" class="form-label fw-semibold">Unit Number</label>
                            <input type="text" class="form-control form-control-custom" id="edit_flat_no" name="flat_no" required>
                        </div>
                        <div class="col-md-4">
                            <label for="edit_block" class="form-label fw-semibold">Block/Tower Name</label>
                            <input type="text" class="form-control form-control-custom" id="edit_block" name="block" required>
                        </div>
                        <div class="col-md-4">
                            <label for="edit_city" class="form-label fw-semibold">Location City</label>
                            <select class="form-select form-control-custom" id="edit_city" name="city">
                                <option value="Chennai">Chennai</option>
                                <option value="Bengaluru">Bengaluru</option>
                                <option value="Coimbatore">Coimbatore</option>
                                <option value="Hyderabad">Hyderabad</option>
                                <option value="Dubai">Dubai</option>
                                <option value="Pune">Pune</option>
                                <option value="Delhi">Delhi</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="edit_bhk" class="form-label fw-semibold">BHK Size</label>
                            <select class="form-select form-control-custom" id="edit_bhk" name="bhk">
                                <option value="1">1 BHK</option>
                                <option value="2">2 BHK</option>
                                <option value="3">3 BHK</option>
                                <option value="4">4 BHK</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="edit_price" class="form-label fw-semibold">Price (₹)</label>
                            <input type="number" step="0.01" class="form-control form-control-custom" id="edit_price" name="price" required>
                        </div>
                        <div class="col-md-4">
                            <label for="edit_status" class="form-label fw-semibold">Status</label>
                            <select class="form-select form-control-custom" id="edit_status" name="status">
                                <option value="Available">Available</option>
                                <option value="Booked">Booked</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="edit_description" class="form-label fw-semibold">Description</label>
                            <textarea class="form-control form-control-custom" id="edit_description" name="description" rows="2"></textarea>
                        </div>
                    </div>

                    <!-- 4 Dedicated Image Slots Section for Edit Modal -->
                    <div class="p-3 border rounded-3 bg-light">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-images text-warning me-1"></i> 4 Property Photo Slots</h6>
                                <p class="text-muted small mb-0">Update presets or upload custom photos for each of the 4 dedicated property viewpoints.</p>
                            </div>
                            <span class="badge bg-warning text-dark fw-semibold">4 Views per Flat</span>
                        </div>

                        <!-- 4 Slot Navigation Pills -->
                        <ul class="nav nav-pills mb-3 gap-2" id="editSlotTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active px-3 py-1.5 fw-semibold" id="edit-slot1-tab" data-bs-toggle="pill" data-bs-target="#edit-slot1-pane" type="button" role="tab" style="border-radius: 20px; font-size: 0.85rem;">
                                    1. Main Exterior
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link px-3 py-1.5 fw-semibold" id="edit-slot2-tab" data-bs-toggle="pill" data-bs-target="#edit-slot2-pane" type="button" role="tab" style="border-radius: 20px; font-size: 0.85rem;">
                                    2. Standard Interior
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link px-3 py-1.5 fw-semibold" id="edit-slot3-tab" data-bs-toggle="pill" data-bs-target="#edit-slot3-pane" type="button" role="tab" style="border-radius: 20px; font-size: 0.85rem;">
                                    3. Society Amenities
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link px-3 py-1.5 fw-semibold" id="edit-slot4-tab" data-bs-toggle="pill" data-bs-target="#edit-slot4-pane" type="button" role="tab" style="border-radius: 20px; font-size: 0.85rem;">
                                    4. Grand Luxury
                                </button>
                            </li>
                        </ul>

                        <!-- Slot Tab Panes -->
                        <div class="tab-content" id="editSlotTabsContent">
                            
                            <!-- Slot 1: Exterior -->
                            <div class="tab-pane fade show active" id="edit-slot1-pane" role="tabpanel">
                                <div class="mb-2 text-muted small fw-semibold">Click a preset below or enter a custom photo for Slot 1 (Exterior):</div>
                                <div class="row g-2 mb-3" style="max-height: 200px; overflow-y: auto;">
                                    <?php foreach ($slot_presets['exterior'] as $p): ?>
                                        <div class="col-6 col-sm-4 col-md-2">
                                            <div class="preset-slot-card p-1 border rounded bg-white text-center cursor-pointer shadow-xs" 
                                                 data-slot="exterior" data-target="edit" data-img="<?php echo $p['path']; ?>"
                                                 style="cursor:pointer;">
                                                <img src="../<?php echo $p['path']; ?>" class="rounded w-100 mb-1" style="height: 50px; object-fit: cover;">
                                                <span class="d-block text-truncate fw-bold" style="font-size: 0.68rem;"><?php echo $p['title']; ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="row g-2 pt-2 border-top">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold mb-1">Custom Upload (Slot 1):</label>
                                        <input type="file" class="form-control form-control-sm" name="edit_exterior_file" accept="image/*" onchange="previewSlotUpload(this, 'exterior', 'edit')">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold mb-1">Web Image URL (Slot 1):</label>
                                        <input type="url" class="form-control form-control-sm" name="edit_exterior_custom_url" placeholder="https://..." oninput="previewSlotUrl(this.value, 'exterior', 'edit')">
                                    </div>
                                </div>
                            </div>

                            <!-- Slot 2: Interior -->
                            <div class="tab-pane fade" id="edit-slot2-pane" role="tabpanel">
                                <div class="mb-2 text-muted small fw-semibold">Click a preset below or enter a custom photo for Slot 2 (Standard Interior):</div>
                                <div class="row g-2 mb-3" style="max-height: 200px; overflow-y: auto;">
                                    <?php foreach ($slot_presets['interior'] as $p): ?>
                                        <div class="col-6 col-sm-4 col-md-2">
                                            <div class="preset-slot-card p-1 border rounded bg-white text-center cursor-pointer shadow-xs" 
                                                 data-slot="interior" data-target="edit" data-img="<?php echo $p['path']; ?>"
                                                 style="cursor:pointer;">
                                                <img src="../<?php echo $p['path']; ?>" class="rounded w-100 mb-1" style="height: 50px; object-fit: cover;">
                                                <span class="d-block text-truncate fw-bold" style="font-size: 0.68rem;"><?php echo $p['title']; ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="row g-2 pt-2 border-top">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold mb-1">Custom Upload (Slot 2):</label>
                                        <input type="file" class="form-control form-control-sm" name="edit_interior_file" accept="image/*" onchange="previewSlotUpload(this, 'interior', 'edit')">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold mb-1">Web Image URL (Slot 2):</label>
                                        <input type="url" class="form-control form-control-sm" name="edit_interior_custom_url" placeholder="https://..." oninput="previewSlotUrl(this.value, 'interior', 'edit')">
                                    </div>
                                </div>
                            </div>

                            <!-- Slot 3: Amenity -->
                            <div class="tab-pane fade" id="edit-slot3-pane" role="tabpanel">
                                <div class="mb-2 text-muted small fw-semibold">Click a preset below or enter a custom photo for Slot 3 (Society Amenities):</div>
                                <div class="row g-2 mb-3" style="max-height: 200px; overflow-y: auto;">
                                    <?php foreach ($slot_presets['amenity'] as $p): ?>
                                        <div class="col-6 col-sm-4 col-md-2">
                                            <div class="preset-slot-card p-1 border rounded bg-white text-center cursor-pointer shadow-xs" 
                                                 data-slot="amenity" data-target="edit" data-img="<?php echo $p['path']; ?>"
                                                 style="cursor:pointer;">
                                                <img src="../<?php echo $p['path']; ?>" class="rounded w-100 mb-1" style="height: 50px; object-fit: cover;">
                                                <span class="d-block text-truncate fw-bold" style="font-size: 0.68rem;"><?php echo $p['title']; ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="row g-2 pt-2 border-top">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold mb-1">Custom Upload (Slot 3):</label>
                                        <input type="file" class="form-control form-control-sm" name="edit_amenity_file" accept="image/*" onchange="previewSlotUpload(this, 'amenity', 'edit')">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold mb-1">Web Image URL (Slot 3):</label>
                                        <input type="url" class="form-control form-control-sm" name="edit_amenity_custom_url" placeholder="https://..." oninput="previewSlotUrl(this.value, 'amenity', 'edit')">
                                    </div>
                                </div>
                            </div>

                            <!-- Slot 4: Grand Luxury -->
                            <div class="tab-pane fade" id="edit-slot4-pane" role="tabpanel">
                                <div class="mb-2 text-muted small fw-semibold">Click a preset below or enter a custom photo for Slot 4 (Grand Luxury):</div>
                                <div class="row g-2 mb-3" style="max-height: 200px; overflow-y: auto;">
                                    <?php foreach ($slot_presets['luxury'] as $p): ?>
                                        <div class="col-6 col-sm-4 col-md-2">
                                            <div class="preset-slot-card p-1 border rounded bg-white text-center cursor-pointer shadow-xs" 
                                                 data-slot="luxury" data-target="edit" data-img="<?php echo $p['path']; ?>"
                                                 style="cursor:pointer;">
                                                <img src="../<?php echo $p['path']; ?>" class="rounded w-100 mb-1" style="height: 50px; object-fit: cover;">
                                                <span class="d-block text-truncate fw-bold" style="font-size: 0.68rem;"><?php echo $p['title']; ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="row g-2 pt-2 border-top">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold mb-1">Custom Upload (Slot 4):</label>
                                        <input type="file" class="form-control form-control-sm" name="edit_luxury_file" accept="image/*" onchange="previewSlotUpload(this, 'luxury', 'edit')">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold mb-1">Web Image URL (Slot 4):</label>
                                        <input type="url" class="form-control form-control-sm" name="edit_luxury_custom_url" placeholder="https://..." oninput="previewSlotUrl(this.value, 'luxury', 'edit')">
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- 4-Slot Live Summary Deck for Edit Modal -->
                        <div class="mt-3 pt-3 border-top bg-white p-3 rounded-3 shadow-xs">
                            <span class="fw-bold text-dark d-block mb-2 small"><i class="fa-solid fa-eye text-warning me-1"></i> Active 4-Photo Deck Summary:</span>
                            <div class="row g-2 text-center">
                                <div class="col-6 col-md-3">
                                    <div class="p-1 border rounded bg-light">
                                        <span class="badge bg-secondary mb-1" style="font-size:0.65rem;">1. Main Exterior</span>
                                        <img src="../images/std_gated_community.jpg" id="edit_preview_exterior" class="rounded w-100" style="height: 65px; object-fit: cover;">
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="p-1 border rounded bg-light">
                                        <span class="badge bg-secondary mb-1" style="font-size:0.65rem;">2. Standard Interior</span>
                                        <img src="../images/std_living_2bhk.jpg" id="edit_preview_interior" class="rounded w-100" style="height: 65px; object-fit: cover;">
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="p-1 border rounded bg-light">
                                        <span class="badge bg-secondary mb-1" style="font-size:0.65rem;">3. Society Amenities</span>
                                        <img src="../images/std_society_pool.jpg" id="edit_preview_amenity" class="rounded w-100" style="height: 65px; object-fit: cover;">
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="p-1 border rounded bg-light">
                                        <span class="badge bg-secondary mb-1" style="font-size:0.65rem;">4. Grand Luxury</span>
                                        <img src="../images/grand_villa_exterior.jpg" id="edit_preview_luxury" class="rounded w-100" style="height: 65px; object-fit: cover;">
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="modal-footer border-0 p-3 bg-light">
                    <button type="button" class="btn btn-secondary rounded-3 px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4 fw-bold">Update Flat in Inventory</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Select Preset Card Handler for a specific slot
function selectSlotPreset(card, slot, target) {
    const modal = document.getElementById(target === 'add' ? 'addFlatModal' : 'editFlatModal');
    const pane = modal.querySelector('#' + target + '-slot' + (slot === 'exterior' ? '1' : (slot === 'interior' ? '2' : (slot === 'amenity' ? '3' : '4'))) + '-pane');
    
    if (pane) {
        pane.querySelectorAll('.preset-slot-card').forEach(c => {
            c.style.borderColor = '#e2e8f0';
            c.style.borderWidth = '1px';
        });
    }
    
    card.style.borderColor = 'var(--orange-brand, #f59e0b)';
    card.style.borderWidth = '2px';
    
    const imgSrc = card.getAttribute('data-img');
    const hiddenField = document.getElementById(target + '_' + slot + '_preset');
    if (hiddenField) hiddenField.value = imgSrc;
    
    const previewImg = document.getElementById(target + '_preview_' + slot);
    if (previewImg) {
        previewImg.src = imgSrc.startsWith('http') ? imgSrc : '../' + imgSrc;
    }
}

// Local File Upload Live Preview for a slot
function previewSlotUpload(input, slot, target) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const previewImg = document.getElementById(target + '_preview_' + slot);
            if (previewImg) previewImg.src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Custom Web URL Live Preview for a slot
function previewSlotUrl(url, slot, target) {
    if (url && url.trim().length > 5) {
        const previewImg = document.getElementById(target + '_preview_' + slot);
        if (previewImg) previewImg.src = url.trim();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Attach click events to all preset slot cards
    document.querySelectorAll('.preset-slot-card').forEach(card => {
        card.addEventListener('click', function() {
            const slot = this.getAttribute('data-slot');
            const target = this.getAttribute('data-target');
            selectSlotPreset(this, slot, target);
        });
    });

    // Edit button click event
    const editBtns = document.querySelectorAll('.edit-btn');
    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_id').value = this.dataset.id;
            document.getElementById('edit_flat_no').value = this.dataset.flat_no;
            document.getElementById('edit_block').value = this.dataset.block;
            document.getElementById('edit_city').value = this.dataset.city;
            document.getElementById('edit_bhk').value = this.dataset.bhk;
            document.getElementById('edit_price').value = this.dataset.price;
            document.getElementById('edit_description').value = this.dataset.description;
            document.getElementById('edit_status').value = this.dataset.status;

            const slots = ['exterior', 'interior', 'amenity', 'luxury'];
            slots.forEach(slot => {
                const imgPath = this.dataset[slot] || '';
                const hiddenInput = document.getElementById('edit_' + slot + '_preset');
                if (hiddenInput && imgPath) hiddenInput.value = imgPath;

                const previewImg = document.getElementById('edit_preview_' + slot);
                if (previewImg && imgPath) {
                    previewImg.src = imgPath.startsWith('http') ? imgPath : '../' + imgPath;
                }

                // Highlight matching card in edit pane if exists
                const paneIndex = slot === 'exterior' ? '1' : (slot === 'interior' ? '2' : (slot === 'amenity' ? '3' : '4'));
                const pane = document.querySelector('#edit-slot' + paneIndex + '-pane');
                if (pane) {
                    pane.querySelectorAll('.preset-slot-card').forEach(card => {
                        if (card.getAttribute('data-img') === imgPath) {
                            card.style.borderColor = 'var(--orange-brand, #f59e0b)';
                            card.style.borderWidth = '2px';
                        } else {
                            card.style.borderColor = '#e2e8f0';
                            card.style.borderWidth = '1px';
                        }
                    });
                }
            });
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
