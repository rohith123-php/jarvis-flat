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

// Handle Add Notice
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);

    if (empty($title) || empty($description)) {
        $error = 'Title and description are required.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO notices (title, description) VALUES (:title, :description)");
            $stmt->execute([
                'title' => $title,
                'description' => $description
            ]);
            $message = 'Notice posted successfully!';
        } catch (PDOException $e) {
            $error = 'Error posting notice: ' . $e->getMessage();
        }
    }
}

// Handle Edit Notice
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = intval($_POST['id']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);

    if (empty($title) || empty($description)) {
        $error = 'Title and description are required.';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE notices SET title = :title, description = :description WHERE id = :id");
            $stmt->execute([
                'id' => $id,
                'title' => $title,
                'description' => $description
            ]);
            $message = 'Notice updated successfully!';
        } catch (PDOException $e) {
            $error = 'Error updating notice: ' . $e->getMessage();
        }
    }
}

// Handle Delete Notice
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    try {
        $stmt = $pdo->prepare("DELETE FROM notices WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $message = 'Notice deleted successfully!';
    } catch (PDOException $e) {
        $error = 'Error deleting notice: ' . $e->getMessage();
    }
}

// Fetch all notices
try {
    $stmt = $pdo->query("SELECT * FROM notices ORDER BY id DESC");
    $notices = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Error fetching notices: ' . $e->getMessage();
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
                    <h2 class="fw-bold">Community Notices</h2>
                    <p class="text-muted mb-0">Post announcements and bulletins to the resident notice board.</p>
                </div>
                <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addNoticeModal">
                    <i class="fa-solid fa-plus me-2"></i>Post Notice
                </button>
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

            <!-- Notices Grid -->
            <div class="row g-3">
                <?php if (count($notices) > 0): ?>
                    <?php foreach ($notices as $notice): ?>
                        <div class="col-md-6">
                            <div class="card card-premium p-4 h-100 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="fw-bold text-indigo mb-0"><?php echo htmlspecialchars($notice['title']); ?></h5>
                                        <small class="text-muted"><i class="fa-regular fa-clock me-1"></i><?php echo date('M d, Y', strtotime($notice['date'])); ?></small>
                                    </div>
                                    <p class="text-muted small mb-3">
                                        <?php echo nl2br(htmlspecialchars($notice['description'])); ?>
                                    </p>
                                </div>
                                <div class="border-top pt-3 text-end">
                                    <button class="btn btn-sm btn-outline-primary me-2 edit-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editNoticeModal"
                                            data-id="<?php echo $notice['id']; ?>"
                                            data-title="<?php echo htmlspecialchars($notice['title']); ?>"
                                            data-description="<?php echo htmlspecialchars($notice['description']); ?>">
                                        <i class="fa-solid fa-pen-to-square"></i> Edit
                                    </button>
                                    <a href="notices.php?delete=<?php echo $notice['id']; ?>"
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Are you sure you want to delete this notice?');">
                                        <i class="fa-solid fa-trash"></i> Delete
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="card card-premium p-5 text-center text-muted">
                            <i class="fa-solid fa-bullhorn fa-3x text-secondary mb-3"></i>
                            <h5 class="fw-bold text-dark">No Notices Posted</h5>
                            <p class="mb-0">Broadcast updates to all residents by clicking the 'Post Notice' button above.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Notice Modal -->
<div class="modal fade" id="addNoticeModal" tabindex="-1" aria-labelledby="addNoticeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 bg-dark text-white p-4 rounded-top-4">
                <h5 class="modal-title fw-bold" id="addNoticeModalLabel"><i class="fa-solid fa-bullhorn me-2"></i>Post Notice</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="notices.php" method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="title" class="form-label fw-semibold">Notice Title</label>
                            <input type="text" class="form-control form-control-custom" id="title" name="title" required placeholder="e.g. Schedule for Pool Maintenance">
                        </div>
                        <div class="col-12">
                            <label for="description" class="form-label fw-semibold">Description</label>
                            <textarea class="form-control form-control-custom" id="description" name="description" rows="5" required placeholder="Describe the notice details here..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom">Post Notice</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Notice Modal -->
<div class="modal fade" id="editNoticeModal" tabindex="-1" aria-labelledby="editNoticeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 bg-dark text-white p-4 rounded-top-4">
                <h5 class="modal-title fw-bold" id="editNoticeModalLabel"><i class="fa-solid fa-pen-to-square me-2"></i>Edit Notice</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="notices.php" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="edit_title" class="form-label fw-semibold">Notice Title</label>
                            <input type="text" class="form-control form-control-custom" id="edit_title" name="title" required>
                        </div>
                        <div class="col-12">
                            <label for="edit_description" class="form-label fw-semibold">Description</label>
                            <textarea class="form-control form-control-custom" id="edit_description" name="description" rows="5" required></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editBtns = document.querySelectorAll('.edit-btn');
    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_id').value = this.dataset.id;
            document.getElementById('edit_title').value = this.dataset.title;
            document.getElementById('edit_description').value = this.dataset.description;
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
