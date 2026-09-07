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

// Handle Add Customer
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($name) || empty($phone) || empty($email) || empty($password)) {
        $error = 'Name, phone, email, and password are required fields.';
    } else {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO residents (name, phone, email, password) VALUES (:name, :phone, :email, :password)");
            $stmt->execute([
                'name' => $name,
                'phone' => $phone,
                'email' => $email,
                'password' => $hashed_password
            ]);
            $message = 'Customer added successfully!';
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = 'The email address is already in use.';
            } else {
                $error = 'Error adding customer: ' . $e->getMessage();
            }
        }
    }
}

// Handle Edit Customer
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = intval($_POST['id']);
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($name) || empty($phone) || empty($email)) {
        $error = 'Name, phone, and email are required fields.';
    } else {
        try {
            // Check if email unique
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM residents WHERE email = :email AND id != :id");
            $stmt_check->execute(['email' => $email, 'id' => $id]);
            if ($stmt_check->fetchColumn() > 0) {
                $error = 'The email address is already in use by another customer.';
            } else {
                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE residents SET name = :name, phone = :phone, email = :email, password = :password WHERE id = :id");
                    $stmt->execute([
                        'name' => $name,
                        'phone' => $phone,
                        'email' => $email,
                        'password' => $hashed_password,
                        'id' => $id
                    ]);
                } else {
                    $stmt = $pdo->prepare("UPDATE residents SET name = :name, phone = :phone, email = :email WHERE id = :id");
                    $stmt->execute([
                        'name' => $name,
                        'phone' => $phone,
                        'email' => $email,
                        'id' => $id
                    ]);
                }
                $message = 'Customer details updated successfully!';
            }
        } catch (PDOException $e) {
            $error = 'Error updating customer: ' . $e->getMessage();
        }
    }
}

// Handle Delete Customer
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    try {
        $stmt_del = $pdo->prepare("DELETE FROM residents WHERE id = :id");
        $stmt_del->execute(['id' => $id]);
        $message = 'Customer deleted successfully!';
    } catch (PDOException $e) {
        $error = 'Error deleting customer: ' . $e->getMessage();
    }
}

// Search Logic
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$residents = [];

try {
    if (!empty($search)) {
        $stmt_res = $pdo->prepare("
            SELECT * FROM residents 
            WHERE name LIKE :search1 OR phone LIKE :search2 OR email LIKE :search3
            ORDER BY id DESC
        ");
        $term = '%' . $search . '%';
        $stmt_res->execute([
            'search1' => $term,
            'search2' => $term,
            'search3' => $term
        ]);
    } else {
        $stmt_res = $pdo->query("SELECT * FROM residents ORDER BY id DESC");
    }
    $residents = $stmt_res->fetchAll();
} catch (PDOException $e) {
    $error = 'Error fetching customers: ' . $e->getMessage();
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
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <div>
                    <h2 class="fw-bold">Customers Directory</h2>
                    <p class="text-muted mb-0">Manage customer user accounts and order history.</p>
                </div>
                <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addResidentModal" style="border-radius:30px;">
                    <i class="fa-solid fa-user-plus me-2"></i>Add Customer
                </button>
            </div>

            <!-- Search Bar -->
            <div class="card card-premium p-3 mb-4">
                <form action="residents.php" method="GET" class="row g-2">
                    <div class="col-md-10">
                        <input type="text" class="form-control form-control-custom border-start-0" name="search" placeholder="Search by name, phone, or email..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary-custom w-100 py-2" style="border-radius:30px;"><i class="fa-solid fa-magnifying-glass me-2"></i>Search</button>
                    </div>
                </form>
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

            <!-- Customers Table -->
            <div class="card card-premium p-4">
                <div class="table-responsive">
                    <table class="table table-premium">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($residents) > 0): ?>
                                <?php foreach ($residents as $res): ?>
                                    <tr>
                                        <td class="fw-bold text-white"><?php echo htmlspecialchars($res['name']); ?></td>
                                        <td><?php echo htmlspecialchars($res['phone']); ?></td>
                                        <td class="fw-semibold text-white"><?php echo htmlspecialchars($res['email']); ?></td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-primary me-2 edit-btn" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editResidentModal"
                                                    data-id="<?php echo $res['id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($res['name']); ?>"
                                                    data-phone="<?php echo htmlspecialchars($res['phone']); ?>"
                                                    data-email="<?php echo htmlspecialchars($res['email']); ?>">
                                                <i class="fa-solid fa-user-pen"></i> Edit
                                            </button>
                                            <a href="residents.php?delete=<?php echo $res['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger" 
                                               onclick="return confirm('Are you sure you want to delete this customer?');">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">No customers found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Customer Modal -->
<div class="modal fade" id="addResidentModal" tabindex="-1" aria-labelledby="addResidentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 bg-dark text-white p-4">
                <h5 class="modal-title fw-bold" id="addResidentModalLabel"><i class="fa-solid fa-user-plus me-2 text-indigo"></i>Add Customer Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="residents.php" method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="name" class="form-label fw-semibold">Full Name</label>
                            <input type="text" class="form-control form-control-custom" id="name" name="name" required placeholder="e.g. John Doe">
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label fw-semibold">Phone Number</label>
                            <input type="text" class="form-control form-control-custom" id="phone" name="phone" required placeholder="e.g. 9876543210">
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label fw-semibold">Email Address</label>
                            <input type="email" class="form-control form-control-custom" id="email" name="email" required placeholder="john@example.com">
                        </div>
                        <div class="col-12">
                            <label for="password" class="form-label fw-semibold">Password</label>
                            <input type="password" class="form-control form-control-custom" id="password" name="password" required placeholder="Enter login password">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom">Add Customer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Customer Modal -->
<div class="modal fade" id="editResidentModal" tabindex="-1" aria-labelledby="editResidentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 bg-dark text-white p-4">
                <h5 class="modal-title fw-bold" id="editResidentModalLabel"><i class="fa-solid fa-user-pen me-2 text-indigo"></i>Edit Customer Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="residents.php" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="edit_name" class="form-label fw-semibold">Full Name</label>
                            <input type="text" class="form-control form-control-custom" id="edit_name" name="name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_phone" class="form-label fw-semibold">Phone Number</label>
                            <input type="text" class="form-control form-control-custom" id="edit_phone" name="phone" required>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_email" class="form-label fw-semibold">Email Address</label>
                            <input type="email" class="form-control form-control-custom" id="edit_email" name="email" required>
                        </div>
                        <div class="col-12">
                            <label for="edit_password" class="form-label fw-semibold">New Password (Leave blank to keep current)</label>
                            <input type="password" class="form-control form-control-custom" id="edit_password" name="password" placeholder="Enter new password">
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
            document.getElementById('edit_name').value = this.dataset.name;
            document.getElementById('edit_phone').value = this.dataset.phone;
            document.getElementById('edit_email').value = this.dataset.email;
            document.getElementById('edit_password').value = '';
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
