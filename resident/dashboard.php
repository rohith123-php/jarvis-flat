<?php
require_once '../includes/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if resident is logged in
if (!isset($_SESSION['resident_logged_in'])) {
    header("Location: login.php");
    exit;
}

require_once '../includes/header.php';

$customer_id = $_SESSION['resident_id'];
$bookings = [];
$notices = [];

try {
    // Fetch bookings history
    $stmt_book = $pdo->prepare("
        SELECT b.*, f.flat_no, f.block, f.city, f.price 
        FROM bookings b 
        JOIN flats f ON b.flat_id = f.id 
        WHERE b.resident_id = :customer_id 
        ORDER BY b.id DESC
    ");
    $stmt_book->execute(['customer_id' => $customer_id]);
    $bookings = $stmt_book->fetchAll();

    // Fetch Notices (announcements)
    $stmt_notices = $pdo->query("SELECT * FROM notices ORDER BY id DESC LIMIT 5");
    $notices = $stmt_notices->fetchAll();

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}
?>

<div class="container-fluid px-4">
    <div class="row">
        <!-- Sidebar Navigation -->
        <div class="col-md-3 col-lg-2 sidebar rounded-4 shadow-sm mb-4 mb-md-0">
            <h5 class="px-3 mb-4 fw-bold" style="color:var(--blue-brand);"><i class="fa-solid fa-gauge me-2"></i>Navigation</h5>
            <a href="dashboard.php" class="sidebar-link active"><i class="fa-solid fa-house-user"></i>Dashboard</a>
            <a href="profile.php" class="sidebar-link"><i class="fa-solid fa-user-gear"></i>My Profile</a>
            <a href="dashboard.php#bookings-section" class="sidebar-link"><i class="fa-solid fa-hotel"></i>Booked Flats</a>
            <a href="payment.php" class="sidebar-link"><i class="fa-solid fa-wallet"></i>Payment History</a>
            <a href="complaint.php" class="sidebar-link"><i class="fa-solid fa-triangle-exclamation"></i>Complaints</a>
            <a href="../index.php" class="sidebar-link"><i class="fa-solid fa-search"></i>Search Properties</a>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold" style="color:var(--blue-brand);">Customer Dashboard</h2>
                    <p class="text-muted mb-0">Review active apartment bookings, deposits history, and updates.</p>
                </div>
            </div>

            <!-- Bookings Log Panel -->
            <div class="card card-premium p-4 mb-4" id="bookings-section">
                <h5 class="fw-bold mb-3" style="color:var(--blue-brand);"><i class="fa-solid fa-receipt me-2"></i>My Booked Apartments</h5>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>City</th>
                                <th>Property Info</th>
                                <th>Deposit Paid</th>
                                <th>Date Reserved</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($bookings) > 0): ?>
                                <?php foreach ($bookings as $bk): ?>
                                    <tr>
                                        <td class="fw-bold text-dark">#<?php echo $bk['id']; ?></td>
                                        <td class="fw-bold text-dark"><?php echo htmlspecialchars($bk['city']); ?></td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?php echo htmlspecialchars($bk['block']); ?></div>
                                            <small class="text-muted">Unit No: <?php echo htmlspecialchars($bk['flat_no']); ?></small>
                                        </td>
                                        <td class="fw-bold text-success">₹<?php echo number_format($bk['amount_paid'], 2); ?></td>
                                        <td><?php echo date('M d, Y h:i A', strtotime($bk['booking_date'])); ?></td>
                                        <td>
                                            <span class="badge badge-status badge-vacant">
                                                Confirmed
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No active apartment bookings found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Announcements Notice Board -->
            <div class="card card-premium p-4" id="notices-section">
                <h5 class="fw-bold mb-3" style="color:var(--blue-brand);"><i class="fa-solid fa-bullhorn me-2 text-warning"></i>Announcements & Notice Board</h5>
                <div class="list-group list-group-flush">
                    <?php if (count($notices) > 0): ?>
                        <?php foreach ($notices as $notice): ?>
                            <div class="list-group-item px-0 py-3 bg-transparent border-bottom border-secondary border-opacity-10" style="color:var(--text-main) !important;">
                                <div class="d-flex justify-content-between mb-1">
                                    <h6 class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars($notice['title']); ?></h6>
                                    <small class="text-muted"><?php echo date('M d, Y', strtotime($notice['date'])); ?></small>
                                </div>
                                <p class="text-muted mb-0 small"><?php echo htmlspecialchars($notice['description']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">No notices posted.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
