<?php
require_once '../includes/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

// Check CSV Export Trigger
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    $filename = "jarvis_export_" . $export_type . "_" . date('Ymd_His') . ".csv";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    $output = fopen('php://output', 'w');

    if ($export_type === 'payments') {
        fputcsv($output, ['ID', 'Transaction ID', 'Customer Name', 'Customer Email', 'Amount', 'Payment Method', 'Payment Status', 'Date']);
        $rows = $pdo->query("SELECT p.id, p.transaction_id, r.name, r.email, p.amount, p.payment_method, p.payment_status, p.payment_date FROM payments p JOIN residents r ON p.resident_id = r.id ORDER BY p.id DESC")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) fputcsv($output, $row);
    } elseif ($export_type === 'bookings') {
        fputcsv($output, ['Booking ID', 'Customer Name', 'Customer Phone', 'Block', 'Flat No', 'City', 'Amount Paid', 'Status', 'Date']);
        $rows = $pdo->query("SELECT b.id, r.name, r.phone, f.block, f.flat_no, f.city, b.amount_paid, b.payment_status, b.booking_date FROM bookings b JOIN residents r ON b.resident_id = r.id JOIN flats f ON b.flat_id = f.id ORDER BY b.id DESC")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) fputcsv($output, $row);
    } elseif ($export_type === 'flats') {
        fputcsv($output, ['ID', 'Block', 'Flat No', 'City', 'BHK', 'Price', 'Status', 'Type']);
        $rows = $pdo->query("SELECT id, block, flat_no, city, bhk, price, status, type FROM flats ORDER BY city ASC, flat_no ASC")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

// Fetch Metrics for Charts & Analytics
$city_stats = $pdo->query("SELECT city, COUNT(*) as total, SUM(CASE WHEN status='Booked' THEN 1 ELSE 0 END) as booked FROM flats GROUP BY city")->fetchAll();

$type_stats = $pdo->query("SELECT type, COUNT(*) as count FROM flats GROUP BY type")->fetchAll();

$total_revenue = $pdo->query("SELECT SUM(amount) FROM payments WHERE payment_status = 'Paid'")->fetchColumn() ?: 0;
$total_booked_count = $pdo->query("SELECT COUNT(*) FROM flats WHERE status = 'Booked'")->fetchColumn();
$total_flats_count = $pdo->query("SELECT COUNT(*) FROM flats")->fetchColumn();
$occupancy_rate = $total_flats_count > 0 ? round(($total_booked_count / $total_flats_count) * 100, 1) : 0;

require_once 'includes/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
                    <h2 class="fw-bold" style="color:var(--blue-brand);">Reports & Advanced Analytics</h2>
                    <p class="text-muted mb-0">Monthly booking revenue trends, city occupancy percentages, and data export downloads.</p>
                </div>
                <div class="dropdown">
                    <button class="btn btn-dark dropdown-toggle px-3 py-2 fw-semibold" type="button" data-bs-toggle="dropdown">
                        <i class="fa-solid fa-download me-1.5 text-warning"></i> One-Click Export Data
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li><a class="dropdown-menu-item dropdown-item fw-semibold" href="reports.php?export=payments"><i class="fa-solid fa-file-csv me-2 text-success"></i> Export Payments CSV</a></li>
                        <li><a class="dropdown-menu-item dropdown-item fw-semibold" href="reports.php?export=bookings"><i class="fa-solid fa-file-csv me-2 text-primary"></i> Export Bookings CSV</a></li>
                        <li><a class="dropdown-menu-item dropdown-item fw-semibold" href="reports.php?export=flats"><i class="fa-solid fa-file-csv me-2 text-warning"></i> Export Flats Inventory CSV</a></li>
                    </ul>
                </div>
            </div>

            <!-- Performance KPI Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="p-4 bg-white rounded-3 border border-light-subtle shadow-sm">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small fw-bold text-uppercase">Total Booking Revenue</span>
                            <span class="p-2 rounded-circle bg-success bg-opacity-10 text-success"><i class="fa-solid fa-sack-dollar"></i></span>
                        </div>
                        <h2 class="fw-extrabold text-success mb-0">₹<?php echo number_format($total_revenue, 2); ?></h2>
                        <div class="text-muted small mt-2"><i class="fa-solid fa-arrow-trend-up text-success me-1"></i> Lifetime collected payments</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-4 bg-white rounded-3 border border-light-subtle shadow-sm">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small fw-bold text-uppercase">Overall Occupancy Rate</span>
                            <span class="p-2 rounded-circle bg-warning bg-opacity-10 text-warning"><i class="fa-solid fa-chart-pie"></i></span>
                        </div>
                        <h2 class="fw-extrabold text-dark mb-0"><?php echo $occupancy_rate; ?>%</h2>
                        <div class="text-muted small mt-2"><?php echo $total_booked_count; ?> of <?php echo $total_flats_count; ?> units reserved</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-4 bg-white rounded-3 border border-light-subtle shadow-sm">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small fw-bold text-uppercase">Active Cities Covered</span>
                            <span class="p-2 rounded-circle bg-primary bg-opacity-10 text-primary"><i class="fa-solid fa-city"></i></span>
                        </div>
                        <h2 class="fw-extrabold text-primary mb-0"><?php echo count($city_stats); ?> Metros</h2>
                        <div class="text-muted small mt-2">Chennai, Bengaluru, Dubai & more</div>
                    </div>
                </div>
            </div>

            <!-- Charts Grid -->
            <div class="row g-4 mb-4">
                <!-- Chart 1: City Occupancy Breakdown -->
                <div class="col-md-7">
                    <div class="card border border-light-subtle rounded-3 shadow-sm p-4 h-100">
                        <h5 class="fw-bold mb-3" style="color:var(--blue-brand);"><i class="fa-solid fa-chart-bar me-2 text-warning"></i>Property Distribution by City</h5>
                        <canvas id="cityChart" height="230"></canvas>
                    </div>
                </div>
                <!-- Chart 2: Property Type Allocation -->
                <div class="col-md-5">
                    <div class="card border border-light-subtle rounded-3 shadow-sm p-4 h-100">
                        <h5 class="fw-bold mb-3" style="color:var(--blue-brand);"><i class="fa-solid fa-chart-pie me-2 text-warning"></i>Property Types Ratio</h5>
                        <canvas id="typeChart" height="230"></canvas>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
// Chart 1: City Occupancy Bar Chart
const cityCtx = document.getElementById('cityChart').getContext('2d');
new Chart(cityCtx, {
    type: 'bar',
    data: {
        labels: [<?php foreach ($city_stats as $cs) echo "'" . $cs['city'] . "',"; ?>],
        datasets: [{
            label: 'Total Units Registered',
            data: [<?php foreach ($city_stats as $cs) echo $cs['total'] . ","; ?>],
            backgroundColor: 'rgba(16, 49, 120, 0.85)'
        }, {
            label: 'Booked Units',
            data: [<?php foreach ($city_stats as $cs) echo $cs['booked'] . ","; ?>],
            backgroundColor: 'rgba(242, 161, 34, 0.95)'
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' } }
    }
});

// Chart 2: Property Types Pie Chart
const typeCtx = document.getElementById('typeChart').getContext('2d');
new Chart(typeCtx, {
    type: 'doughnut',
    data: {
        labels: [<?php foreach ($type_stats as $ts) echo "'" . $ts['type'] . "',"; ?>],
        datasets: [{
            data: [<?php foreach ($type_stats as $ts) echo $ts['count'] . ","; ?>],
            backgroundColor: ['#103178', '#f2a122', '#10b981', '#ef4444', '#8b5cf6']
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } }
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
