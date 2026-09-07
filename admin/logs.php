<?php
require_once '../includes/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

// Clear Logs Action
if (isset($_POST['action']) && $_POST['action'] === 'clear_logs') {
    $pdo->query("TRUNCATE TABLE activity_logs");
    $message = "Audit Activity Logs cleared successfully!";
}

$logs = $pdo->query("SELECT * FROM activity_logs ORDER BY id DESC LIMIT 100")->fetchAll();

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
                    <h2 class="fw-bold" style="color:var(--blue-brand);">Audit Activity Trail & Security Logs</h2>
                    <p class="text-muted mb-0">Monitor admin logins, data mutations, record updates, and system activity logs.</p>
                </div>
                <?php if (count($logs) > 0): ?>
                    <form method="POST" onsubmit="return confirm('Clear all activity logs?');">
                        <input type="hidden" name="action" value="clear_logs">
                        <button type="submit" class="btn btn-outline-danger btn-sm px-3 py-2 fw-semibold">
                            <i class="fa-solid fa-trash me-1"></i> Clear Logs
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <?php if (isset($message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-circle-check me-2"></i><?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Logs Table -->
            <div class="card border border-light-subtle rounded-3 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Timestamp</th>
                                <th>Admin User</th>
                                <th>Module</th>
                                <th>Action Taken</th>
                                <th>Details</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($logs) > 0): ?>
                                <?php foreach ($logs as $l): ?>
                                    <tr>
                                        <td class="small text-muted"><?php echo date('M d, Y H:i:s', strtotime($l['created_at'])); ?></td>
                                        <td>
                                            <span class="badge bg-dark text-white fw-semibold">
                                                <i class="fa-solid fa-user-shield me-1 text-warning"></i> <?php echo htmlspecialchars($l['admin_username']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border me-1"><?php echo htmlspecialchars($l['module']); ?></span>
                                        </td>
                                        <td class="fw-bold text-dark"><?php echo htmlspecialchars($l['action']); ?></td>
                                        <td class="small text-secondary"><?php echo htmlspecialchars($l['details']); ?></td>
                                        <td><code class="small text-muted"><?php echo htmlspecialchars($l['ip_address']); ?></code></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-clock-rotate-left fa-2x mb-2 text-secondary"></i>
                                        <div>No audit logs recorded yet. System activities will appear here in real-time.</div>
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

<?php require_once 'includes/footer.php'; ?>
