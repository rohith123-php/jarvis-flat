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

$message = '';
$error = '';

// Handle Delete Inquiry
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    try {
        $stmt = $pdo->prepare("DELETE FROM inquiries WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $message = 'Inquiry record removed successfully!';
    } catch (PDOException $e) {
        $error = 'Error deleting inquiry: ' . $e->getMessage();
    }
}

// Fetch all inquiries
try {
    $stmt = $pdo->query("SELECT * FROM inquiries ORDER BY id DESC");
    $inquiries = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Error fetching inquiries: ' . $e->getMessage();
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
                    <h2 class="fw-bold" style="color:var(--blue-brand);">Customer Inquiries Directory</h2>
                    <p class="text-muted mb-0 d-print-none">Track interest inquiries generated from the homepage widgets.</p>
                </div>
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

            <!-- Inquiries Table -->
            <div class="card card-premium p-4">
                <div class="table-responsive">
                    <table class="table table-premium align-middle">
                        <thead>
                            <tr>
                                <th>Inquiry ID</th>
                                <th>Client Name</th>
                                <th>Phone Number</th>
                                <th>Preferred Location</th>
                                <th>Submitted At</th>
                                <th class="text-end d-print-none">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($inquiries) > 0): ?>
                                <?php foreach ($inquiries as $inq): ?>
                                    <tr>
                                        <td class="fw-bold text-dark">#<?php echo $inq['id']; ?></td>
                                        <td class="fw-bold text-dark"><?php echo htmlspecialchars($inq['name']); ?></td>
                                        <td class="fw-semibold text-dark"><?php echo htmlspecialchars($inq['phone']); ?></td>
                                        <td>
                                            <span class="badge bg-primary px-3 py-2 text-uppercase" style="background-color:var(--blue-brand) !important; font-size:0.65rem;">
                                                <i class="fa-solid fa-location-dot me-1"></i> <?php echo htmlspecialchars($inq['city']); ?>
                                            </span>
                                        </td>
                                        <td class="text-muted"><?php echo date('M d, Y h:i A', strtotime($inq['created_at'])); ?></td>
                                        <td class="text-end d-print-none">
                                            <a href="inquiries.php?delete=<?php echo $inq['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger" 
                                               onclick="return confirm('Are you sure you want to delete this customer inquiry?');"
                                               style="border-radius:4px;">
                                                <i class="fa-solid fa-trash-can"></i> Delete
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No client inquiries registered in database.</td>
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
