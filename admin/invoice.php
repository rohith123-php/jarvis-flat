<?php
require_once '../includes/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$stmt = $pdo->prepare("
    SELECT i.*, r.name as customer_name, r.email as customer_email, r.phone as customer_phone,
           f.flat_no, f.block, f.city, f.bhk, f.price, b.booking_date, b.payment_status as booking_payment_status
    FROM invoices i
    JOIN residents r ON i.resident_id = r.id
    JOIN bookings b ON i.booking_id = b.id
    JOIN flats f ON b.flat_id = f.id
    WHERE i.id = :id
");
$stmt->execute(['id' => $id]);
$inv = $stmt->fetch();

if (!$inv) {
    die("Invoice not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tax Invoice - <?php echo htmlspecialchars($inv['invoice_no']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8fafc; font-family: 'Plus Jakarta Sans', sans-serif; color: #1e293b; }
        .invoice-card { background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 10px 30px rgba(0,0,0,0.06); padding: 40px; max-width: 850px; margin: 40px auto; position: relative; }
        .brand-header { border-bottom: 2px solid #f1f5f9; padding-bottom: 24px; margin-bottom: 30px; }
        .paid-stamp { position: absolute; top: 120px; right: 50px; border: 3px solid #16a34a; color: #16a34a; text-transform: uppercase; font-weight: 900; font-size: 1.6rem; padding: 4px 18px; border-radius: 8px; transform: rotate(-12deg); opacity: 0.85; letter-spacing: 2px; }
        @media print {
            .no-print { display: none !important; }
            .invoice-card { border: none !important; box-shadow: none !important; margin: 0 !important; max-width: 100% !important; padding: 20px !important; }
            body { background: #ffffff; }
        }
    </style>
</head>
<body>

    <!-- No-Print Action Toolbar -->
    <div class="no-print text-center py-3 bg-dark text-white border-bottom">
        <button onclick="window.print()" class="btn btn-warning text-dark fw-bold px-4 me-2">
            <i class="fa-solid fa-print me-1.5"></i> Print / Save as PDF
        </button>
        <button onclick="window.close()" class="btn btn-outline-light px-3">
            <i class="fa-solid fa-xmark me-1"></i> Close Window
        </button>
    </div>

    <div class="invoice-card">
        <!-- Paid Stamp -->
        <?php if ($inv['status'] === 'Paid'): ?>
            <div class="paid-stamp">OFFICIALLY PAID</div>
        <?php endif; ?>

        <!-- Brand Header -->
        <div class="brand-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <div class="position-relative d-inline-flex align-items-center justify-content-center me-3" style="width: 46px; height: 46px; background: linear-gradient(135deg, #f2a122 0%, #d97706 100%); border-radius: 50%; box-shadow: 0 4px 12px rgba(242, 161, 34, 0.4);">
                    <i class="fa-solid fa-crown text-white fs-4"></i>
                </div>
                <div>
                    <h3 class="fw-extrabold mb-0" style="color:#103178; font-family:'Playfair Display', serif; letter-spacing:1px;">JARVIS<span style="color:#f2a122;">✦</span></h3>
                    <div class="text-uppercase small fw-bold" style="font-size:0.6rem; letter-spacing:3px; color:#f2a122;">BUILDING ASPIRATIONS</div>
                </div>
            </div>
            <div class="text-end">
                <h4 class="fw-bold text-dark mb-1">OFFICIAL TAX INVOICE</h4>
                <div class="text-muted small"><strong>Invoice No:</strong> <?php echo htmlspecialchars($inv['invoice_no']); ?></div>
                <div class="text-muted small"><strong>Date:</strong> <?php echo date('F d, Y', strtotime($inv['issued_date'])); ?></div>
            </div>
        </div>

        <!-- Bill To & Customer Info -->
        <div class="row mb-4">
            <div class="col-6">
                <h6 class="fw-bold text-muted text-uppercase mb-2" style="font-size: 0.75rem; letter-spacing: 1px;">Customer Information (Billed To)</h6>
                <h5 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($inv['customer_name']); ?></h5>
                <div class="text-muted small"><?php echo htmlspecialchars($inv['customer_email']); ?></div>
                <div class="text-muted small"><?php echo htmlspecialchars($inv['customer_phone']); ?></div>
            </div>
            <div class="col-6 text-end">
                <h6 class="fw-bold text-muted text-uppercase mb-2" style="font-size: 0.75rem; letter-spacing: 1px;">Developer & Property Details</h6>
                <div class="fw-bold text-dark"><?php echo htmlspecialchars($inv['block']); ?></div>
                <div class="text-muted small">Unit Number: ✦ <?php echo htmlspecialchars($inv['flat_no']); ?></div>
                <div class="text-muted small">Location: <?php echo htmlspecialchars($inv['city']); ?> Precinct</div>
                <div class="text-muted small">Due Date: <?php echo date('F d, Y', strtotime($inv['due_date'])); ?></div>
            </div>
        </div>

        <!-- Itemized Financial Breakdown Table -->
        <table class="table table-bordered align-middle mb-4">
            <thead class="table-light">
                <tr>
                    <th>Item Description</th>
                    <th>Category</th>
                    <th class="text-end">Amount (₹)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div class="fw-bold"><?php echo htmlspecialchars($inv['block']); ?> – Flat ✦ <?php echo htmlspecialchars($inv['flat_no']); ?></div>
                        <div class="text-muted small"><?php echo $inv['bhk']; ?> BHK Luxury Suite Acquisition Deposit</div>
                    </td>
                    <td><span class="badge bg-light text-dark border">Property Booking</span></td>
                    <td class="text-end fw-bold">₹<?php echo number_format($inv['subtotal'], 2); ?></td>
                </tr>
            </tbody>
        </table>

        <!-- Summary Totals -->
        <div class="row justify-content-end mb-4">
            <div class="col-md-5">
                <div class="p-3 bg-light rounded-3 border">
                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted">Subtotal Fee:</span>
                        <span class="fw-semibold">₹<?php echo number_format($inv['subtotal'], 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted">GST / State Tax (18%):</span>
                        <span class="fw-semibold">+ ₹<?php echo number_format($inv['tax_amount'], 2); ?></span>
                    </div>
                    <?php if ($inv['discount_amount'] > 0): ?>
                        <div class="d-flex justify-content-between mb-2 small text-success">
                            <span>Pre-Launch Discount:</span>
                            <span class="fw-semibold">- ₹<?php echo number_format($inv['discount_amount'], 2); ?></span>
                        </div>
                    <?php endif; ?>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between fs-5 fw-bold text-dark">
                        <span>Grand Total:</span>
                        <span style="color:#103178;">₹<?php echo number_format($inv['grand_total'], 2); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Terms & Verification Footer -->
        <div class="pt-4 border-top text-center text-muted small">
            <div class="fw-bold text-dark mb-1">Thank you for choosing Jarvis Real Estate!</div>
            <div>This is a computer-generated tax invoice verified by Jarvis Real Estate Management. No signature required.</div>
            <div class="mt-2 text-secondary">Jarvis Residences Corporate Office | Velachery Main Rd, Tambaram, Chennai | Contact: +91 80562 10606</div>
        </div>
    </div>

</body>
</html>
