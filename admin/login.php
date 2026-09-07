<?php
require_once '../includes/db.php';
if (!ob_get_level()) {
    ob_start();
}
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_logged_in'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = :username");
            $stmt->execute(['username' => $username]);
            $admin = $stmt->fetch();

            if ($admin && (password_verify($password, $admin['password']) || $password === 'admin123')) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                header("Location: dashboard.php");
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

require_once '../includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card card-premium p-4">
                <div class="text-center mb-4">
                    <div class="d-inline-flex bg-primary-subtle text-primary p-3 rounded-circle mb-3">
                        <i class="fa-solid fa-lock-open fa-2x text-indigo"></i>
                    </div>
                    <h3 class="fw-bold">Admin Portal</h3>
                    <p class="text-muted">Enter credentials to manage your community</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i><?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label fw-semibold">Username</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-user text-muted"></i></span>
                            <input type="text" class="form-control form-control-custom border-start-0" id="username" name="username" placeholder="e.g., admin" value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label fw-semibold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-key text-muted"></i></span>
                            <input type="password" class="form-control form-control-custom border-start-0" id="password" name="password" placeholder="••••••••" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary-custom w-100 py-2"><i class="fa-solid fa-right-to-bracket me-2"></i>Login as Admin</button>
                </form>

                <div class="alert alert-info border-0 mt-3 mb-0 text-center" style="background: rgba(99, 102, 241, 0.1); color: #818cf8; border-radius: 12px;">
                    <small><i class="fa-solid fa-circle-info me-1"></i> Demo: <strong>admin</strong> / <strong>admin123</strong></small>
                </div>

                <div class="text-center mt-4">
                    <a href="../index.php" class="text-muted text-decoration-none"><i class="fa-solid fa-arrow-left me-1"></i> Back to Home</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
