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

$resident_id = $_SESSION['resident_id'];
$message = '';
$error = '';

// Handle Simulated Payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'pay') {
    $bill_id = intval($_POST['bill_id']);
    
    try {
        // Verify bill belongs to this resident and is pending
        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM maintenance WHERE id = :id AND resident_id = :res_id AND status = 'Pending'");
        $check_stmt->execute(['id' => $bill_id, 'res_id' => $resident_id]);
        
        if ($check_stmt->fetchColumn() > 0) {
            $update_stmt = $pdo->prepare("UPDATE maintenance SET status = 'Paid', payment_date = :date WHERE id = :id");
            $update_stmt->execute([
                'date' => date('Y-m-d'),
                'id' => $bill_id
            ]);
            $message = 'Payment successful! Thank you for clearing your dues.';
        } else {
            $error = 'Invalid payment request or bill already paid.';
        }
    } catch (PDOException $e) {
        $error = 'Error processing payment: ' . $e->getMessage();
    }
}

// Fetch maintenance records
try {
    $stmt = $pdo->prepare("SELECT * FROM maintenance WHERE resident_id = :resident_id ORDER BY id DESC");
    $stmt->execute(['resident_id' => $resident_id]);
    $records = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Error fetching records: ' . $e->getMessage();
}
?>

<div class="container-fluid px-4">
    <div class="row">
        <!-- Sidebar Navigation -->
        <div class="col-md-3 col-lg-2 sidebar rounded-4 shadow-sm mb-4 mb-md-0">
            <h5 class="px-3 mb-4 fw-bold" style="color:var(--blue-brand);"><i class="fa-solid fa-gauge me-2"></i>Navigation</h5>
            <a href="dashboard.php" class="sidebar-link"><i class="fa-solid fa-house-user"></i>Dashboard</a>
            <a href="profile.php" class="sidebar-link"><i class="fa-solid fa-user-gear"></i>My Profile</a>
            <a href="dashboard.php#bookings-section" class="sidebar-link"><i class="fa-solid fa-hotel"></i>Booked Flats</a>
            <a href="payment.php" class="sidebar-link active"><i class="fa-solid fa-wallet"></i>Payment History</a>
            <a href="complaint.php" class="sidebar-link"><i class="fa-solid fa-triangle-exclamation"></i>Complaints</a>
            <a href="../index.php" class="sidebar-link"><i class="fa-solid fa-search"></i>Search Properties</a>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold">Pay Maintenance</h2>
                    <p class="text-muted mb-0">View maintenance dues history and process payments securely.</p>
                </div>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-circle-check me-2"></i><?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-circle-xmark me-2"></i><?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Bills Lists -->
            <div class="card card-premium p-4">
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-receipt text-indigo me-2"></i>Dues History</h5>
                <div class="table-responsive">
                    <table class="table table-premium align-middle">
                        <thead>
                            <tr>
                                <th>Billing Month</th>
                                <th>Amount</th>
                                <th>Payment Date</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($records) > 0): ?>
                                <?php foreach ($records as $record): ?>
                                    <tr>
                                        <td class="fw-bold text-indigo"><?php echo htmlspecialchars($record['month']); ?></td>
                                        <td class="fw-bold">₹<?php echo number_format($record['amount'], 2); ?></td>
                                        <td><?php echo $record['payment_date'] ? date('M d, Y', strtotime($record['payment_date'])) : 'N/A'; ?></td>
                                        <td>
                                            <span class="badge badge-status <?php echo $record['status'] === 'Paid' ? 'badge-occupied' : 'badge-pending'; ?>">
                                                <?php echo htmlspecialchars($record['status']); ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <?php if ($record['status'] === 'Pending'): ?>
                                                <button class="btn btn-sm btn-success pay-btn" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#payModal"
                                                        data-id="<?php echo $record['id']; ?>"
                                                        data-month="<?php echo htmlspecialchars($record['month']); ?>"
                                                        data-amount="<?php echo number_format($record['amount'], 2); ?>">
                                                    <i class="fa-solid fa-credit-card me-1"></i> Pay Now
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-outline-secondary" disabled>
                                                    <i class="fa-solid fa-circle-check"></i> Paid
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No maintenance fee record found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Pay Simulation Modal -->
<div class="modal fade" id="payModal" tabindex="-1" aria-labelledby="payModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 bg-dark text-white p-4 rounded-top-4">
                <h5 class="modal-title fw-bold" id="payModalLabel"><i class="fa-solid fa-shield-halved me-2"></i>Checkout Simulation</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="payment.php" method="POST">
                <input type="hidden" name="action" value="pay">
                <input type="hidden" name="bill_id" id="pay_bill_id">
                <div class="modal-body p-4">
                    <div class="text-center mb-4">
                        <small class="text-muted">Payment amount for <strong id="pay_month"></strong></small>
                        <h2 class="fw-extrabold text-indigo mt-1">$<span id="pay_amount"></span></h2>
                    </div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Cardholder Name</label>
                            <input type="text" class="form-control form-control-custom" placeholder="e.g. John Doe" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Card Number</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-credit-card text-muted"></i></span>
                                <input type="text" class="form-control form-control-custom border-start-0" placeholder="4111 2222 3333 4444" required maxlength="19">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Expiry Date</label>
                            <input type="text" class="form-control form-control-custom" placeholder="MM/YY" required maxlength="5">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">CVV</label>
                            <input type="password" class="form-control form-control-custom" placeholder="•••" required maxlength="4">
                        </div>
                    </div>
                    <div class="alert alert-info mt-4 mb-0" role="alert">
                        <i class="fa-solid fa-circle-info me-2"></i>This is a secure checkout simulation. No real money will be charged.
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4 fw-bold">Confirm Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const payBtns = document.querySelectorAll('.pay-btn');
    payBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('pay_bill_id').value = this.dataset.id;
            document.getElementById('pay_month').textContent = this.dataset.month;
            document.getElementById('pay_amount').textContent = this.dataset.amount;
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
