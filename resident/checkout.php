<?php
if (!ob_get_level()) {
    ob_start();
}
require_once '../includes/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in
if (!isset($_SESSION['resident_logged_in'])) {
    $redirect_url = "login.php?redirect=checkout" . (isset($_GET['flat_id']) ? "&flat_id=" . intval($_GET['flat_id']) : "");
    header("Location: " . $redirect_url);
    exit;
}

$resident_id = $_SESSION['resident_id'];
$flat_id = isset($_GET['flat_id']) ? intval($_GET['flat_id']) : 0;
$flat = null;

// Fetch Flat details
if ($flat_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM flats WHERE id = :flat_id");
        $stmt->execute(['flat_id' => $flat_id]);
        $flat = $stmt->fetch();
    } catch (PDOException $e) {
        // Fallback
    }
}

// Fallback: If no valid flat_id was supplied or flat is not Available, load first available flat
if (!$flat || $flat['status'] !== 'Available') {
    try {
        $stmt = $pdo->query("SELECT * FROM flats WHERE status = 'Available' ORDER BY id ASC LIMIT 1");
        $flat = $stmt->fetch();
        if ($flat) {
            $flat_id = $flat['id'];
        }
    } catch (PDOException $e) {}
}

$error = '';
$success_booking = false;
$booking_id = 0;

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm_booking') {
    $cardholder = trim($_POST['cardholder']);
    $card_number = trim($_POST['card_number']);
    
    if (empty($cardholder) || empty($card_number)) {
        $error = 'All billing and credit card fields are required.';
    } elseif (!$flat) {
        $error = 'Selected apartment unit is no longer available.';
    } else {
        try {
            $pdo->beginTransaction();
            
            // 1. Create booking record
            $stmt_book = $pdo->prepare("INSERT INTO bookings (resident_id, flat_id, amount_paid, payment_status) VALUES (:res_id, :flat_id, :amount, 'Paid')");
            $stmt_book->execute([
                'res_id' => $resident_id,
                'flat_id' => $flat['id'],
                'amount' => $flat['price']
            ]);
            $booking_id = $pdo->lastInsertId();
            
            // 2. Set flat status to Booked
            $stmt_flat = $pdo->prepare("UPDATE flats SET status = 'Booked' WHERE id = :flat_id");
            $stmt_flat->execute(['flat_id' => $flat['id']]);
            
            $pdo->commit();
            $success_booking = true;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Error placing booking: ' . $e->getMessage();
        }
    }
}

// Include header after redirects and session validation
require_once '../includes/header.php';

if (!$flat) {
    echo "<div class='container my-5 text-center py-5'><div class='card card-premium p-5 max-width-600 mx-auto border-0 shadow-sm rounded-4'><i class='fa-solid fa-building-circle-exclamation fa-4x text-warning mb-3'></i><h3 class='fw-bold text-dark mb-2'>No Available Apartments</h3><p class='text-muted mb-4'>All units are currently booked or inventory is updating. Please return to the showroom catalog.</p><a href='../flats.php' class='btn btn-warning-custom px-4 py-2.5 font-semibold'><i class='fa-solid fa-list me-2'></i>Explore All Flats</a></div></div>";
    require_once '../includes/footer.php';
    exit;
}

// Helper to get friendly photo title
function get_friendly_photo_title($slot, $path) {
    $map = [
        'std_gated_community.jpg' => 'Gated Community Exterior',
        'std_apartment_building.jpg' => 'Modern Highrise Facade',
        'std_midrise_block.jpg' => 'Midrise Residential Block',
        'std_balcony_view.jpg' => 'Skyline Balcony View',
        'std_security_gate.jpg' => 'Main Security Entrance',
        'ventures_banner.png' => 'Grand Residential Township',
        'std_living_2bhk.jpg' => 'Spacious 2 BHK Living Lounge',
        'std_living_3bhk.jpg' => '3 BHK Designer Lounge',
        'std_compact_1bhk.jpg' => 'Compact 1 BHK Hall',
        'std_master_bedroom.jpg' => 'Master Bedroom Suite',
        'std_guest_bedroom.jpg' => 'Modern Guest Bedroom',
        'std_modular_kitchen.jpg' => 'High-End Modular Kitchen',
        'std_compact_kitchen.jpg' => 'Compact Modern Kitchen',
        'std_dining_room.jpg' => 'Family Dining Area',
        'std_modern_bathroom.jpg' => 'Ceramic Bathroom Suite',
        'std_kids_play_area.jpg' => 'Children Play Park',
        'std_society_pool.jpg' => 'Olympic Swimming Pool',
        'std_society_gym.jpg' => 'Fitness Club & Gym',
        'std_community_hall.jpg' => 'Community Clubhouse',
        'championship_tennis_court.jpg' => 'Championship Tennis Court',
        'private_botanical_garden.jpg' => 'Botanical Landscape Garden',
        'grand_villa_exterior.jpg' => 'Grand Villa Facade',
        'grand_waterfront_estate.jpg' => 'Waterfront Luxury Villa',
        'royal_palace_villa.jpg' => 'Royal Palace Architecture',
        'futuristic_glass_tower.jpg' => 'Futuristic Glass Skyscraper',
        'sky_bridge_residences.jpg' => 'Sky Bridge Penthouse Tower',
        'hillside_luxury_retreat.jpg' => 'Hillside Luxury Retreat',
        'evening_mansion_facade.jpg' => 'Evening Mansion Facade',
        'zen_courtyard_villa.jpg' => 'Zen Courtyard Mansion',
        'private_island_villa.jpg' => 'Private Island Oceanfront Villa',
        'double_height_grand_hall.jpg' => 'Double-Height Grand Lounge',
        'presidential_master_suite.jpg' => 'Presidential Master Suite',
        'chef_gourmet_kitchen.jpg' => 'Chef Gourmet Kitchen',
        'spa_marble_bathroom.jpg' => 'Spa Italian Marble Bathroom',
        'private_home_cinema.jpg' => 'Private Acoustic Home Cinema',
        'rooftop_infinity_skypool.jpg' => 'Rooftop Sky Infinity Pool',
    ];
    $base = basename($path);
    if (isset($map[$base])) {
        return $map[$base];
    }
    switch ($slot) {
        case 'exterior': return 'Main Exterior View';
        case 'interior': return 'Standard Interior Suite';
        case 'amenity':  return 'Society Amenities & Club';
        case 'luxury':   return 'Grand Luxury Feature';
        default:         return 'Property View';
    }
}

$chk_photos = parse_flat_photos($flat['image_url'] ?? '');
$gallery_items = [
    [
        'slot' => 'exterior',
        'label' => '1. Main Exterior',
        'title' => get_friendly_photo_title('exterior', $chk_photos['exterior']),
        'src'   => strpos($chk_photos['exterior'], 'http') === 0 ? $chk_photos['exterior'] : '../' . htmlspecialchars($chk_photos['exterior'])
    ],
    [
        'slot' => 'interior',
        'label' => '2. Standard Interior',
        'title' => get_friendly_photo_title('interior', $chk_photos['interior']),
        'src'   => strpos($chk_photos['interior'], 'http') === 0 ? $chk_photos['interior'] : '../' . htmlspecialchars($chk_photos['interior'])
    ],
    [
        'slot' => 'amenity',
        'label' => '3. Society Amenities',
        'title' => get_friendly_photo_title('amenity', $chk_photos['amenity']),
        'src'   => strpos($chk_photos['amenity'], 'http') === 0 ? $chk_photos['amenity'] : '../' . htmlspecialchars($chk_photos['amenity'])
    ],
    [
        'slot' => 'luxury',
        'label' => '4. Grand Luxury',
        'title' => get_friendly_photo_title('luxury', $chk_photos['luxury']),
        'src'   => strpos($chk_photos['luxury'], 'http') === 0 ? $chk_photos['luxury'] : '../' . htmlspecialchars($chk_photos['luxury'])
    ]
];
?>

<style>
/* Interactive Room & Facility Showcase Styles */
.showcase-container {
    background: #1e2430;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.25);
    color: #ffffff;
}

.showcase-header {
    margin-bottom: 14px;
}

.showcase-header h5 {
    font-size: 1.05rem;
    font-weight: 800;
    letter-spacing: -0.2px;
    margin-bottom: 2px;
}

.showcase-viewport {
    position: relative;
    border-radius: 12px;
    overflow: hidden;
    height: 310px;
    background: #0f131a;
    box-shadow: 0 8px 24px rgba(0,0,0,0.3);
}

.showcase-main-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: opacity 0.25s ease-in-out, transform 0.3s ease;
}

.showcase-btn-next-top {
    position: absolute;
    top: 14px;
    right: 14px;
    background: #f59e0b;
    color: #111827;
    font-weight: 700;
    font-size: 0.75rem;
    padding: 6px 14px;
    border-radius: 20px;
    border: none;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
    transition: all 0.2s ease;
    z-index: 5;
}

.showcase-btn-next-top:hover {
    background: #fbbf24;
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(245, 158, 11, 0.5);
}

.showcase-nav-arrow {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: rgba(17, 24, 39, 0.7);
    color: #ffffff;
    border: 1px solid rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(6px);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    z-index: 5;
}

.showcase-nav-arrow:hover {
    background: rgba(245, 158, 11, 0.9);
    color: #111827;
    border-color: #f59e0b;
    transform: translateY(-50%) scale(1.08);
}

.showcase-nav-arrow.prev { left: 12px; }
.showcase-nav-arrow.next { right: 12px; }

.showcase-bottom-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 16px;
    background: linear-gradient(to top, rgba(15, 19, 26, 0.95) 0%, rgba(15, 19, 26, 0.6) 60%, transparent 100%);
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    z-index: 4;
}

.showcase-active-badge {
    background: #f59e0b;
    color: #111827;
    font-size: 0.65rem;
    font-weight: 800;
    padding: 2px 7px;
    border-radius: 4px;
    text-transform: uppercase;
    display: inline-block;
    margin-bottom: 3px;
    letter-spacing: 0.5px;
}

.showcase-view-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: #ffffff;
    margin: 0;
    text-shadow: 0 1px 3px rgba(0,0,0,0.8);
}

.showcase-index-counter {
    border: 2px solid #f59e0b;
    color: #f59e0b;
    font-size: 0.75rem;
    font-weight: 800;
    padding: 3px 8px;
    border-radius: 6px;
    background: rgba(15, 19, 26, 0.7);
    backdrop-filter: blur(4px);
}

.showcase-thumbnail-strip {
    display: flex;
    gap: 8px;
    margin-top: 12px;
}

.showcase-thumb-item {
    flex: 1;
    height: 60px;
    border-radius: 8px;
    overflow: hidden;
    cursor: pointer;
    border: 2px solid rgba(255, 255, 255, 0.15);
    opacity: 0.65;
    transition: all 0.2s ease;
    position: relative;
    background: #0f131a;
}

.showcase-thumb-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.showcase-thumb-item:hover {
    opacity: 1;
    transform: translateY(-2px);
    border-color: rgba(245, 158, 11, 0.6);
}

.showcase-thumb-item.active {
    opacity: 1;
    border-color: #f59e0b !important;
    box-shadow: 0 0 14px rgba(245, 158, 11, 0.6);
    transform: scale(1.03);
}
</style>

<div class="container my-5">
    <?php if (!$success_booking): ?>
        <div class="mb-4">
            <a href="../flats.php" class="btn btn-secondary-custom btn-sm"><i class="fa-solid fa-arrow-left me-1"></i> Back to All Flats</a>
        </div>

        <div class="row justify-content-center g-4">
            <!-- Left Flat Details Summary & Interactive Room Showcase -->
            <div class="col-lg-6">
                <!-- Interactive Room & Facility Showcase Component -->
                <div class="showcase-container mb-4">
                    <div class="showcase-header d-flex align-items-center gap-2">
                        <div class="d-flex align-items-center justify-content-center text-warning" style="font-size: 1.25rem;">
                            <i class="fa-solid fa-images"></i>
                        </div>
                        <div>
                            <h5 class="text-white mb-0">Interactive Room &amp; Facility Showcase</h5>
                            <span class="text-white-50 small" style="font-size: 0.78rem;">Click any thumbnail below or use the Next/Prev buttons to preview room layouts.</span>
                        </div>
                    </div>

                    <!-- Main Viewport -->
                    <div class="showcase-viewport">
                        <img id="showcaseMainImg" src="<?php echo $gallery_items[0]['src']; ?>" alt="Room Showcase" class="showcase-main-img" onerror="this.src='https://placehold.co/600x400/161619/f59e0b?text=Apartment+View'">
                        
                        <!-- Top Right Next Image Button -->
                        <button type="button" class="showcase-btn-next-top" onclick="navigateShowcase(1)">
                            Next Image &gt;
                        </button>

                        <!-- Left / Right Navigation Arrows -->
                        <button type="button" class="showcase-nav-arrow prev" onclick="navigateShowcase(-1)" aria-label="Previous image">
                            <i class="fa-solid fa-chevron-left"></i>
                        </button>
                        <button type="button" class="showcase-nav-arrow next" onclick="navigateShowcase(1)" aria-label="Next image">
                            <i class="fa-solid fa-chevron-right"></i>
                        </button>

                        <!-- Bottom Gradient Info Overlay -->
                        <div class="showcase-bottom-overlay">
                            <div>
                                <span class="showcase-active-badge">ACTIVE VIEW</span>
                                <h6 class="showcase-view-title" id="showcaseViewTitle"><?php echo htmlspecialchars($gallery_items[0]['title']); ?></h6>
                            </div>
                            <span class="showcase-index-counter" id="showcaseIndexCounter">1/4</span>
                        </div>
                    </div>

                    <!-- Bottom Thumbnail Carousel Strip -->
                    <div class="showcase-thumbnail-strip">
                        <?php foreach ($gallery_items as $index => $item): ?>
                            <div class="showcase-thumb-item <?php echo $index === 0 ? 'active' : ''; ?>" 
                                 onclick="setShowcaseIndex(<?php echo $index; ?>)" 
                                 id="thumb_item_<?php echo $index; ?>"
                                 title="<?php echo htmlspecialchars($item['label'] . ' - ' . $item['title']); ?>">
                                <img src="<?php echo $item['src']; ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Flat Specifications & Details Card -->
                <div class="card card-premium p-4">
                    <h5 class="fw-bold mb-3" style="color:var(--blue-brand);"><i class="fa-solid fa-hotel me-2"></i>Apartment Summary</h5>
                    <hr class="border-secondary border-opacity-20">

                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Location City:</span>
                        <span class="fw-bold text-dark"><?php echo htmlspecialchars($flat['city']); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Unit &amp; Block:</span>
                        <span class="fw-bold text-dark"><?php echo htmlspecialchars($flat['block']); ?> - Unit <?php echo htmlspecialchars($flat['flat_no']); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Apartment Size:</span>
                        <span class="fw-bold text-dark"><?php echo $flat['bhk']; ?> BHK Layout</span>
                    </div>

                    <div class="bg-light p-3 rounded-3 mb-2" style="background-color: rgba(16, 49, 120, 0.05) !important; border: 1px solid rgba(16, 49, 120, 0.15);">
                        <div class="d-flex justify-content-between align-items-center text-dark">
                            <span class="fw-bold" style="color:var(--blue-brand);">Booking Price</span>
                            <span class="fs-4 fw-extrabold" style="color:var(--blue-brand);">₹<?php echo number_format($flat['price'], 2); ?></span>
                        </div>
                    </div>

                    <!-- Booking Offers Box -->
                    <div class="mt-3 p-3 rounded-3" style="background-color: rgba(39, 174, 96, 0.05); border: 1px dashed #27ae60;">
                        <h6 class="fw-bold mb-2 text-success" style="font-size:0.85rem;"><i class="fa-solid fa-gift me-2 text-success"></i>Exclusive Booking Offers Included:</h6>
                        <ul class="list-unstyled mb-0 small text-muted d-flex flex-column gap-2">
                            <li class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-circle-check text-success"></i>
                                <span><strong>Free Covered Car Parking Slot</strong></span>
                            </li>
                            <li class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-circle-check text-success"></i>
                                <span><strong>Free High-Speed Wi-Fi</strong> (1-Year Plan)</span>
                            </li>
                            <li class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-circle-check text-success"></i>
                                <span><strong>0% Processing Fee</strong> on Home Loans</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Right Checkout Form -->
            <div class="col-lg-6">
                <div class="card card-premium p-4">
                    <h5 class="fw-bold mb-3" style="color:var(--blue-brand);"><i class="fa-solid fa-credit-card me-2"></i>Secure Booking Checkout</h5>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fa-solid fa-triangle-exclamation me-2"></i><?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <form action="checkout.php?flat_id=<?php echo $flat['id']; ?>" method="POST">
                        <input type="hidden" name="action" value="confirm_booking">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="cardholder" class="form-label fw-semibold">Cardholder Name</label>
                                <input type="text" class="form-control form-control-custom" id="cardholder" name="cardholder" required placeholder="e.g. John Doe">
                            </div>
                            <div class="col-12">
                                <label for="card_number" class="form-label fw-semibold">Card Number</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fa-solid fa-credit-card text-muted"></i></span>
                                    <input type="text" class="form-control form-control-custom border-start-0" id="card_number" name="card_number" required placeholder="4111 2222 3333 4444" maxlength="19">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="expiry" class="form-label fw-semibold">Expiry Date</label>
                                <input type="text" class="form-control form-control-custom" id="expiry" placeholder="MM/YY" required maxlength="5">
                            </div>
                            <div class="col-md-6">
                                <label for="cvv" class="form-label fw-semibold">CVV</label>
                                <input type="password" class="form-control form-control-custom" id="cvv" placeholder="•••" required maxlength="4">
                            </div>
                        </div>

                        <div class="alert alert-info mt-4 mb-4" role="alert" style="background: rgba(242, 161, 34, 0.1); color: var(--orange-brand); border: none; border-radius: 4px;">
                            <i class="fa-solid fa-circle-info me-2"></i>This is a booking deposit simulation. No real money will be charged.
                        </div>

                        <button type="submit" class="btn btn-primary-custom w-100 py-3 fw-bold text-white" style="background-color:var(--blue-brand); border-color:var(--blue-brand);">
                            <i class="fa-solid fa-lock me-2"></i>Confirm Booking Deposit (₹<?php echo number_format($flat['price'], 2); ?>)
                        </button>
                    </form>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Success Confirmation Receipt View -->
        <div class="row justify-content-center py-5">
            <div class="col-md-7 text-center">
                <div class="card card-premium p-5 text-center">
                    <div class="d-inline-flex bg-success bg-opacity-20 text-success p-4 rounded-circle mb-4 mx-auto" style="border: 2px solid rgba(39, 174, 96, 0.3); background-color:rgba(39, 174, 96, 0.15) !important;">
                        <i class="fa-solid fa-circle-check fa-4x text-success"></i>
                    </div>
                    <h2 class="fw-extrabold mb-2" style="color:var(--blue-brand);">Booking Deposit Confirmed!</h2>
                    <p class="text-muted fs-5 mb-4">Your booking ID #<?php echo $booking_id; ?> is registered. Our sales representatives will reach out shortly.</p>
                    
                    <div class="bg-light p-4 rounded-3 text-start mb-4 border border-secondary border-opacity-10" style="background-color:#f5f6f9 !important;">
                        <h6 class="fw-bold text-indigo mb-3" style="color:var(--blue-brand) !important;"><i class="fa-solid fa-receipt me-2"></i>Booking Invoice Details</h6>
                        <div class="row g-3 text-white-50 small" style="color:#2c3e50 !important;">
                            <div class="col-6"><strong>Buyer Name:</strong><br><span class="text-dark fw-bold"><?php echo htmlspecialchars($_SESSION['resident_name'] ?? 'John Doe'); ?></span></div>
                            <div class="col-6"><strong>Booking Reference:</strong><br><span class="text-dark fw-bold">CSG-#<?php echo $booking_id; ?></span></div>
                            <div class="col-6"><strong>Deposit Paid:</strong><br><span class="text-dark fw-bold">₹<?php echo number_format($flat['price'], 2); ?></span></div>
                            <div class="col-6"><strong>Booking Status:</strong><br><span class="text-success fw-bold">Confirmed</span></div>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-3 justify-content-center">
                        <button onclick="window.print()" class="btn btn-secondary-custom px-4 py-3"><i class="fa-solid fa-print me-2"></i>Print Invoice</button>
                        <a href="dashboard.php" class="btn btn-primary-custom px-4 py-3 text-white" style="background-color:var(--blue-brand); border-color:var(--blue-brand);">Go to Dashboard</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Gallery data array from PHP
const showcaseItems = <?php echo json_encode($gallery_items); ?>;
let currentShowcaseIndex = 0;

function updateShowcaseDisplay() {
    const item = showcaseItems[currentShowcaseIndex];
    if (!item) return;

    const mainImg = document.getElementById('showcaseMainImg');
    const viewTitle = document.getElementById('showcaseViewTitle');
    const indexCounter = document.getElementById('showcaseIndexCounter');

    // Smooth image transition
    if (mainImg) {
        mainImg.style.opacity = '0.3';
        setTimeout(() => {
            mainImg.src = item.src;
            mainImg.style.opacity = '1';
        }, 150);
    }

    if (viewTitle) {
        viewTitle.innerText = item.title;
    }

    if (indexCounter) {
        indexCounter.innerText = (currentShowcaseIndex + 1) + '/' + showcaseItems.length;
    }

    // Update active thumbnail border glow
    document.querySelectorAll('.showcase-thumb-item').forEach((thumb, idx) => {
        if (idx === currentShowcaseIndex) {
            thumb.classList.add('active');
        } else {
            thumb.classList.remove('active');
        }
    });
}

function setShowcaseIndex(index) {
    if (index >= 0 && index < showcaseItems.length) {
        currentShowcaseIndex = index;
        updateShowcaseDisplay();
    }
}

function navigateShowcase(direction) {
    currentShowcaseIndex = (currentShowcaseIndex + direction + showcaseItems.length) % showcaseItems.length;
    updateShowcaseDisplay();
}

// Keyboard arrow navigation
document.addEventListener('keydown', function(e) {
    if (e.key === 'ArrowRight') {
        navigateShowcase(1);
    } else if (e.key === 'ArrowLeft') {
        navigateShowcase(-1);
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
