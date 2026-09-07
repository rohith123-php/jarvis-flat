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

// Handle Resolve Complaint
if (isset($_GET['resolve'])) {
    $id = intval($_GET['resolve']);
    try {
        $stmt = $pdo->prepare("UPDATE complaints SET status = 'Resolved' WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $message = 'Complaint marked as Resolved!';
    } catch (PDOException $e) {
        $error = 'Error resolving complaint: ' . $e->getMessage();
    }
}

// Handle Delete Complaint
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    try {
        $stmt = $pdo->prepare("DELETE FROM complaints WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $message = 'Complaint deleted successfully!';
    } catch (PDOException $e) {
        $error = 'Error deleting complaint: ' . $e->getMessage();
    }
}

// Fetch complaints
try {
    $stmt = $pdo->query("
        SELECT c.*, r.name as resident_name, r.phone as resident_phone, COALESCE(f.flat_no, 'N/A') as flat_no, COALESCE(f.block, 'General') as block 
        FROM complaints c 
        JOIN residents r ON c.resident_id = r.id 
        LEFT JOIN bookings b ON b.resident_id = r.id 
        LEFT JOIN flats f ON b.flat_id = f.id 
        GROUP BY c.id
        ORDER BY c.status ASC, c.id DESC
    ");
    $complaints = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Error fetching complaints: ' . $e->getMessage();
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
                    <h2 class="fw-bold">Complaint Management</h2>
                    <p class="text-muted mb-0">Track and resolve issues submitted by community residents.</p>
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

            <!-- Complaints Layout -->
            <div class="row g-3">
                <?php if (count($complaints) > 0): ?>
                    <?php foreach ($complaints as $comp): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card card-premium p-4 h-100 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <span class="badge <?php echo $comp['status'] === 'Resolved' ? 'bg-success text-white' : 'bg-warning text-dark'; ?> px-3 py-1.5 rounded-pill fw-semibold" style="font-size: 0.75rem;">
                                            <i class="fa-solid <?php echo $comp['status'] === 'Resolved' ? 'fa-circle-check' : 'fa-clock'; ?> me-1"></i> <?php echo htmlspecialchars($comp['status']); ?>
                                        </span>
                                        <small class="text-muted">ID: #<?php echo $comp['id']; ?></small>
                                    </div>
                                    <h5 class="fw-bold text-indigo mb-2"><?php echo htmlspecialchars($comp['title']); ?></h5>
                                    <p class="text-muted small mb-3" style="min-height: 50px;">
                                        <?php echo htmlspecialchars($comp['description']); ?>
                                    </p>
                                </div>
                                <div class="border-top pt-3">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-light p-2 rounded-circle me-2">
                                            <i class="fa-solid fa-user text-muted small"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold small"><?php echo htmlspecialchars($comp['resident_name']); ?></div>
                                            <div class="text-muted small" style="font-size: 0.8rem;">
                                                Block <?php echo htmlspecialchars($comp['block']); ?> - Flat <?php echo htmlspecialchars($comp['flat_no']); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <?php if ($comp['status'] === 'Pending'): ?>
                                            <a href="complaints.php?resolve=<?php echo $comp['id']; ?>" class="btn btn-sm btn-success flex-grow-1">
                                                <i class="fa-solid fa-circle-check me-1"></i> Resolve
                                            </a>
                                        <?php endif; ?>
                                        <a href="complaints.php?delete=<?php echo $comp['id']; ?>" 
                                           class="btn btn-sm btn-outline-danger <?php echo $comp['status'] === 'Resolved' ? 'w-100' : ''; ?>"
                                           onclick="return confirm('Are you sure you want to delete this complaint record?');">
                                            <i class="fa-solid fa-trash"></i> Delete
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="card card-premium p-5 text-center text-muted">
                            <i class="fa-solid fa-circle-check fa-3x text-success mb-3"></i>
                            <h5 class="fw-bold text-dark">All Complaints Resolved!</h5>
                            <p class="mb-0">There are no pending complaints from residents at the moment.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
