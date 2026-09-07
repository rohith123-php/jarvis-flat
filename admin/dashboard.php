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

try {
    // 1. Total Flats
    $total_flats = $pdo->query("SELECT COUNT(*) FROM flats")->fetchColumn();

    // 2. Booked Flats
    $booked_flats = $pdo->query("SELECT COUNT(*) FROM flats WHERE status = 'Booked'")->fetchColumn();

    // 3. Available Flats
    $available_flats = $pdo->query("SELECT COUNT(*) FROM flats WHERE status = 'Available'")->fetchColumn();

    // 4. Total Customers
    $total_residents = $pdo->query("SELECT COUNT(*) FROM residents")->fetchColumn();

    // 5. Total Booking Revenue
    $raw_revenue = $pdo->query("SELECT SUM(amount_paid) FROM bookings WHERE payment_status = 'Paid'")->fetchColumn() ?: 0;
    
    // Format Revenue e.g. ₹12.70 L or ₹1,125,000.00
    if ($raw_revenue >= 100000) {
        $formatted_revenue = '₹' . number_format($raw_revenue / 100000, 2) . ' L';
    } else {
        $formatted_revenue = '₹' . number_format($raw_revenue, 2);
    }

    // Fetch recent bookings with details
    $stmt_bks = $pdo->query("
        SELECT b.*, r.name as customer_name, f.flat_no, f.block, f.city 
        FROM bookings b 
        JOIN residents r ON b.resident_id = r.id 
        JOIN flats f ON b.flat_id = f.id
        ORDER BY b.id DESC LIMIT 5
    ");
    $recent_bookings = $stmt_bks->fetchAll();

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}
?>

<div class="container-fluid px-4">
    <div class="row">
        <!-- Compact Sidebar Navigation -->
        <div class="col-md-3 col-lg-2">
            <?php include 'includes/sidebar.php'; ?>
        </div>

        <!-- Main Dashboard Area -->
        <div class="col-md-9 col-lg-10">
            <!-- Header Welcome Title -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold mb-1" style="color: #123B7A;">Dashboard</h3>
                    <p class="text-muted mb-0 small">Welcome back, Admin! Here is your flat management and financial overview.</p>
                </div>
            </div>

            <!-- 5 Standard Compact Stat Cards Row -->
            <div class="row g-3 mb-4">
                <!-- Total Flats -->
                <div class="col">
                    <a href="menu.php" class="text-decoration-none">
                        <div class="stat-card-compact h-100">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted fw-semibold fs-7 text-uppercase">Total Flats</span>
                                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:36px; height:36px; background: rgba(18, 59, 122, 0.12); color: #123B7A;">
                                    <i class="fa-solid fa-building" style="font-size: 1rem;"></i>
                                </div>
                            </div>
                            <h3 class="fw-bold mb-0 text-dark"><?php echo $total_flats; ?></h3>
                        </div>
                    </a>
                </div>

                <!-- Booked Flats -->
                <div class="col">
                    <a href="menu.php" class="text-decoration-none">
                        <div class="stat-card-compact h-100">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted fw-semibold fs-7 text-uppercase">Booked</span>
                                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:36px; height:36px; background: rgba(14, 165, 233, 0.12); color: #0284c7;">
                                    <i class="fa-solid fa-house-lock" style="font-size: 1rem;"></i>
                                </div>
                            </div>
                            <h3 class="fw-bold mb-0 text-dark"><?php echo $booked_flats; ?></h3>
                        </div>
                    </a>
                </div>

                <!-- Available / Vacant Flats -->
                <div class="col">
                    <a href="menu.php" class="text-decoration-none">
                        <div class="stat-card-compact h-100">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted fw-semibold fs-7 text-uppercase">Vacant</span>
                                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:36px; height:36px; background: rgba(39, 174, 96, 0.12); color: #16a34a;">
                                    <i class="fa-solid fa-house-chimney-user" style="font-size: 1rem;"></i>
                                </div>
                            </div>
                            <h3 class="fw-bold mb-0 text-dark"><?php echo $available_flats; ?></h3>
                        </div>
                    </a>
                </div>

                <!-- Customers / Residents -->
                <div class="col">
                    <a href="residents.php" class="text-decoration-none">
                        <div class="stat-card-compact h-100">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted fw-semibold fs-7 text-uppercase">Users</span>
                                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:36px; height:36px; background: rgba(245, 158, 11, 0.18); color: #b45309;">
                                    <i class="fa-solid fa-users" style="font-size: 1rem; color: #b45309 !important;"></i>
                                </div>
                            </div>
                            <h3 class="fw-bold mb-0 text-dark"><?php echo $total_residents; ?></h3>
                        </div>
                    </a>
                </div>

                <!-- Total Revenue -->
                <div class="col">
                    <a href="payments.php" class="text-decoration-none">
                        <div class="stat-card-compact h-100">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted fw-semibold fs-7 text-uppercase">Total Revenue</span>
                                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:36px; height:36px; background: rgba(39, 174, 96, 0.12); color: #16a34a;">
                                    <i class="fa-solid fa-sack-dollar" style="font-size: 1rem;"></i>
                                </div>
                            </div>
                            <h4 class="fw-bold mb-0 text-dark"><?php echo $formatted_revenue; ?></h4>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Charts Section (Revenue Overview & Flat Status) -->
            <div class="row g-4 mb-4">
                <!-- Revenue Overview Chart -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm rounded-3 p-4 h-100" style="background:#ffffff;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="fw-bold mb-0" style="color: #123B7A;">Revenue Overview</h6>
                                <small class="text-muted">Monthly booking revenue vs deposits (2026)</small>
                            </div>
                            <span class="badge bg-light text-dark border px-2.5 py-1">₹ INR</span>
                        </div>
                        <div style="height: 250px; position: relative;">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Flat Status Doughnut Chart -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm rounded-3 p-4 h-100" style="background:#ffffff;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="fw-bold mb-0" style="color: #123B7A;">Flat Status</h6>
                                <small class="text-muted">Inventory occupancy breakdown</small>
                            </div>
                        </div>
                        <div style="height: 210px; position: relative;" class="d-flex align-items-center justify-content-center">
                            <canvas id="statusChart"></canvas>
                        </div>
                        <div class="d-flex justify-content-center gap-3 mt-3 pt-2 border-top">
                            <small class="fw-semibold"><i class="fa-solid fa-circle text-success me-1"></i>Vacant (<?php echo $available_flats; ?>)</small>
                            <small class="fw-semibold"><i class="fa-solid fa-circle me-1" style="color:#123B7A;"></i>Booked (<?php echo $booked_flats; ?>)</small>
                            <small class="fw-semibold"><i class="fa-solid fa-circle text-warning me-1"></i>Reserved (0)</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Bookings Table Section -->
            <div class="card border-0 shadow-sm rounded-3 p-4" style="background:#ffffff;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0" style="color: #123B7A;">Recent Bookings</h6>
                    <a href="bookings.php" class="btn btn-outline-secondary btn-sm px-3 rounded-pill fw-semibold">View All</a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr class="text-uppercase fs-7 text-muted">
                                <th>Customer</th>
                                <th>Flat</th>
                                <th>Building / City</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($recent_bookings) > 0): ?>
                                <?php foreach ($recent_bookings as $bk): ?>
                                    <tr>
                                        <td class="fw-bold text-dark"><?php echo htmlspecialchars($bk['customer_name']); ?></td>
                                        <td><span class="badge bg-light text-dark border fw-semibold">Flat <?php echo htmlspecialchars($bk['flat_no']); ?></span></td>
                                        <td class="small text-muted"><?php echo htmlspecialchars($bk['block']); ?>, <?php echo htmlspecialchars($bk['city']); ?></td>
                                        <td class="fw-bold text-dark">₹<?php echo number_format($bk['amount_paid'], 2); ?></td>
                                        <td class="small text-muted"><?php echo date('M d', strtotime($bk['booking_date'])); ?></td>
                                        <td>
                                            <?php if ($bk['payment_status'] === 'Paid'): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 rounded-pill fw-semibold">Paid</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2.5 py-1 rounded-pill fw-semibold">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No recent bookings found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Visualization Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Revenue Overview Line Chart
    const ctxRev = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctxRev, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'Monthly Revenue (₹)',
                data: [150000, 280000, 420000, 390000, 650000, 810000, 950000, <?php echo $raw_revenue; ?>, 0, 0, 0, 0],
                borderColor: '#123B7A',
                backgroundColor: 'rgba(18, 59, 122, 0.08)',
                borderWidth: 3,
                fill: true,
                tension: 0.35,
                pointRadius: 4,
                pointBackgroundColor: '#F59E0B'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#F3F4F6' },
                    ticks: {
                        callback: function(val) { return '₹' + (val >= 100000 ? (val/100000) + 'L' : val); }
                    }
                },
                x: { grid: { display: false } }
            }
        }
    });

    // 2. Flat Status Doughnut Chart
    const ctxStatus = document.getElementById('statusChart').getContext('2d');
    new Chart(ctxStatus, {
        type: 'doughnut',
        data: {
            labels: ['Vacant', 'Booked', 'Reserved'],
            datasets: [{
                data: [<?php echo $available_flats; ?>, <?php echo $booked_flats; ?>, 0],
                backgroundColor: ['#16A34A', '#123B7A', '#F59E0B'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            cutout: '72%'
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
