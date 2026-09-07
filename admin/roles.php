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

// Add New Admin Account
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_admin') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO admin (username, password) VALUES (:user, :pass)");
            $stmt->execute(['user' => $username, 'pass' => $hash]);

            // Log activity
            $stmt_log = $pdo->prepare("INSERT INTO activity_logs (admin_username, action, module, details) VALUES (:user, 'Created Admin User', 'Admin Roles', :det)");
            $stmt_log->execute([
                'user' => $_SESSION['admin_username'] ?? 'admin',
                'det' => "Created new admin user '{$username}'"
            ]);

            $message = "Admin user '{$username}' created successfully!";
        } catch (PDOException $e) {
            $error = "Error adding admin: " . $e->getMessage();
        }
    }
}

$admins = $pdo->query("SELECT * FROM admin ORDER BY id ASC")->fetchAll();

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
                    <h2 class="fw-bold" style="color:var(--blue-brand);">Admin Roles & User Permissions</h2>
                    <p class="text-muted mb-0">Manage system administrators, staff access privileges, and role permissions.</p>
                </div>
                <button class="btn btn-warning text-dark fw-bold px-3 py-2" data-bs-toggle="modal" data-bs-target="#addAdminModal" style="background-color: var(--orange-brand); border: none;">
                    <i class="fa-solid fa-user-plus me-1.5"></i> Create Admin User
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

            <!-- Admins Table -->
            <div class="card border border-light-subtle rounded-3 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Admin ID</th>
                                <th>Username</th>
                                <th>Assigned Role</th>
                                <th>Access Privilege Level</th>
                                <th>Account Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($admins as $a): ?>
                                <tr>
                                    <td><code>#ADM-<?php echo sprintf('%03d', $a['id']); ?></code></td>
                                    <td class="fw-bold text-dark"><i class="fa-solid fa-user-shield me-2 text-warning"></i><?php echo htmlspecialchars($a['username']); ?></td>
                                    <td>
                                        <span class="badge bg-primary bg-opacity-15 text-primary border border-primary border-opacity-20 px-2.5 py-1.5 rounded-pill">
                                            Super Administrator
                                        </span>
                                    </td>
                                    <td class="small text-muted">Full Access (Inventory, Financials, Users, Audit)</td>
                                    <td><span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-20 px-2.5 py-1.5 rounded-pill"><i class="fa-solid fa-check me-1"></i> Active</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Admin Modal -->
<div class="modal fade" id="addAdminModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-user-shield me-2 text-warning"></i>Create New Admin User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_admin">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Admin Username</label>
                        <input type="text" name="username" class="form-control" placeholder="manager_chennai" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning text-dark fw-bold" style="background-color: var(--orange-brand); border:none;">Create Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
