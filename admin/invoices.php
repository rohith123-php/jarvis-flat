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

// Generate New Invoice Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate_invoice') {
    $booking_id = intval($_POST['booking_id']);
    $subtotal = floatval($_POST['subtotal']);
    $tax_rate = floatval($_POST['tax_rate']); // e.g. 18% GST
    $discount = floatval($_POST['discount']);
    $due_days = intval($_POST['due_days']);

    $tax_amount = ($subtotal * $tax_rate) / 100;
    $grand_total = ($subtotal + $tax_amount) - $discount;

    // Fetch booking to get resident_id
    $stmt_bk = $pdo->prepare("SELECT resident_id FROM bookings WHERE id = :id");
    $stmt_bk->execute(['id' => $booking_id]);
    $booking = $stmt_bk->fetch();

    if ($booking) {
        $invoice_no = 'INV-' . date('Y') . '-' . sprintf('%03d', rand(100, 999));
        $issued_date = date('Y-m-d');
        $due_date = date('Y-m-d', strtotime("+{$due_days} days"));

        try {
            $stmt = $pdo->prepare("INSERT INTO invoices (invoice_no, booking_id, resident_id, subtotal, tax_amount, discount_amount, grand_total, issued_date, due_date, status) 
                                  VALUES (:inv, :bk, :res, :sub, :tax, :disc, :grand, :iss, :due, 'Paid')");
            $stmt->execute([
                'inv' => $invoice_no,
                'bk' => $booking_id,
                'res' => $booking['resident_id'],
                'sub' => $subtotal,
                'tax' => $tax_amount,
                'disc' => $discount,
                'grand' => $grand_total,
                'iss' => $issued_date,
                'due' => $due_date
            ]);

            // Log activity
            $stmt_log = $pdo->prepare("INSERT INTO activity_logs (admin_username, action, module, details) VALUES (:user, 'Generated Invoice', 'Invoices', :det)");
            $stmt_log->execute([
                'user' => $_SESSION['admin_username'] ?? 'admin',
                'det' => "Generated Invoice {$invoice_no} for Booking #{$booking_id}"
            ]);

            $message = "Invoice {$invoice_no} generated successfully!";
        } catch (PDOException $e) {
            $error = "Error generating invoice: " . $e->getMessage();
        }
    } else {
        $error = "Invalid booking reference selected.";
    }
}

// Fetch all invoices
$invoices = $pdo->query("
    SELECT i.*, r.name as customer_name, r.email as customer_email, f.flat_no, f.block, f.city 
    FROM invoices i
    JOIN residents r ON i.resident_id = r.id
    JOIN bookings b ON i.booking_id = b.id
    JOIN flats f ON b.flat_id = f.id
    ORDER BY i.id DESC
")->fetchAll();

// Fetch bookings for invoice creation modal
$bookings = $pdo->query("
    SELECT b.id, b.amount_paid, r.name as customer_name, f.block, f.flat_no, f.city 
    FROM bookings b
    JOIN residents r ON b.resident_id = r.id
    JOIN flats f ON b.flat_id = f.id
    ORDER BY b.id DESC
")->fetchAll();

require_once 'includes/header.php';
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
                    <h2 class="fw-bold" style="color:var(--blue-brand);">Invoice Generation & Receipts</h2>
                    <p class="text-muted mb-0">Generate professional tax invoices, receipt statements, and downloadable customer bills.</p>
                </div>
                <button class="btn btn-warning text-dark fw-bold px-3 py-2" data-bs-toggle="modal" data-bs-target="#generateInvoiceModal" style="background-color: var(--orange-brand); border: none;">
                    <i class="fa-solid fa-file-circle-plus me-1.5"></i> Generate New Invoice
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

            <!-- Invoices Table -->
            <div class="card border border-light-subtle rounded-3 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Invoice #</th>
                                <th>Customer Name</th>
                                <th>Property Details</th>
                                <th>Issued / Due Date</th>
                                <th>Grand Total</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($invoices) > 0): ?>
                                <?php foreach ($invoices as $inv): ?>
                                    <tr>
                                        <td>
                                            <code class="text-dark bg-light px-2.5 py-1 rounded fw-bold" style="font-size: 0.85rem; border: 1px solid #cbd5e1;">
                                                <?php echo htmlspecialchars($inv['invoice_no']); ?>
                                            </code>
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?php echo htmlspecialchars($inv['customer_name']); ?></div>
                                            <div class="text-muted small"><?php echo htmlspecialchars($inv['customer_email']); ?></div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?php echo htmlspecialchars($inv['block']); ?></div>
                                            <div class="text-muted small">Flat ✦ <?php echo htmlspecialchars($inv['flat_no']); ?> (<?php echo htmlspecialchars($inv['city']); ?>)</div>
                                        </td>
                                        <td class="small">
                                            <div><strong>Issued:</strong> <?php echo date('M d, Y', strtotime($inv['issued_date'])); ?></div>
                                            <div class="text-muted"><strong>Due:</strong> <?php echo date('M d, Y', strtotime($inv['due_date'])); ?></div>
                                        </td>
                                        <td class="fw-bold text-dark fs-6">₹<?php echo number_format($inv['grand_total'], 2); ?></td>
                                        <td>
                                            <?php if ($inv['status'] === 'Paid'): ?>
                                                <span class="badge bg-success text-white px-3 py-1.5 rounded-pill fw-bold" style="background-color: #15803d !important; color: #ffffff !important; font-size: 0.75rem;"><i class="fa-solid fa-check me-1"></i> Paid</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark px-3 py-1.5 rounded-pill fw-bold" style="background-color: #f59e0b !important; color: #0f172a !important; font-size: 0.75rem;"><i class="fa-solid fa-clock me-1"></i> Unpaid</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="invoice.php?id=<?php echo $inv['id']; ?>" class="btn btn-outline-primary btn-sm px-3 fw-semibold" target="_blank">
                                                <i class="fa-solid fa-print me-1"></i> View / Print PDF
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-file-invoice-dollar fa-2x mb-2 text-secondary"></i>
                                        <div>No invoices created yet. Click "Generate New Invoice" to issue one.</div>
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

<!-- Generate Invoice Modal -->
<div class="modal fade" id="generateInvoiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-receipt me-2 text-warning"></i>Generate Tax Invoice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="generate_invoice">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Customer Booking</label>
                        <select name="booking_id" class="form-select" required>
                            <?php foreach ($bookings as $b): ?>
                                <option value="<?php echo $b['id']; ?>">
                                    Booking #<?php echo $b['id']; ?> - <?php echo htmlspecialchars($b['customer_name']); ?> (<?php echo htmlspecialchars($b['block']); ?> Unit <?php echo $b['flat_no']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Subtotal Booking Fee (₹)</label>
                        <input type="number" step="0.01" name="subtotal" class="form-control" placeholder="145000.00" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">GST / Tax Rate (%)</label>
                            <input type="number" step="0.1" name="tax_rate" class="form-control" value="18">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Discount (₹)</label>
                            <input type="number" step="0.01" name="discount" class="form-control" value="0.00">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Payment Due Period</label>
                        <select name="due_days" class="form-select">
                            <option value="15">15 Days</option>
                            <option value="30" selected>30 Days</option>
                            <option value="45">45 Days</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning text-dark fw-bold" style="background-color: var(--orange-brand); border:none;">Generate & Issue Invoice</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
