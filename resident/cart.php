<?php
require_once '../includes/db.php';
require_once '../includes/header.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$cart_items = [];
$total_price = 0.00;

// Handle Cart Actions (Update, Remove)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $fabric_id = intval($_POST['fabric_id']);
    
    if ($_POST['action'] === 'update_qty') {
        $qty = intval($_POST['qty']);
        if ($qty > 0) {
            $_SESSION['cart'][$fabric_id] = $qty;
        } else {
            unset($_SESSION['cart'][$fabric_id]);
        }
    } elseif ($_POST['action'] === 'remove') {
        unset($_SESSION['cart'][$fabric_id]);
    }
    header("Location: cart.php");
    exit;
}

// Fetch Cart details from DB (fabrics table)
if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0) {
    $fabric_ids = array_keys($_SESSION['cart']);
    if (count($fabric_ids) > 0) {
        $placeholders = implode(',', array_fill(0, count($fabric_ids), '?'));
        try {
            $stmt = $pdo->prepare("SELECT * FROM fabrics WHERE id IN ($placeholders)");
            $stmt->execute($fabric_ids);
            $fabrics = $stmt->fetchAll();
            
            foreach ($fabrics as $fab) {
                $qty = intval($_SESSION['cart'][$fab['id']]);
                $subtotal = $fab['price'] * $qty;
                $total_price += $subtotal;
                
                $cart_items[] = [
                    'id' => $fab['id'],
                    'name' => $fab['name'],
                    'category' => $fab['category'],
                    'price' => $fab['price'],
                    'image_url' => $fab['image_url'],
                    'qty' => $qty,
                    'subtotal' => $subtotal
                ];
            }
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
}
?>

<div class="container my-5">
    <div class="mb-4">
        <a href="../index.php" class="btn btn-secondary-custom btn-sm"><i class="fa-solid fa-arrow-left me-1"></i> Add More Fabrics</a>
    </div>

    <div class="row justify-content-center g-4">
        <!-- Left Cart Items Column -->
        <div class="col-lg-8">
            <div class="card card-premium p-4">
                <h4 class="fw-bold mb-4 text-white"><i class="fa-solid fa-cart-shopping text-indigo me-2"></i>Your Fabric Order Cart</h4>
                
                <?php if (count($cart_items) > 0): ?>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Preview</th>
                                    <th>Fabric Details</th>
                                    <th>Yards Quantity</th>
                                    <th>Subtotal</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart_items as $item): ?>
                                    <tr>
                                        <td style="width: 80px;">
                                            <img src="<?php echo '../' . htmlspecialchars($item['image_url'] ?? 'images/fabric_bedding.png'); ?>" alt="fabric" style="width: 70px; height: 50px; object-fit: cover;" onerror="this.src='https://placehold.co/100x80/161619/ffffff?text=Fabric'">
                                        </td>
                                        <td>
                                            <div class="fw-bold text-white"><?php echo htmlspecialchars($item['name']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($item['category']); ?> • ₹<?php echo number_format($item['price'], 2); ?>/yard</small>
                                        </td>
                                        <td>
                                            <form action="cart.php" method="POST" class="d-flex align-items-center gap-2">
                                                <input type="hidden" name="action" value="update_qty">
                                                <input type="hidden" name="fabric_id" value="<?php echo $item['id']; ?>">
                                                <input type="number" name="qty" value="<?php echo $item['qty']; ?>" class="form-control form-control-custom text-center py-1" style="width: 65px;" min="1" max="100" onchange="this.form.submit()">
                                            </form>
                                        </td>
                                        <td class="fw-bold text-white">₹<?php echo number_format($item['subtotal'], 2); ?></td>
                                        <td class="text-end">
                                            <form action="cart.php" method="POST">
                                                <input type="hidden" name="action" value="remove">
                                                <input type="hidden" name="fabric_id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger border-0">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fa-solid fa-cart-arrow-down fa-3x text-muted mb-3"></i>
                        <h5 class="fw-bold text-white">Your Cart is Empty</h5>
                        <p class="text-muted mb-0">Browse our coordinates fabrics and add items to place an order.</p>
                        <a href="../index.php" class="btn btn-primary-custom btn-sm mt-4 px-4 py-2">View Collections</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Summary checkout card -->
        <?php if (count($cart_items) > 0): ?>
            <div class="col-lg-4">
                <div class="card card-premium p-4">
                    <h5 class="fw-bold mb-3 text-white"><i class="fa-solid fa-receipt text-indigo me-2"></i>Order Summary</h5>
                    <hr class="border-secondary border-opacity-20">
                    
                    <div class="d-flex justify-content-between align-items-center mb-2 text-white-50">
                        <span>Fabrics Subtotal:</span>
                        <span>₹<?php echo number_format($total_price, 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3 text-white-50">
                        <span>Shipping/Handling:</span>
                        <span class="text-success">FREE</span>
                    </div>

                    <div class="bg-light p-3 rounded-3 mb-4" style="background-color: var(--gold-light) !important; border: 1px solid var(--gold-border);">
                        <div class="d-flex justify-content-between align-items-center text-white">
                            <span class="fw-bold">Total Dues</span>
                            <span class="fs-4 fw-extrabold">₹<?php echo number_format($total_price, 2); ?></span>
                        </div>
                    </div>

                    <?php if (isset($_SESSION['resident_logged_in'])): ?>
                        <a href="checkout.php" class="btn btn-primary-custom btn-lg w-100 py-3">
                            <i class="fa-solid fa-circle-check me-2"></i>Proceed to Checkout
                        </a>
                    <?php else: ?>
                        <a href="login.php?redirect=checkout" class="btn btn-primary-custom btn-lg w-100 py-3">
                            <i class="fa-solid fa-right-to-bracket me-2"></i>Sign In to Place Order
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
