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

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    if (empty($name) || empty($phone) || empty($email)) {
        $error = 'Name, phone, and email are required fields.';
    } else {
        try {
            // Check if email already exists for another resident
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM residents WHERE email = :email AND id != :id");
            $stmt_check->execute(['email' => $email, 'id' => $resident_id]);
            
            if ($stmt_check->fetchColumn() > 0) {
                $error = 'The email address is already in use by another resident.';
            } else {
                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE residents SET name = :name, phone = :phone, email = :email, password = :password WHERE id = :id");
                    $stmt->execute([
                        'name' => $name,
                        'phone' => $phone,
                        'email' => $email,
                        'password' => $hashed_password,
                        'id' => $resident_id
                    ]);
                } else {
                    $stmt = $pdo->prepare("UPDATE residents SET name = :name, phone = :phone, email = :email WHERE id = :id");
                    $stmt->execute([
                        'name' => $name,
                        'phone' => $phone,
                        'email' => $email,
                        'id' => $resident_id
                    ]);
                }
                
                // Update session values
                $_SESSION['resident_name'] = $name;
                $_SESSION['resident_email'] = $email;
                
                $message = 'Profile details updated successfully!';
            }
        } catch (PDOException $e) {
            $error = 'Error updating profile: ' . $e->getMessage();
        }
    }
}

// Fetch current resident details
try {
    $stmt = $pdo->prepare("SELECT r.* FROM residents r WHERE r.id = :id");
    $stmt->execute(['id' => $resident_id]);
    $res = $stmt->fetch();
} catch (PDOException $e) {
    $error = 'Error fetching profile: ' . $e->getMessage();
}
?>

<div class="container-fluid px-4">
    <div class="row">
        <!-- Sidebar Navigation -->
        <div class="col-md-3 col-lg-2 sidebar rounded-4 shadow-sm mb-4 mb-md-0">
            <h5 class="px-3 mb-4 fw-bold" style="color:var(--blue-brand);"><i class="fa-solid fa-gauge me-2"></i>Navigation</h5>
            <a href="dashboard.php" class="sidebar-link"><i class="fa-solid fa-house-user"></i>Dashboard</a>
            <a href="profile.php" class="sidebar-link active"><i class="fa-solid fa-user-gear"></i>My Profile</a>
            <a href="dashboard.php#bookings-section" class="sidebar-link"><i class="fa-solid fa-hotel"></i>Booked Flats</a>
            <a href="payment.php" class="sidebar-link"><i class="fa-solid fa-wallet"></i>Payment History</a>
            <a href="complaint.php" class="sidebar-link"><i class="fa-solid fa-triangle-exclamation"></i>Complaints</a>
            <a href="../index.php" class="sidebar-link"><i class="fa-solid fa-search"></i>Search Properties</a>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold">My Profile Settings</h2>
                    <p class="text-muted mb-0">Manage your personal information and change your account password.</p>
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
                <!-- Profile Edit Form -->
                <div class="col-lg-7">
                    <div class="card card-premium p-4">
                        <h5 class="fw-bold mb-3"><i class="fa-solid fa-user-pen me-2 text-indigo"></i>Edit Profile Information</h5>
                        <form action="profile.php" method="POST">
                            <input type="hidden" name="action" value="update">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="name" class="form-label fw-semibold">Full Name</label>
                                    <input type="text" class="form-control form-control-custom" id="name" name="name" value="<?php echo htmlspecialchars($res['name']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="phone" class="form-label fw-semibold">Phone Number</label>
                                    <input type="text" class="form-control form-control-custom" id="phone" name="phone" value="<?php echo htmlspecialchars($res['phone']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label fw-semibold">Email Address</label>
                                    <input type="email" class="form-control form-control-custom" id="email" name="email" value="<?php echo htmlspecialchars($res['email']); ?>" required>
                                </div>
                                <div class="col-12">
                                    <label for="password" class="form-label fw-semibold">New Password (Leave blank to keep current password)</label>
                                    <input type="password" class="form-control form-control-custom" id="password" name="password" placeholder="••••••••">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary-custom w-100 py-2 mt-4"><i class="fa-solid fa-floppy-disk me-2"></i>Save Changes</button>
                        </form>
                    </div>
                </div>

                <!-- Flat and Profile Summary Sidebar -->
                <div class="col-lg-5">
                    <div class="card card-premium p-4 text-center">
                        <div class="d-inline-flex bg-primary-subtle text-primary p-3 rounded-circle mb-3 mx-auto" style="background-color: var(--accent-light);">
                            <i class="fa-solid fa-address-card fa-3x text-indigo"></i>
                        </div>
                        <h4 class="fw-bold mb-1"><?php echo htmlspecialchars($res['name']); ?></h4>
                        <p class="text-muted mb-4"><?php echo htmlspecialchars($res['email']); ?></p>

                        <div class="border-top pt-3 text-start">
                            <div class="row g-2">
                                <div class="col-6">
                                    <small class="text-muted">Account Status</small>
                                    <div class="fw-bold">Active Member</div>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Phone Contact</small>
                                    <div class="fw-semibold text-dark"><?php echo htmlspecialchars($res['phone']); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
