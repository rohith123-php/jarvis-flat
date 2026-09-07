<?php
if (!ob_get_level()) {
    ob_start();
}
require_once '../includes/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// If resident is already logged in, redirect to dashboard or checkout
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
$success = '';

// Fetch all available flats to optionally select from during registration
$vacant_flats = [];
try {
    $flats_stmt = $pdo->query("SELECT * FROM flats WHERE status = 'Available' OR status = 'Vacant' ORDER BY block ASC, flat_no ASC");
    $vacant_flats = $flats_stmt->fetchAll();
} catch (PDOException $e) {
    // Ignore
}

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $flat_id = !empty($_POST['flat_id']) ? intval($_POST['flat_id']) : null;
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (empty($name) || empty($phone) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields (Name, Phone, Email, Password).';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        try {
            // Check if email already exists
            $check_email = $pdo->prepare("SELECT id FROM residents WHERE email = :email");
            $check_email->execute(['email' => $email]);
            $existing_user = $check_email->fetch();
            
            if ($existing_user) {
                $error = 'This email address is already registered. Please log in directly.';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new resident into residents table
                $stmt = $pdo->prepare("INSERT INTO residents (name, phone, email, password) VALUES (:name, :phone, :email, :password)");
                $stmt->execute([
                    'name' => $name,
                    'phone' => $phone,
                    'email' => $email,
                    'password' => $hashed_password
                ]);
                $new_resident_id = $pdo->lastInsertId();

                // Auto-login newly registered resident
                $_SESSION['resident_logged_in'] = true;
                $_SESSION['resident_id'] = $new_resident_id;
                $_SESSION['resident_name'] = $name;
                $_SESSION['resident_email'] = $email;

                // Determine redirect target
                $target_redirect = 'dashboard.php';
                if (isset($_GET['redirect']) && $_GET['redirect'] === 'checkout') {
                    $flat_param = isset($_GET['flat_id']) ? '?flat_id=' . intval($_GET['flat_id']) : ($flat_id ? '?flat_id=' . $flat_id : '');
                    $target_redirect = 'checkout.php' . $flat_param;
                } elseif ($flat_id) {
                    $target_redirect = 'checkout.php?flat_id=' . $flat_id;
                }

                header("Location: " . $target_redirect);
                exit;
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
        <div class="col-md-6">
            <div class="card card-premium p-4 shadow-sm border-0 rounded-4">
                <div class="text-center mb-4">
                    <div class="d-inline-flex bg-primary-subtle text-primary p-3 rounded-circle mb-3" style="background-color: rgba(18, 59, 122, 0.1);">
                        <i class="fa-solid fa-user-plus fa-2x" style="color: var(--blue-brand, #123B7A);"></i>
                    </div>
                    <h3 class="fw-bold" style="color: var(--blue-brand, #123B7A);">Resident Registration</h3>
                    <p class="text-muted small">Create an account to join the community portal and book properties</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i><?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form action="register.php<?php echo !empty($_SERVER['QUERY_STRING']) ? '?' . htmlspecialchars($_SERVER['QUERY_STRING']) : ''; ?>" method="POST">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="name" class="form-label fw-semibold">Full Name *</label>
                            <input type="text" class="form-control form-control-custom" id="name" name="name" required placeholder="e.g. Praveen Kumar" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label fw-semibold">Phone Number *</label>
                            <input type="text" class="form-control form-control-custom" id="phone" name="phone" required placeholder="e.g. 9876543210" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label fw-semibold">Email Address *</label>
                            <input type="email" class="form-control form-control-custom" id="email" name="email" required placeholder="e.g. resident@example.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                        <div class="col-12">
                            <label for="flat_id" class="form-label fw-semibold">Interested Apartment / Flat <span class="text-muted fw-normal">(Optional)</span></label>
                            <select class="form-select form-control-custom" id="flat_id" name="flat_id">
                                <option value="">-- Select preferred flat (or choose later) --</option>
                                <?php foreach ($vacant_flats as $flat): ?>
                                    <option value="<?php echo $flat['id']; ?>">
                                        <?php echo htmlspecialchars($flat['block']); ?> - Unit <?php echo htmlspecialchars($flat['flat_no']); ?> (<?php echo htmlspecialchars($flat['city']); ?> - <?php echo $flat['bhk']; ?> BHK)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="password" class="form-label fw-semibold">Password *</label>
                            <input type="password" class="form-control form-control-custom" id="password" name="password" required placeholder="••••••••">
                        </div>
                        <div class="col-md-6">
                            <label for="confirm_password" class="form-label fw-semibold">Confirm Password *</label>
                            <input type="password" class="form-control form-control-custom" id="confirm_password" name="confirm_password" required placeholder="••••••••">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary-custom w-100 py-2.5 mt-4 fw-bold text-white" style="background-color: var(--blue-brand, #123B7A); border-color: var(--blue-brand, #123B7A); border-radius: 6px;">
                        <i class="fa-solid fa-user-check me-2"></i>Register Account
                    </button>
                </form>

                <div class="text-center mt-4">
                    <span class="text-muted">Already registered? </span>
                    <a href="login.php<?php echo !empty($_SERVER['QUERY_STRING']) ? '?' . htmlspecialchars($_SERVER['QUERY_STRING']) : ''; ?>" class="text-primary fw-bold text-decoration-none">Log in here</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
