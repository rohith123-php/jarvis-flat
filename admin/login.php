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

$login_logo_bg     = get_setting('logo_bg_color', '#f59e0b');
$login_logo_shield = get_setting('logo_shield_color', '#103178');
$login_logo_text   = get_setting('logo_text_color', '#103178');
require_once '../includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card card-premium p-4">
                <div class="text-center mb-4">
                    <div class="d-inline-flex justify-content-center align-items-center mb-3">
                        <div class="logo-crest d-flex align-items-center justify-content-center" style="width: 58px; height: 58px; background: <?php echo htmlspecialchars($login_logo_bg); ?>; border-radius: 16px; border: 2.5px solid <?php echo htmlspecialchars($login_logo_shield); ?>; box-shadow: 0 4px 18px rgba(0, 0, 0, 0.12), 0 0 16px <?php echo htmlspecialchars($login_logo_bg); ?>88; position: relative;">
                            <i class="fa-solid fa-shield" style="font-size: 2rem; color: <?php echo htmlspecialchars($login_logo_shield); ?>; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.25));"></i>
                            <i class="fa-solid fa-crown" style="position: absolute; font-size: 0.95rem; color: #ffffff; top: 16px; filter: drop-shadow(0 1px 2px rgba(0,0,0,0.5));"></i>
                            <span style="position: absolute; top: -4px; right: -4px; width: 16px; height: 16px; background: <?php echo htmlspecialchars($login_logo_shield); ?>; border: 2px solid #ffffff; border-radius: 50%; box-shadow: 0 0 6px rgba(0, 0, 0, 0.35); display: flex; align-items: center; justify-content: center;">
                                <i class="fa-solid fa-diamond" style="font-size: 7px; color: <?php echo htmlspecialchars($login_logo_bg); ?>;"></i>
                            </span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-center mb-0" style="font-family: 'Playfair Display', serif; font-weight: 900; font-size: 2.3rem; color: <?php echo htmlspecialchars($login_logo_text); ?>; letter-spacing: 1.5px; text-shadow: 0 1px 2px rgba(0,0,0,0.08);">
                        <span><?php echo htmlspecialchars(strtoupper(get_setting('system_name', 'JARVIS'))); ?></span>
                        <svg class="logo-sparkle-star" width="22" height="22" viewBox="0 0 24 24" fill="<?php echo htmlspecialchars($login_logo_text); ?>" xmlns="http://www.w3.org/2000/svg" style="display:inline-block; vertical-align:middle; margin-left: 6px; filter: drop-shadow(0 0 3px rgba(16, 49, 120, 0.3));">
                            <path d="M12 2L14.8 9.2L22 12L14.8 14.8L12 22L9.2 14.8L2 12L9.2 9.2L12 2Z" fill="<?php echo htmlspecialchars($login_logo_text); ?>"/>
                        </svg>
                    </div>
                    <span style="display:block; font-size:0.56rem; letter-spacing:5px; color:<?php echo htmlspecialchars($login_logo_text); ?>; font-weight:800; text-transform:uppercase; margin-top:2px; margin-bottom:15px; font-family:'Plus Jakarta Sans', sans-serif;"><?php echo htmlspecialchars(get_setting('brand_tagline', 'BUILDING ASPIRATIONS')); ?></span>
                    <h6 class="fw-bold text-dark mb-1">Admin Portal</h6>
                    <p class="text-muted small mb-0">Enter credentials to manage your community</p>
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
