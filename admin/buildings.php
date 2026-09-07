<?php
require_once '../includes/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

$message = '';
$error = '';

// Add New Building Block
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_building') {
    $name = trim($_POST['name']);
    $city = $_POST['city'];
    $floors = intval($_POST['total_floors']);
    $units = intval($_POST['total_units']);
    $status = $_POST['status'];

    if (!empty($name)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO buildings (name, city, total_floors, total_units, status) VALUES (:name, :city, :floors, :units, :status)");
            $stmt->execute([
                'name' => $name,
                'city' => $city,
                'floors' => $floors,
                'units' => $units,
                'status' => $status
            ]);
            $message = "Building Block '{$name}' added successfully!";
        } catch (PDOException $e) {
            $error = "Error adding building: " . $e->getMessage();
        }
    }
}

// Fetch all buildings
$buildings = $pdo->query("SELECT * FROM buildings ORDER BY id DESC")->fetchAll();

// Group flats by block and floor for interactive matrix display
$flats_by_block = [];
$raw_flats = $pdo->query("SELECT * FROM flats ORDER BY floor ASC, flat_no ASC")->fetchAll();
foreach ($raw_flats as $f) {
    $flats_by_block[$f['block']][$f['floor']][] = $f;
}

require_once 'includes/header.php';
?>

<div class="container-fluid px-4">
    <div class="row">
        <!-- Sidebar Navigation -->
        <div class="col-md-3 col-lg-2">
            <?php include 'includes/sidebar.php'; ?>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold" style="color:var(--blue-brand);">Building & Floor Management</h2>
                    <p class="text-muted mb-0">Manage multi-city towers, blocks, floors, and real-time unit occupancy matrices.</p>
                </div>
                <button class="btn btn-warning text-dark fw-bold px-3 py-2" data-bs-toggle="modal" data-bs-target="#addBuildingModal" style="background-color: var(--orange-brand); border: none;">
                    <i class="fa-solid fa-plus-circle me-1.5"></i> Add Building Block
                </button>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-circle-check me-2"></i><?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i><?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Buildings Overview Cards -->
            <div class="row g-3 mb-5">
                <?php foreach ($buildings as $b): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border border-light-subtle shadow-sm p-4 rounded-3" style="background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <span class="badge bg-light text-dark border me-1"><i class="fa-solid fa-location-dot me-1 text-danger"></i> <?php echo htmlspecialchars($b['city']); ?></span>
                                    <span class="badge bg-success text-white px-2.5 py-1 rounded-pill fw-semibold" style="background-color: #15803d !important; color: #ffffff !important;"><?php echo htmlspecialchars($b['status']); ?></span>
                                </div>
                                <div class="p-2 rounded-circle bg-primary bg-opacity-10 text-primary">
                                    <i class="fa-solid fa-building fa-lg"></i>
                                </div>
                            </div>
                            <h5 class="fw-bold text-dark mb-2" style="font-family:'Playfair Display', serif;"><?php echo htmlspecialchars($b['name']); ?></h5>
                            <div class="row g-2 mt-2 pt-2 border-top text-center text-muted small">
                                <div class="col-6 border-end">
                                    <div class="fw-bold text-dark fs-6"><?php echo $b['total_floors']; ?> Floors</div>
                                    <span class="text-uppercase" style="font-size:0.65rem;">Vertical Levels</span>
                                </div>
                                <div class="col-6">
                                    <div class="fw-bold text-dark fs-6"><?php echo $b['total_units']; ?> Units</div>
                                    <span class="text-uppercase" style="font-size:0.65rem;">Capacity</span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Real-Time Floor Matrix & Unit Layout -->
            <h4 class="fw-bold mb-3" style="color:var(--blue-brand);"><i class="fa-solid fa-cubes-stacked me-2 text-warning"></i>Real-Time Floor Plan Matrix</h4>
            <p class="text-muted small mb-4">Click any floor unit to view details or manage unit status.</p>

            <?php foreach ($flats_by_block as $block_name => $floors): ?>
                <div class="card border border-light-subtle rounded-3 shadow-sm mb-4">
                    <div class="card-header bg-dark text-white p-3 d-flex justify-content-between align-items-center">
                        <div class="fw-bold fs-6"><i class="fa-solid fa-building-user me-2 text-warning"></i><?php echo htmlspecialchars($block_name); ?></div>
                        <span class="badge bg-warning text-dark fw-bold"><?php echo count($floors); ?> Active Floors</span>
                    </div>
                    <div class="card-body p-4">
                        <?php foreach ($floors as $floor_num => $units_list): ?>
                            <div class="row align-items-center mb-3 pb-3 border-bottom border-light-subtle">
                                <div class="col-md-2">
                                    <span class="badge bg-light text-dark border px-3 py-2 fw-bold w-100">
                                        <i class="fa-solid fa-layer-group me-1 text-primary"></i> Floor <?php echo $floor_num; ?>
                                    </span>
                                </div>
                                <div class="col-md-10">
                                    <div class="d-flex flex-wrap gap-2">
                                        <?php foreach ($units_list as $u): ?>
                                            <a href="../resident/view_flat.php?flat_id=<?php echo $u['id']; ?>" target="_blank" class="text-decoration-none">
                                                <div class="px-3 py-2 rounded border text-center shadow-xs" style="min-width: 100px; <?php echo ($u['status'] === 'Booked') ? 'background: #eff6ff; border-color: #3b82f6 !important;' : 'background: #f0fdf4; border-color: #22c55e !important;'; ?>">
                                                    <div class="fw-bold small" style="color: #0f172a;">Flat ✦ <?php echo htmlspecialchars($u['flat_no']); ?></div>
                                                    <div style="font-size: 0.68rem;" class="<?php echo ($u['status'] === 'Booked') ? 'text-primary fw-bold' : 'text-success fw-bold'; ?>">
                                                        <?php echo ($u['status'] === 'Booked') ? 'Reserved' : 'Available'; ?>
                                                    </div>
                                                </div>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

        </div>
    </div>
</div>

<!-- Add Building Modal -->
<div class="modal fade" id="addBuildingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-city me-2 text-warning"></i>Add Building Block</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_building">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Building Block Name</label>
                        <input type="text" name="name" class="form-control" placeholder="Tower D (Casagrand Pinnacle)" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">City Precinct</label>
                        <select name="city" class="form-select">
                            <option value="Chennai">Chennai</option>
                            <option value="Bengaluru">Bengaluru</option>
                            <option value="Coimbatore">Coimbatore</option>
                            <option value="Hyderabad">Hyderabad</option>
                            <option value="Dubai">Dubai</option>
                            <option value="Pune">Pune</option>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Total Floors</label>
                            <input type="number" name="total_floors" class="form-control" value="12" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Total Units</label>
                            <input type="number" name="total_units" class="form-control" value="48" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Construction Status</label>
                        <select name="status" class="form-select">
                            <option value="Completed">Completed & Ready for Move-In</option>
                            <option value="Under Construction">Under Construction</option>
                            <option value="Pre-Launch">Pre-Launch Phase</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning text-dark fw-bold" style="background-color: var(--orange-brand); border:none;">Create Building Block</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
