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

// Handle Payment Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $payment_id = intval($_POST['payment_id']);
        $new_status = $_POST['status'];
        try {
            $stmt = $pdo->prepare("UPDATE payments SET payment_status = :status WHERE id = :id");
            $stmt->execute(['status' => $new_status, 'id' => $payment_id]);
            
            // Log Activity
            $stmt_log = $pdo->prepare("INSERT INTO activity_logs (admin_username, action, module, details) VALUES (:user, :act, 'Payments', :det)");
            $stmt_log->execute([
                'user' => $_SESSION['admin_username'] ?? 'admin',
                'act' => 'Updated Payment Status',
                'det' => "Updated Payment #{$payment_id} status to {$new_status}"
            ]);

            $message = "Payment status updated to {$new_status} successfully!";
        } catch (PDOException $e) {
            $error = "Error updating payment: " . $e->getMessage();
        }
    } elseif ($_POST['action'] === 'record_payment') {
        $resident_id = intval($_POST['resident_id']);
        $amount = floatval($_POST['amount']);
        $method = $_POST['payment_method'];
        $status = $_POST['payment_status'];
        $txn_id = 'TXN-' . rand(10000000, 99999999);

        try {
            $stmt = $pdo->prepare("INSERT INTO payments (resident_id, transaction_id, amount, payment_method, payment_status) VALUES (:res, :txn, :amt, :meth, :stat)");
            $stmt->execute([
                'res' => $resident_id,
                'txn' => $txn_id,
                'amt' => $amount,
                'meth' => $method,
                'stat' => $status
            ]);
            $message = "New payment record {$txn_id} created successfully!";
        } catch (PDOException $e) {
            $error = "Error recording payment: " . $e->getMessage();
        }
    }
}

// Fetch Filter
$status_filter = $_GET['status'] ?? 'all';
$query = "SELECT p.*, r.name as customer_name, r.email as customer_email, r.phone as customer_phone 
          FROM payments p 
          JOIN residents r ON p.resident_id = r.id";

if ($status_filter !== 'all') {
    $query .= " WHERE p.payment_status = " . $pdo->quote($status_filter);
}
$query .= " ORDER BY p.id DESC";

$payments = $pdo->query($query)->fetchAll();
$residents = $pdo->query("SELECT id, name, email FROM residents ORDER BY name ASC")->fetchAll();

// Payment Metrics
$total_paid = $pdo->query("SELECT SUM(amount) FROM payments WHERE payment_status = 'Paid'")->fetchColumn() ?: 0;
$total_pending = $pdo->query("SELECT SUM(amount) FROM payments WHERE payment_status = 'Pending'")->fetchColumn() ?: 0;
$total_refunded = $pdo->query("SELECT SUM(amount) FROM payments WHERE payment_status = 'Refunded'")->fetchColumn() ?: 0;
$count_txns = $pdo->query("SELECT COUNT(*) FROM payments")->fetchColumn();

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
                    <h2 class="fw-bold" style="color:var(--blue-brand);">Payment Management</h2>
                    <p class="text-muted mb-0">Track booking deposits, pending balances, refunds, and financial transactions.</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-warning text-dark fw-bold px-3 py-2" data-bs-toggle="modal" data-bs-target="#recordPaymentModal" style="background-color: var(--orange-brand); border: none;">
                        <i class="fa-solid fa-plus me-1.5"></i> Record Payment
                    </button>
                    <a href="reports.php?export=payments" class="btn btn-outline-secondary px-3 py-2 fw-semibold">
                        <i class="fa-solid fa-file-csv me-1.5"></i> Export CSV
                    </a>
                </div>
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

            <!-- Financial Metrics Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="p-3 bg-white rounded-3 border border-light-subtle shadow-sm">
                        <span class="text-muted small fw-semibold text-uppercase">Total Collected</span>
                        <h3 class="fw-bold text-success mb-0 mt-1">₹<?php echo number_format($total_paid, 2); ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 bg-white rounded-3 border border-light-subtle shadow-sm">
                        <span class="text-muted small fw-semibold text-uppercase">Pending Balance</span>
                        <h3 class="fw-bold text-warning mb-0 mt-1">₹<?php echo number_format($total_pending, 2); ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 bg-white rounded-3 border border-light-subtle shadow-sm">
                        <span class="text-muted small fw-semibold text-uppercase">Refunded Amount</span>
                        <h3 class="fw-bold text-danger mb-0 mt-1">₹<?php echo number_format($total_refunded, 2); ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 bg-white rounded-3 border border-light-subtle shadow-sm">
                        <span class="text-muted small fw-semibold text-uppercase">Total Transactions</span>
                        <h3 class="fw-bold text-primary mb-0 mt-1"><?php echo $count_txns; ?></h3>
                    </div>
                </div>
            </div>

            <!-- Status Filter Tabs -->
            <div class="mb-4 d-flex gap-2">
                <a href="payments.php?status=all" class="btn <?php echo ($status_filter == 'all') ? 'btn-dark' : 'btn-outline-dark'; ?> btn-sm rounded-pill px-3">All Payments</a>
                <a href="payments.php?status=Paid" class="btn <?php echo ($status_filter == 'Paid') ? 'btn-success' : 'btn-outline-success'; ?> btn-sm rounded-pill px-3">Paid</a>
                <a href="payments.php?status=Pending" class="btn <?php echo ($status_filter == 'Pending') ? 'btn-warning text-dark' : 'btn-outline-warning'; ?> btn-sm rounded-pill px-3">Pending</a>
                <a href="payments.php?status=Failed" class="btn <?php echo ($status_filter == 'Failed') ? 'btn-danger' : 'btn-outline-danger'; ?> btn-sm rounded-pill px-3">Failed</a>
                <a href="payments.php?status=Refunded" class="btn <?php echo ($status_filter == 'Refunded') ? 'btn-secondary' : 'btn-outline-secondary'; ?> btn-sm rounded-pill px-3">Refunded</a>
            </div>

            <!-- Payments Table -->
            <div class="card border border-light-subtle rounded-3 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Txn ID</th>
                                <th>Customer Details</th>
                                <th>Amount</th>
                                <th>Payment Method</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($payments) > 0): ?>
                                <?php foreach ($payments as $p): ?>
                                    <tr>
                                        <td><code class="text-dark bg-light px-2 py-1 rounded fw-bold"><?php echo htmlspecialchars($p['transaction_id']); ?></code></td>
                                        <td>
                                            <div class="fw-bold"><?php echo htmlspecialchars($p['customer_name']); ?></div>
                                            <div class="text-muted small"><?php echo htmlspecialchars($p['customer_email']); ?> | <?php echo htmlspecialchars($p['customer_phone']); ?></div>
                                        </td>
                                        <td class="fw-bold text-dark">₹<?php echo number_format($p['amount'], 2); ?></td>
                                        <td>
                                            <span class="badge bg-light text-dark border border-secondary border-opacity-20">
                                                <i class="fa-solid fa-credit-card me-1 text-primary"></i> <?php echo htmlspecialchars($p['payment_method']); ?>
                                            </span>
                                        </td>
                                        <td class="small text-muted"><?php echo date('M d, Y H:i', strtotime($p['payment_date'])); ?></td>
                                        <td>
                                            <?php if ($p['payment_status'] === 'Paid'): ?>
                                                <span class="badge bg-success text-white px-3 py-1.5 rounded-pill fw-bold" style="background-color: #15803d !important; color: #ffffff !important; font-size: 0.75rem;"><i class="fa-solid fa-circle-check me-1"></i> Paid</span>
                                            <?php elseif ($p['payment_status'] === 'Pending'): ?>
                                                <span class="badge bg-warning text-dark px-3 py-1.5 rounded-pill fw-bold" style="background-color: #f59e0b !important; color: #0f172a !important; font-size: 0.75rem;"><i class="fa-solid fa-clock me-1"></i> Pending</span>
                                            <?php elseif ($p['payment_status'] === 'Failed'): ?>
                                                <span class="badge bg-danger text-white px-3 py-1.5 rounded-pill fw-bold" style="background-color: #dc2626 !important; color: #ffffff !important; font-size: 0.75rem;"><i class="fa-solid fa-circle-xmark me-1"></i> Failed</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary text-white px-3 py-1.5 rounded-pill fw-bold" style="background-color: #475569 !important; color: #ffffff !important; font-size: 0.75rem;"><i class="fa-solid fa-rotate-left me-1"></i> Refunded</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <form method="POST" class="d-inline-flex gap-1">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="payment_id" value="<?php echo $p['id']; ?>">
                                                
                                                <?php if ($p['payment_status'] !== 'Paid'): ?>
                                                    <button type="submit" name="status" value="Paid" class="btn btn-outline-success btn-sm" title="Mark Paid">
                                                        <i class="fa-solid fa-check"></i>
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <?php if ($p['payment_status'] === 'Paid'): ?>
                                                    <button type="submit" name="status" value="Refunded" class="btn btn-outline-danger btn-sm" onclick="return confirm('Refund this transaction?')" title="Issue Refund">
                                                        <i class="fa-solid fa-arrow-rotate-left"></i> Refund
                                                    </button>
                                                <?php endif; ?>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-receipt fa-2x mb-2 text-secondary"></i>
                                        <div>No payment records found matching status filter.</div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Record Payment Modal -->
<div class="modal fade" id="recordPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-plus-circle me-2 text-warning"></i>Record Customer Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="record_payment">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Customer</label>
                        <select name="resident_id" class="form-select" required>
                            <?php foreach ($residents as $r): ?>
                                <option value="<?php echo $r['id']; ?>"><?php echo htmlspecialchars($r['name']); ?> (<?php echo htmlspecialchars($r['email']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Amount (₹)</label>
                        <input type="number" step="0.01" name="amount" class="form-control" placeholder="100000.00" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Payment Method</label>
                        <select name="payment_method" class="form-select">
                            <option value="Credit Card">Credit Card</option>
                            <option value="Net Banking">Net Banking</option>
                            <option value="UPI">UPI</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Cheque">Cheque</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Payment Status</label>
                        <select name="payment_status" class="form-select">
                            <option value="Paid">Paid (Confirmed)</option>
                            <option value="Pending">Pending Verification</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning text-dark fw-bold" style="background-color: var(--orange-brand); border:none;">Record Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
