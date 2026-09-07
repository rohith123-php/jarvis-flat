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

$error = '';
$message = '';

// Handle Delete/Cancel Booking
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    try {
        $pdo->beginTransaction();
        
        // Find flat_id to revert status to Available
        $stmt_flat = $pdo->prepare("SELECT flat_id FROM bookings WHERE id = :id");
        $stmt_flat->execute(['id' => $id]);
        $flat_id = $stmt_flat->fetchColumn();
        
        if ($flat_id) {
            $stmt_revert = $pdo->prepare("UPDATE flats SET status = 'Available' WHERE id = :flat_id");
            $stmt_revert->execute(['flat_id' => $flat_id]);
        }
        
        // Delete booking record
        $stmt_del = $pdo->prepare("DELETE FROM bookings WHERE id = :id");
        $stmt_del->execute(['id' => $id]);
        
        $pdo->commit();
        $message = 'Booking cancelled and flat released back to Available status successfully!';
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = 'Error deleting booking: ' . $e->getMessage();
    }
}

// Fetch all bookings
try {
    $stmt = $pdo->query("
        SELECT b.*, r.name as customer_name, r.email as customer_email, f.flat_no, f.block, f.city 
        FROM bookings b 
        JOIN residents r ON b.resident_id = r.id 
        JOIN flats f ON b.flat_id = f.id
        ORDER BY b.id DESC
    ");
    $bookings = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Error fetching bookings: ' . $e->getMessage();
}
?>

<div class="container-fluid px-4">
    <div class="row">
        <!-- Sidebar Navigation -->
        <div class="col-md-3 col-lg-2 d-print-none">
            <?php include 'includes/sidebar.php'; ?>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold" style="color:var(--blue-brand);">Bookings Log</h2>
                    <p class="text-muted mb-0 d-print-none">Track flat booking reservations and handle refunds or releases.</p>
                </div>
                <div class="d-print-none">
                    <button class="btn btn-outline-secondary text-dark border-secondary" onclick="window.print()">
                        <i class="fa-solid fa-print me-2"></i>Print Ledger
                    </button>
                </div>
            </div>

            <!-- Print Header Banner -->
            <div class="d-none d-print-block mb-4 text-center">
                <h2>Real Estate Booking Deposits Ledger</h2>
                <p>Generated on <?php echo date('F d, Y h:i A'); ?></p>
                <hr>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert alert-success alert-dismissible fade show d-print-none" role="alert">
                    <i class="fa-solid fa-circle-check me-2"></i><?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show d-print-none" role="alert">
                    <i class="fa-solid fa-circle-xmark me-2"></i><?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Bookings Table -->
            <div class="card card-premium p-4">
                <div class="table-responsive">
                    <table class="table table-premium align-middle">
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Buyer Details</th>
                                <th>Location City</th>
                                <th>Property Info</th>
                                <th>Deposit Paid</th>
                                <th>Booking Date</th>
                                <th class="text-end d-print-none">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($bookings) > 0): ?>
                                <?php foreach ($bookings as $bk): ?>
                                    <tr>
                                        <td class="fw-bold text-dark">#<?php echo $bk['id']; ?></td>
                                        <td>
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($bk['customer_name']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($bk['customer_email']); ?></small>
                                        </td>
                                        <td class="fw-bold text-dark"><?php echo htmlspecialchars($bk['city']); ?></td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?php echo htmlspecialchars($bk['block']); ?></div>
                                            <small class="text-muted">Unit No: <?php echo htmlspecialchars($bk['flat_no']); ?></small>
                                        </td>
                                        <td class="fw-bold text-success">₹<?php echo number_format($bk['amount_paid'], 2); ?></td>
                                        <td><?php echo date('M d, Y h:i A', strtotime($bk['booking_date'])); ?></td>
                                        <td class="text-end d-print-none">
                                            <a href="bookings.php?delete=<?php echo $bk['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger" 
                                               onclick="return confirm('Are you sure you want to cancel this booking deposit and release the apartment back to vacant status?');"
                                               style="border-radius:4px;">
                                                <i class="fa-solid fa-trash-can me-1"></i> Release Unit
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No flat bookings registered in system ledger.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
