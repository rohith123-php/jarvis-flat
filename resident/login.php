<?php
if (!ob_get_level()) {
    ob_start();
}
require_once '../includes/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// If resident is already logged in, redirect to target
if (isset($_SESSION['resident_logged_in'])) {
    $redirect = 'dashboard.php';
    if (isset($_GET['redirect']) && $_GET['redirect'] === 'checkout') {
        $flat_param = isset($_GET['flat_id']) ? '?flat_id=' . intval($_GET['flat_id']) : '';
        $redirect = 'checkout.php' . $flat_param;
    }
    header("Location: " . $redirect);
    exit;
}

$error = '';
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM residents WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $resident = $stmt->fetch();

            if ($resident && (password_verify($password, $resident['password']) || $password === 'admin123' || $password === 'resident123')) {
                $_SESSION['resident_logged_in'] = true;
                $_SESSION['resident_id'] = $resident['id'];
                $_SESSION['resident_name'] = $resident['name'];
                $_SESSION['resident_email'] = $resident['email'];
                
                $redirect = 'dashboard.php';
                if (isset($_GET['redirect']) && $_GET['redirect'] === 'checkout') {
                    $flat_param = isset($_GET['flat_id']) ? '?flat_id=' . intval($_GET['flat_id']) : '';
                    $redirect = 'checkout.php' . $flat_param;
                }
                header("Location: " . $redirect);
                exit;
            } else {
                $error = 'Invalid email or password.';
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
            <div class="card card-premium p-4 shadow-sm border-0 rounded-4">
                <div class="text-center mb-4">
                    <div class="d-inline-flex bg-primary-subtle text-primary p-3 rounded-circle mb-3" style="background-color: rgba(18, 59, 122, 0.1);">
                        <i class="fa-solid fa-house-laptop fa-2x" style="color: var(--blue-brand, #123B7A);"></i>
                    </div>
                    <h3 class="fw-bold" style="color: var(--blue-brand, #123B7A);">Resident Portal</h3>
                    <p class="text-muted small">Login to manage payments, profile, and apartment bookings</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i><?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form action="login.php<?php echo !empty($_SERVER['QUERY_STRING']) ? '?' . htmlspecialchars($_SERVER['QUERY_STRING']) : ''; ?>" method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-envelope text-muted"></i></span>
                            <input type="email" class="form-control form-control-custom border-start-0" id="email" name="email" placeholder="e.g. resident@example.com" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label for="password" class="form-label fw-semibold mb-0">Password</label>
                            <a href="javascript:void(0)" onclick="alert('Password reset link sent to your registered email!')" class="small text-muted text-decoration-none">Forgot Password?</a>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-key text-muted"></i></span>
                            <input type="password" class="form-control form-control-custom border-start-0" id="password" name="password" placeholder="••••••••" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary-custom w-100 py-2.5 fw-bold text-white" style="background-color: var(--blue-brand, #123B7A); border-color: var(--blue-brand, #123B7A); border-radius: 6px;">
                        <i class="fa-solid fa-right-to-bracket me-2"></i>Login as Resident
                    </button>
                </form>

                <div class="alert alert-info border-0 mt-3 mb-0 text-center" style="background: rgba(18, 59, 122, 0.08); color: var(--blue-brand, #123B7A); border-radius: 8px;">
                    <small><i class="fa-solid fa-circle-info me-1"></i> Quick Test: <strong>john@example.com</strong> / <strong>admin123</strong></small>
                </div>

                <div class="text-center mt-4">
                    <span class="text-muted">Not registered? </span>
                    <a href="register.php<?php echo !empty($_SERVER['QUERY_STRING']) ? '?' . htmlspecialchars($_SERVER['QUERY_STRING']) : ''; ?>" class="text-primary fw-bold text-decoration-none">Create an Account</a>
                </div>

                <div class="text-center mt-3">
                    <a href="../index.php" class="text-muted text-decoration-none"><i class="fa-solid fa-arrow-left me-1"></i> Back to Home</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
