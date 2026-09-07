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

// Handle Submit Complaint
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);

    if (empty($title) || empty($description)) {
        $error = 'Title and description are required.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO complaints (resident_id, title, description, status) VALUES (:resident_id, :title, :description, 'Pending')");
            $stmt->execute([
                'resident_id' => $resident_id,
                'title' => $title,
                'description' => $description
            ]);
            $message = 'Complaint submitted successfully! Admin will review it shortly.';
        } catch (PDOException $e) {
            $error = 'Error submitting complaint: ' . $e->getMessage();
        }
    }
}

// Fetch resident's complaints
try {
    $stmt = $pdo->prepare("SELECT * FROM complaints WHERE resident_id = :resident_id ORDER BY id DESC");
    $stmt->execute(['resident_id' => $resident_id]);
    $complaints = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Error fetching complaints: ' . $e->getMessage();
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
            <a href="payment.php" class="sidebar-link"><i class="fa-solid fa-wallet"></i>Payment History</a>
            <a href="complaint.php" class="sidebar-link active"><i class="fa-solid fa-triangle-exclamation"></i>Complaints</a>
            <a href="../index.php" class="sidebar-link"><i class="fa-solid fa-search"></i>Search Properties</a>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold">Submit Complaint</h2>
                    <p class="text-muted mb-0">File a complaint to notify society administration of any concerns.</p>
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

            <div class="row g-4">
                <!-- Submit Complaint Form -->
                <div class="col-lg-5">
                    <div class="card card-premium p-4">
                        <h5 class="fw-bold mb-3"><i class="fa-solid fa-pen-clip me-2 text-indigo"></i>New Complaint Form</h5>
                        <form action="complaint.php" method="POST">
                            <input type="hidden" name="action" value="submit">
                            <div class="mb-3">
                                <label for="title" class="form-label fw-semibold">Complaint Subject/Title</label>
                                <input type="text" class="form-control form-control-custom" id="title" name="title" required placeholder="e.g. Water leakage in bathroom">
                            </div>
                            <div class="mb-4">
                                <label for="description" class="form-label fw-semibold">Detailed Description</label>
                                <textarea class="form-control form-control-custom" id="description" name="description" rows="5" required placeholder="Describe the issue in detail..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary-custom w-100 py-2"><i class="fa-solid fa-paper-plane me-2"></i>Submit Complaint</button>
                        </form>
                    </div>
                </div>

                <!-- Complaints History List -->
                <div class="col-lg-7">
                    <div class="card card-premium p-4">
                        <h5 class="fw-bold mb-3"><i class="fa-solid fa-clock-rotate-left me-2 text-indigo"></i>My Submitted Complaints</h5>
                        <div class="list-group list-group-flush">
                            <?php if (count($complaints) > 0): ?>
                                <?php foreach ($complaints as $comp): ?>
                                    <div class="list-group-item px-0 py-3 bg-transparent border-bottom">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <h6 class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($comp['title']); ?></h6>
                                            <span class="badge badge-status <?php echo $comp['status'] === 'Resolved' ? 'badge-resolved' : 'badge-pending'; ?>">
                                                <?php echo $comp['status']; ?>
                                            </span>
                                        </div>
                                        <p class="text-muted mb-0 small"><?php echo nl2br(htmlspecialchars($comp['description'])); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center text-muted py-4">No complaint history records.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
