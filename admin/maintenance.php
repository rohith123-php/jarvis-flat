<?php
require_once '../includes/db.php';
require_once 'includes/header.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

$message = '';
$error = '';

// Handle Add Maintenance Bill
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $resident_id = intval($_POST['resident_id']);
    $amount = floatval($_POST['amount']);
    $month = trim($_POST['month']);
    $status = $_POST['status'];
    $payment_date = ($status === 'Paid') ? date('Y-m-d') : null;

    if (empty($resident_id) || empty($amount) || empty($month)) {
        $error = 'Resident, amount, and month are required.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO maintenance (resident_id, amount, month, payment_date, status) VALUES (:resident_id, :amount, :month, :payment_date, :status)");
            $stmt->execute([
                'resident_id' => $resident_id,
                'amount' => $amount,
                'month' => $month,
                'payment_date' => $payment_date,
                'status' => $status
            ]);
            $message = 'Maintenance fee record created successfully!';
        } catch (PDOException $e) {
            $error = 'Error creating maintenance record: ' . $e->getMessage();
        }
    }
}

// Handle Mark as Paid
if (isset($_GET['mark_paid'])) {
    $id = intval($_GET['mark_paid']);
    try {
        $stmt = $pdo->prepare("UPDATE maintenance SET status = 'Paid', payment_date = :date WHERE id = :id");
        $stmt->execute([
            'date' => date('Y-m-d'),
            'id' => $id
        ]);
        $message = 'Maintenance record marked as Paid!';
    } catch (PDOException $e) {
        $error = 'Error updating status: ' . $e->getMessage();
    }
}

// Handle Delete Record
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    try {
        $stmt = $pdo->prepare("DELETE FROM maintenance WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $message = 'Maintenance record deleted successfully!';
    } catch (PDOException $e) {
        $error = 'Error deleting record: ' . $e->getMessage();
    }
}

// Fetch lists of residents
try {
    $residents_stmt = $pdo->query("
        SELECT r.id, r.name, COALESCE(f.flat_no, 'N/A') as flat_no, COALESCE(f.block, 'General') as block 
        FROM residents r 
        LEFT JOIN bookings b ON b.resident_id = r.id 
        LEFT JOIN flats f ON b.flat_id = f.id 
        GROUP BY r.id
        ORDER BY r.name ASC
    ");
    $residents = $residents_stmt->fetchAll();

    // Fetch maintenance records
    $maint_stmt = $pdo->query("
        SELECT m.*, r.name as resident_name, COALESCE(f.flat_no, 'N/A') as flat_no, COALESCE(f.block, 'General') as block 
        FROM maintenance m 
        JOIN residents r ON m.resident_id = r.id 
        LEFT JOIN bookings b ON b.resident_id = r.id 
        LEFT JOIN flats f ON b.flat_id = f.id 
        GROUP BY m.id
        ORDER BY m.id DESC
    ");
    $records = $maint_stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Error: ' . $e->getMessage();
}
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
                    <h2 class="fw-bold">Maintenance Fees</h2>
                    <p class="text-muted mb-0 d-print-none">Track maintenance collections, create invoices, and view summaries.</p>
                </div>
                <div class="d-print-none">
                    <button class="btn btn-outline-secondary me-2" onclick="window.print()">
                        <i class="fa-solid fa-print me-2"></i>Print Report
                    </button>
                    <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addBillModal">
                        <i class="fa-solid fa-plus me-2"></i>Create Bill
                    </button>
                </div>
            </div>

            <!-- Print Header Banner (Only visible during print) -->
            <div class="d-none d-print-block mb-4 text-center">
                <h2>FlatManage Community Maintenance Report</h2>
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

            <!-- Maintenance Collections Table -->
            <div class="card card-premium p-4">
                <div class="table-responsive">
                    <table class="table table-premium align-middle">
                        <thead>
                            <tr>
                                <th>Resident</th>
                                <th>Flat</th>
                                <th>Billing Month</th>
                                <th>Amount</th>
                                <th>Payment Date</th>
                                <th>Status</th>
                                <th class="text-end d-print-none">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($records) > 0): ?>
                                <?php foreach ($records as $record): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?php echo htmlspecialchars($record['resident_name']); ?></div>
                                        </td>
                                        <td>Block <?php echo htmlspecialchars($record['block']); ?> - Flat <?php echo htmlspecialchars($record['flat_no']); ?></td>
                                        <td class="fw-semibold text-indigo"><?php echo htmlspecialchars($record['month']); ?></td>
                                        <td class="fw-bold">₹<?php echo number_format($record['amount'], 2); ?></td>
                                        <td><?php echo $record['payment_date'] ? date('M d, Y', strtotime($record['payment_date'])) : 'N/A'; ?></td>
                                        <td>
                                            <?php if ($record['status'] === 'Paid'): ?>
                                                <span class="badge bg-success text-white px-3 py-1.5 rounded-pill fw-semibold" style="font-size: 0.75rem;">
                                                    <i class="fa-solid fa-circle-check me-1"></i> Paid
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark px-3 py-1.5 rounded-pill fw-semibold" style="font-size: 0.75rem;">
                                                    <i class="fa-solid fa-clock me-1"></i> Pending
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end d-print-none">
                                            <?php if ($record['status'] === 'Pending'): ?>
                                                <a href="maintenance.php?mark_paid=<?php echo $record['id']; ?>" class="btn btn-sm btn-success me-2">
                                                    <i class="fa-solid fa-check"></i> Paid
                                                </a>
                                            <?php endif; ?>
                                            <a href="maintenance.php?delete=<?php echo $record['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger" 
                                               onclick="return confirm('Are you sure you want to delete this bill record?');">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No maintenance fee records found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Maintenance Bill Modal -->
<div class="modal fade" id="addBillModal" tabindex="-1" aria-labelledby="addBillModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 bg-dark text-white p-4 rounded-top-4">
                <h5 class="modal-title fw-bold" id="addBillModalLabel"><i class="fa-solid fa-money-bill-transfer me-2"></i>Create Bill</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="maintenance.php" method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="resident_id" class="form-label fw-semibold">Select Resident</label>
                            <select class="form-select form-control-custom" id="resident_id" name="resident_id" required>
                                <option value="">-- Choose Resident --</option>
                                <?php foreach ($residents as $res): ?>
                                    <option value="<?php echo $res['id']; ?>">
                                        <?php echo htmlspecialchars($res['name']); ?> (Block <?php echo $res['block']; ?> - <?php echo $res['flat_no']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="amount" class="form-label fw-semibold">Amount ($)</label>
                            <input type="number" step="0.01" class="form-control form-control-custom" id="amount" name="amount" required placeholder="e.g. 150.00">
                        </div>
                        <div class="col-md-6">
                            <label for="month" class="form-label fw-semibold">Billing Month</label>
                            <input type="text" class="form-control form-control-custom" id="month" name="month" required placeholder="e.g. July 2026">
                        </div>
                        <div class="col-12">
                            <label for="status" class="form-label fw-semibold">Status</label>
                            <select class="form-select form-control-custom" id="status" name="status">
                                <option value="Pending">Pending</option>
                                <option value="Paid">Paid</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom">Create Bill</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
@media print {
    body {
        background-color: #fff !important;
        color: #000 !important;
    }
    .card-premium {
        box-shadow: none !important;
        border: none !important;
        padding: 0 !important;
    }
    .table-premium tr {
        background-color: transparent !important;
        box-shadow: none !important;
    }
    .table-premium td {
        border-top: 1px solid #dee2e6 !important;
        border-bottom: 1px solid #dee2e6 !important;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>
