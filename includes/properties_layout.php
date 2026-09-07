<?php
require_once 'db.php';

$inquiry_success = '';
$inquiry_error = '';

// Handle Inquiry Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_inquiry') {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $city = trim($_POST['city']);
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    
    if (empty($name) || empty($phone) || empty($city)) {
        $inquiry_error = 'All inquiry fields are required.';
    } else {
        try {
            $stmt_inq = $pdo->prepare("INSERT INTO inquiries (name, phone, city) VALUES (:name, :phone, :city)");
            $stmt_inq->execute([
                'name' => $name,
                'phone' => $phone,
                'city' => $city
            ]);
            $inquiry_success = "Thank you, Non-Resident Investor! Your inquiry was successfully registered. Our sales representatives will call you shortly.";
        } catch (PDOException $e) {
            $inquiry_error = 'Error saving inquiry: ' . $e->getMessage();
        }
    }
}

// Handle Site Visit Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'schedule_visit') {
    $name = trim($_POST['visit_name']);
    $phone = trim($_POST['visit_phone']);
    $date = trim($_POST['visit_date']);
    $time = trim($_POST['visit_time']);
    
    if (empty($name) || empty($phone) || empty($date)) {
        $inquiry_error = 'Please fill out name, phone, and date.';
    } else {
        $inquiry_success = "Site visit successfully scheduled for " . htmlspecialchars($date) . " at " . htmlspecialchars($time) . ". A confirmation SMS has been sent to " . htmlspecialchars($phone) . ".";
    }
}

// Handle logouts
if (isset($_GET['logout'])) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if ($_GET['logout'] === 'resident') {
        unset($_SESSION['resident_logged_in']);
        unset($_SESSION['resident_id']);
        unset($_SESSION['resident_name']);
        unset($_SESSION['resident_email']);
    } elseif ($_GET['logout'] === 'admin') {
        unset($_SESSION['admin_logged_in']);
        unset($_SESSION['admin_id']);
        unset($_SESSION['admin_username']);
    }
    header("Location: index.php");
    exit;
}

require_once 'header.php';

$current_page = basename($_SERVER['PHP_SELF']);
$any_only = isset($only_grid) || isset($only_amenities) || isset($only_gallery) || isset($only_pricing) || isset($only_about) || isset($only_contact);

if (!isset($active_type)) {
    $active_type = 'Residential';
}

$cities_list = ['Chennai', 'Bengaluru', 'Coimbatore', 'Hyderabad', 'Dubai', 'Pune'];
$city_counts = [];
$total_projects = 0;
$total_available = 0;
$total_booked = 0;

foreach ($cities_list as $c) {
    try {
        $proj_stmt = $pdo->prepare("SELECT COUNT(*) FROM flats WHERE city = :city AND type = :type");
        $proj_stmt->execute(['city' => $c, 'type' => $active_type]);
        $c_proj = intval($proj_stmt->fetchColumn());

        if ($c_proj === 0) {
            $fallback_stmt = $pdo->prepare("SELECT COUNT(*) FROM flats WHERE city = :city");
            $fallback_stmt->execute(['city' => $c]);
            $c_proj = intval($fallback_stmt->fetchColumn());
        }

        $avail_stmt = $pdo->prepare("SELECT COUNT(*) FROM flats WHERE city = :city AND status = 'Available'");
        $avail_stmt->execute(['city' => $c]);
        $c_avail = intval($avail_stmt->fetchColumn());

        $booked_stmt = $pdo->prepare("SELECT COUNT(*) FROM flats WHERE city = :city AND status = 'Booked'");
        $booked_stmt->execute(['city' => $c]);
        $c_booked = intval($booked_stmt->fetchColumn());
        
        $city_counts[$c] = [
            'projects' => $c_proj,
            'available' => $c_avail,
            'booked' => $c_booked
        ];
        
        $total_projects += $c_proj;
        $total_available += $c_avail;
        $total_booked += $c_booked;
    } catch (PDOException $e) {
        $city_counts[$c] = ['projects' => 2, 'available' => 2, 'booked' => 0];
    }
}

// Handle filters
$filter_city = isset($_GET['city']) ? $_GET['city'] : '';
$filter_budget = isset($_GET['budget']) ? $_GET['budget'] : '';
$filter_bhk = isset($_GET['bhk']) ? $_GET['bhk'] : '';
$filter_search_name = isset($_GET['search_name']) ? trim($_GET['search_name']) : '';
$filter_floor = isset($_GET['floor']) ? $_GET['floor'] : '';

$where_clauses = ["type = :type"];
$params = ['type' => $active_type];

if (!empty($filter_city) && in_array($filter_city, $cities_list)) {
    $where_clauses[] = "city = :city";
    $params['city'] = $filter_city;
}

if (!empty($filter_search_name)) {
    $where_clauses[] = "block LIKE :search_name";
    $params['search_name'] = '%' . $filter_search_name . '%';
}

if ($filter_floor !== '') {
    $where_clauses[] = "floor = :floor";
    $params['floor'] = intval($filter_floor);
}

if (!empty($filter_budget)) {
    if ($filter_budget === '100000') {
        $where_clauses[] = "price < 100000";
    } elseif ($filter_budget === '200000') {
        $where_clauses[] = "price BETWEEN 100000 AND 200000";
    } elseif ($filter_budget === '300000') {
        $where_clauses[] = "price BETWEEN 200000 AND 300000";
    } elseif ($filter_budget === 'above') {
        $where_clauses[] = "price > 300000";
    }
}

if (!empty($filter_bhk)) {
    $where_clauses[] = "bhk = :bhk";
    $params['bhk'] = intval($filter_bhk);
}

$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
if (!empty($filter_status) && in_array($filter_status, ['Available', 'Booked'])) {
    $where_clauses[] = "status = :status";
    $params['status'] = $filter_status;
}

$where_sql = "WHERE " . implode(" AND ", $where_clauses);
try {
    $stmt = $pdo->prepare("SELECT * FROM flats $where_sql ORDER BY status ASC, city ASC, flat_no ASC");
    $stmt->execute($params);
    $flats = $stmt->fetchAll();

    // Fallback: If no flats were found for this specific city/type filter combination, fetch all flats for the city
    if (empty($flats) && !empty($filter_city)) {
        $fallback_stmt = $pdo->prepare("SELECT * FROM flats WHERE city = :city ORDER BY status ASC, flat_no ASC");
        $fallback_stmt->execute(['city' => $filter_city]);
        $flats = $fallback_stmt->fetchAll();
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}
?>

<!-- Inquiry Alerts -->
<?php if (!empty($inquiry_success)): ?>
    <div class="container mt-3">
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i><?php echo htmlspecialchars($inquiry_success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($inquiry_error)): ?>
    <div class="container mt-3">
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-xmark me-2"></i><?php echo htmlspecialchars($inquiry_error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
<?php endif; ?>

<!-- Right Sticky Contact Sidebar Widget -->
<div class="sticky-contact-sidebar d-print-none">
    <a href="#welcome" class="sticky-contact-icon" title="Register Inquiry" data-bs-toggle="modal" data-bs-target="#inquiryModal"><i class="fa-regular fa-clipboard"></i></a>
    <a href="tel:<?php echo preg_replace('/[^0-9+]/', '', get_setting('contact_phone', '+918056210606')); ?>" class="sticky-contact-icon" title="Call Sales Representative"><i class="fa-solid fa-phone"></i></a>
    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', get_setting('whatsapp_phone', '918056210606')); ?>" class="sticky-contact-icon" title="Chat on WhatsApp" target="_blank"><i class="fa-brands fa-whatsapp"></i></a>
    <a href="mailto:<?php echo htmlspecialchars(get_setting('contact_email', 'sales@jarvisresidences.com')); ?>" class="sticky-contact-icon" title="Email Sales"><i class="fa-solid fa-envelope"></i></a>
    <a href="https://maps.google.com/?q=<?php echo urlencode(get_setting('contact_address', 'Casagrand Chennai')); ?>" class="sticky-contact-icon" title="View Location" target="_blank"><i class="fa-solid fa-map-location-dot"></i></a>
</div>

<!-- Floating Circular Chat Widget -->
<div class="floating-chat-bubble d-print-none" onclick="toggleChat()">
    <i class="fa-regular fa-comments"></i>
    <span>Chat</span>
</div>

<!-- Sliding Live Chat Box -->
<div class="chat-box-container" id="chatBox">
    <div class="chat-header">
        <div class="d-flex align-items-center gap-2">
            <div class="glowing-jarvis-core">
                <div class="jarvis-core-pulse"></div>
                <i class="fa-solid fa-atom text-warning" style="font-size: 0.75rem;"></i>
            </div>
            <span class="fw-bold">Jarvis Elite Concierge</span>
        </div>
        <button class="btn btn-sm btn-link text-white p-0 border-0 text-decoration-none" onclick="toggleChat()"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="chat-body" id="chatBody">
        <div class="chat-msg-row bot-row">
            <div class="bot-avatar-mini"><i class="fa-solid fa-atom"></i></div>
            <div class="chat-msg bot">Hi! Welcome to Jarvis Residences. How can I assist you with our luxury properties today?</div>
        </div>
    </div>
    <div class="chat-input-area">
        <input type="text" id="chatInput" placeholder="Ask about prices, locations..." onkeydown="if(event.key === 'Enter') sendChatMessage()">
        <button onclick="sendChatMessage()">Send</button>
    </div>
</div>

<?php if ($active_type === 'Residential'): ?>
    <!-- ------------------------------------------------------------- -->
    <!-- RESIDENTIAL HOME VIEW (Screenshot 1 Layout) -->
    <!-- ------------------------------------------------------------- -->
    <!-- Hero 5-Slide Carousel (Permanently moves every 2 seconds) -->
    <?php if (!$any_only): ?>
    <section class="position-relative overflow-hidden mb-5">
        <div id="heroCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="2000" data-bs-pause="false" data-bs-wrap="true">
            <div class="carousel-indicators">
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active" aria-label="Residential Slide"></button>
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1" aria-label="Commercial Slide"></button>
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2" aria-label="Industrial Slide"></button>
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="3" aria-label="NRI Slide"></button>
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="4" aria-label="Ventures Slide"></button>
            </div>
            <div class="carousel-inner">
                <!-- Slide 1: Residential -->
                <div class="carousel-item active" data-bs-interval="2000">
                    <img src="<?php echo $base_path; ?>images/living.png" class="carousel-zoom-img" alt="Residential Project Background">
                    <div class="carousel-overlay-custom"></div>
                    <div class="carousel-caption d-flex flex-column h-100 justify-content-center align-items-center pb-5">
                        <span class="text-warning text-uppercase fw-bold mb-2" style="letter-spacing:3px; font-size:0.9rem;"><?php echo htmlspecialchars(get_setting('hero_subtitle', 'INTELLIGENT INTEGRATED TOWNSHIPS')); ?></span>
                        <h2 class="fw-bold text-white text-shadow mb-3" style="font-family:'Plus Jakarta Sans', sans-serif; font-size: 2.8rem; letter-spacing: 0.5px;"><?php echo htmlspecialchars(get_setting('hero_title', 'Ventures Projects - Jarvis')); ?></h2>
                        <div class="d-flex gap-3">
                            <a href="#contact" class="btn btn-warning text-dark fw-bold px-4 py-2.5" style="border-radius: 6px;"><i class="fa-solid fa-house-circle-check me-2"></i>Book Now</a>
                            <a href="#apartments-showcase" class="btn btn-outline-light fw-semibold px-4 py-2.5" style="border-radius: 6px;"><i class="fa-solid fa-list me-2"></i>Explore Flats</a>
                        </div>
                    </div>
                </div>
                <!-- Slide 2: Commercial -->
                <div class="carousel-item" data-bs-interval="2000">
                    <img src="<?php echo $base_path; ?>images/kitchen.png" class="carousel-zoom-img" alt="Commercial Project Background">
                    <div class="carousel-overlay-custom"></div>
                    <div class="carousel-caption d-flex flex-column h-100 justify-content-center align-items-center pb-5">
                        <span class="text-warning text-uppercase fw-bold mb-2" style="letter-spacing:3px; font-size:0.9rem;">High-Yield Business Plazas</span>
                        <h2 class="fw-bold text-white text-shadow mb-3" style="font-family:'Plus Jakarta Sans', sans-serif; font-size: 2.8rem; letter-spacing: 0.5px;">Commercial Projects - <?php echo htmlspecialchars(get_setting('system_name', 'Jarvis')); ?></h2>
                        <div class="d-flex gap-3">
                            <a href="#contact" class="btn btn-warning text-dark fw-bold px-4 py-2.5" style="border-radius: 6px;"><i class="fa-solid fa-house-circle-check me-2"></i>Book Now</a>
                            <a href="#apartments-showcase" class="btn btn-outline-light fw-semibold px-4 py-2.5" style="border-radius: 6px;"><i class="fa-solid fa-list me-2"></i>Explore Flats</a>
                        </div>
                    </div>
                </div>
                <!-- Slide 3: Industrial -->
                <div class="carousel-item" data-bs-interval="2000">
                    <img src="<?php echo $base_path; ?>images/bedroom.png" class="carousel-zoom-img" alt="Industrial Project Background">
                    <div class="carousel-overlay-custom"></div>
                    <div class="carousel-caption d-flex flex-column h-100 justify-content-center align-items-center pb-5">
                        <span class="text-warning text-uppercase fw-bold mb-2" style="letter-spacing:3px; font-size:0.9rem;">Modern Intelligent Logistics</span>
                        <h2 class="fw-bold text-white text-shadow mb-3" style="font-family:'Plus Jakarta Sans', sans-serif; font-size: 2.8rem; letter-spacing: 0.5px;">Industrial Projects - <?php echo htmlspecialchars(get_setting('system_name', 'Jarvis')); ?></h2>
                        <div class="d-flex gap-3">
                            <a href="#contact" class="btn btn-warning text-dark fw-bold px-4 py-2.5" style="border-radius: 6px;"><i class="fa-solid fa-house-circle-check me-2"></i>Book Now</a>
                            <a href="#apartments-showcase" class="btn btn-outline-light fw-semibold px-4 py-2.5" style="border-radius: 6px;"><i class="fa-solid fa-list me-2"></i>Explore Flats</a>
                        </div>
                    </div>
                </div>
                <!-- Slide 4: NRI Beach Villas -->
                <div class="carousel-item" data-bs-interval="2000">
                    <img src="<?php echo $base_path; ?>images/living.png" class="carousel-zoom-img" style="filter: hue-rotate(15deg);" alt="NRI Project Background">
                    <div class="carousel-overlay-custom"></div>
                    <div class="carousel-caption d-flex flex-column h-100 justify-content-center align-items-center pb-5">
                        <span class="text-warning text-uppercase fw-bold mb-2" style="letter-spacing:3px; font-size:0.9rem;">Luxury Waterfront Mansions</span>
                        <h2 class="fw-bold text-white text-shadow mb-3" style="font-family:'Plus Jakarta Sans', sans-serif; font-size: 2.8rem; letter-spacing: 0.5px;">NRI Projects - <?php echo htmlspecialchars(get_setting('system_name', 'Jarvis')); ?></h2>
                        <div class="d-flex gap-3">
                            <a href="#contact" class="btn btn-warning text-dark fw-bold px-4 py-2.5" style="border-radius: 6px;"><i class="fa-solid fa-house-circle-check me-2"></i>Book Now</a>
                            <a href="#apartments-showcase" class="btn btn-outline-light fw-semibold px-4 py-2.5" style="border-radius: 6px;"><i class="fa-solid fa-list me-2"></i>Explore Flats</a>
                        </div>
                    </div>
                </div>
                <!-- Slide 5: Our Ventures -->
                <div class="carousel-item" data-bs-interval="2000">
                    <img src="<?php echo $base_path; ?>images/kitchen.png" class="carousel-zoom-img" style="filter: hue-rotate(60deg);" alt="Ventures Project Background">
                    <div class="carousel-overlay-custom"></div>
                    <div class="carousel-caption d-flex flex-column h-100 justify-content-center align-items-center pb-5">
                        <span class="text-warning text-uppercase fw-bold mb-2" style="letter-spacing:3px; font-size:0.9rem;"><?php echo htmlspecialchars(get_setting('hero_subtitle', 'INTELLIGENT INTEGRATED TOWNSHIPS')); ?></span>
                        <h2 class="fw-bold text-white text-shadow mb-3" style="font-family:'Plus Jakarta Sans', sans-serif; font-size: 2.8rem; letter-spacing: 0.5px;"><?php echo htmlspecialchars(get_setting('hero_title', 'Ventures Projects - Jarvis')); ?></h2>
                        <div class="d-flex gap-3">
                            <a href="#contact" class="btn btn-warning text-dark fw-bold px-4 py-2.5" style="border-radius: 6px;"><i class="fa-solid fa-house-circle-check me-2"></i>Book Now</a>
                            <a href="#apartments-showcase" class="btn btn-outline-light fw-semibold px-4 py-2.5" style="border-radius: 6px;"><i class="fa-solid fa-list me-2"></i>Explore Flats</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var heroEl = document.getElementById('heroCarousel');
        if (heroEl && typeof bootstrap !== 'undefined') {
            var heroCarouselInstance = bootstrap.Carousel.getOrCreateInstance(heroEl, {
                interval: 2000,
                ride: 'carousel',
                pause: false,
                wrap: true
            });
            heroCarouselInstance.cycle();
        }
    });
    
// Switch Card Photo in Multi-Slot View
function switchCardPhoto(imgId, newSrc, btn) {
    const img = document.getElementById(imgId);
    if (!img) return;
    img.style.opacity = '0.4';
    setTimeout(() => {
        img.src = newSrc;
        img.style.opacity = '1';
    }, 150);
    const parent = btn.parentElement;
    if (parent) {
        parent.querySelectorAll('.photo-slot-pill').forEach(b => {
            b.classList.remove('active');
            b.style.background = 'rgba(255,255,255,0.25)';
        });
        btn.classList.add('active');
        btn.style.background = 'var(--orange-brand, #f59e0b)';
    }
}
</script>
    <?php endif; ?>

    <!-- Search Section (Item 3) -->
    <?php if (isset($only_grid) || !$any_only): ?>
    <div class="container mb-5 mt-n4 position-relative" style="z-index: 100;">
        <div class="card shadow-lg border-0 p-4" style="border-radius:16px; background-color: #ffffff; border-top: 5px solid var(--orange-brand) !important;">
            <h5 class="fw-bold mb-3 text-dark" style="font-family:'Plus Jakarta Sans', sans-serif;"><i class="fa-solid fa-magnifying-glass text-warning me-2"></i>Find Your Dream Flat</h5>
            <form action="flats.php" method="GET" class="row g-3">
                <!-- Search by Apartment Name -->
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted">Search by Apartment Name</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-hotel text-muted"></i></span>
                        <input type="text" name="search_name" class="form-control form-control-custom border-start-0" placeholder="e.g. Casagrand Elan" value="<?php echo htmlspecialchars($filter_search_name); ?>">
                    </div>
                </div>
                <!-- Search by Location -->
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted">Search by Location</label>
                    <select name="city" class="form-select form-control-custom">
                        <option value="">All Locations</option>
                        <?php foreach ($cities_list as $c): ?>
                            <option value="<?php echo htmlspecialchars($c); ?>" <?php echo $filter_city === $c ? 'selected' : ''; ?>><?php echo htmlspecialchars($c); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Flat Type -->
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted">Flat Type</label>
                    <select name="bhk" class="form-select form-control-custom">
                        <option value="">All Types</option>
                        <option value="1" <?php echo $filter_bhk === '1' ? 'selected' : ''; ?>>1 BHK Layout</option>
                        <option value="2" <?php echo $filter_bhk === '2' ? 'selected' : ''; ?>>2 BHK Layout</option>
                        <option value="3" <?php echo $filter_bhk === '3' ? 'selected' : ''; ?>>3 BHK Layout</option>
                    </select>
                </div>
                <!-- Budget Filter -->
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Budget Range</label>
                    <select name="budget" class="form-select form-control-custom">
                        <option value="">All Prices</option>
                        <option value="100000" <?php echo $filter_budget === '100000' ? 'selected' : ''; ?>>Under ₹1,00,000</option>
                        <option value="200000" <?php echo $filter_budget === '200000' ? 'selected' : ''; ?>>₹1,00,000 - ₹2,00,000</option>
                        <option value="300000" <?php echo $filter_budget === '300000' ? 'selected' : ''; ?>>₹2,00,000 - ₹3,00,000</option>
                        <option value="above" <?php echo $filter_budget === 'above' ? 'selected' : ''; ?>>Above ₹3,00,000</option>
                    </select>
                </div>
                <!-- Availability -->
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Availability</label>
                    <select name="status" class="form-select form-control-custom">
                        <option value="">All States</option>
                        <option value="Available" <?php echo (isset($_GET['status']) && $_GET['status'] === 'Available') ? 'selected' : ''; ?>>Available</option>
                        <option value="Booked" <?php echo (isset($_GET['status']) && $_GET['status'] === 'Booked') ? 'selected' : ''; ?>>Booked / Reserved</option>
                    </select>
                </div>
                <!-- Floor Number -->
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Floor Number</label>
                    <select name="floor" class="form-select form-control-custom">
                        <option value="">All Floors</option>
                        <option value="0" <?php echo $filter_floor === '0' ? 'selected' : ''; ?>>Ground Floor</option>
                        <option value="1" <?php echo $filter_floor === '1' ? 'selected' : ''; ?>>1st Floor</option>
                        <option value="2" <?php echo $filter_floor === '2' ? 'selected' : ''; ?>>2nd Floor</option>
                        <option value="5" <?php echo $filter_floor === '5' ? 'selected' : ''; ?>>5th Floor</option>
                        <option value="12" <?php echo $filter_floor === '12' ? 'selected' : ''; ?>>12th Floor</option>
                    </select>
                </div>
                <!-- Search Button -->
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-warning text-dark fw-bold w-100 py-2.5" style="border-radius:6px; background-color: var(--orange-brand); border: none;"><i class="fa-solid fa-search me-2"></i>Search</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Location Summary tab filter row -->
    <?php if (!isset($only_about) && !isset($only_amenities) && !isset($only_gallery) && !isset($only_pricing) && !isset($only_contact)): ?>
    <div class="container mb-5">
        <div class="location-summary-row shadow-sm">
            <!-- ALL TAB -->
            <a href="flats.php" class="location-tab <?php echo $filter_city === '' ? 'active' : ''; ?>">
                <span class="tab-label">All</span>
                <span class="tab-subtext">Booked - <?php echo $total_booked; ?></span>
                <span class="tab-subtext">Available - <?php echo $total_available; ?></span>
            </a>
            <!-- CITY TABS -->
            <?php foreach ($cities_list as $city): ?>
                <?php 
                    $b_count = $city_counts[$city]['booked'];
                    $a_count = $city_counts[$city]['available'];
                ?>
                <a href="flats.php?city=<?php echo urlencode($city); ?>" class="location-tab <?php echo $filter_city === $city ? 'active' : ''; ?>">
                    <span class="tab-label"><?php echo htmlspecialchars($city); ?></span>
                    <span class="tab-subtext">Booked - <?php echo $b_count; ?></span>
                    <span class="tab-subtext">Available - <?php echo $a_count; ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Properties Listing showcase grid -->
    <?php if (isset($only_grid) || !$any_only): ?>
    <div class="container my-5 py-3" id="apartments-showcase">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-4 mb-5 border-bottom pb-4 border-secondary border-opacity-10">
            <div>
                <h2 class="fw-bold text-uppercase" style="letter-spacing: 2px;">Featured Residential Properties</h2>
                <p class="text-muted mb-0">Select from our verified luxury projects. Reserve your space directly online.</p>
            </div>
            <div>
                <?php if (!empty($filter_city)): ?>
                    <span class="badge bg-primary px-3 py-2 text-uppercase" style="background-color:var(--blue-brand) !important; font-size:0.75rem;"><i class="fa-solid fa-location-dot me-1"></i> <?php echo htmlspecialchars($filter_city); ?></span>
                <?php else: ?>
                    <span class="badge bg-primary px-3 py-2 text-uppercase" style="background-color:var(--blue-brand) !important; font-size:0.75rem;"><i class="fa-solid fa-globe me-1"></i> All Locations</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Listings Cards Grid -->
        <div class="row g-4">
            <?php 
            $rendered_count = 0;
            if (count($flats) > 0): 
                foreach ($flats as $flat): 
                    if (($flat['status'] === 'Booked' || $flat['status'] === 'Occupied') && (empty($_GET['status']) || $_GET['status'] !== 'Booked')) continue; 
                    $rendered_count++;
            ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card card-premium h-100 d-flex flex-column justify-content-between position-relative">
                            
                            <!-- Wishlist heart button -->
                            <button class="wishlist-heart-btn" data-flat-id="<?php echo $flat['id']; ?>" onclick="toggleWishlist(<?php echo $flat['id']; ?>, this)">
                                <i class="fa-solid fa-heart"></i>
                            </button>
                            
                            <div>
                                <!-- Card Image Preview with 4-Slot Photo Switcher -->
                                <?php 
                                    $photos = parse_flat_photos($flat['image_url'] ?? '');
                                    $ext_img = $base_path . htmlspecialchars($photos['exterior']);
                                    $int_img = $base_path . htmlspecialchars($photos['interior']);
                                    $amn_img = $base_path . htmlspecialchars($photos['amenity']);
                                    $lux_img = $base_path . htmlspecialchars($photos['luxury']);
                                    $flat_card_id = 'card_img_' . $flat['id'];
                                ?>
                                <div class="card-image-wrapper position-relative">
                                    <img id="<?php echo $flat_card_id; ?>" src="<?php echo $ext_img; ?>" alt="Apartment showcase preview" onerror="this.src='https://placehold.co/600x400/f5f6f9/2c3e50?text=Luxury+Suite'" style="transition: opacity 0.2s ease-in-out;">
                                    <span class="position-absolute top-0 start-0 m-3 badge bg-dark bg-opacity-80 text-white fw-bold d-flex align-items-center gap-1.5 px-2.5 py-1.5 rounded-pill backdrop-blur shadow-sm z-2" style="font-size:0.68rem; letter-spacing:0.5px; border: 1px solid rgba(242,161,34,0.4);">
                                        <i class="fa-solid fa-crown text-warning" style="font-size: 0.75rem;"></i> JARVIS VERIFIED
                                    </span>
                                    <!-- 4-Photo View Switcher Strip -->
                                    <div class="position-absolute bottom-0 start-0 w-100 p-2 d-flex justify-content-center gap-1 z-2" style="background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, transparent 100%);">
                                        <button type="button" class="btn btn-xs py-0.5 px-2 text-white border-0 fw-semibold rounded-pill photo-slot-pill active" 
                                                onclick="switchCardPhoto('<?php echo $flat_card_id; ?>', '<?php echo $ext_img; ?>', this)" 
                                                style="font-size:0.65rem; background: var(--orange-brand); cursor:pointer;">
                                            Exterior
                                        </button>
                                        <button type="button" class="btn btn-xs py-0.5 px-2 text-white border-0 fw-semibold rounded-pill photo-slot-pill" 
                                                onclick="switchCardPhoto('<?php echo $flat_card_id; ?>', '<?php echo $int_img; ?>', this)" 
                                                style="font-size:0.65rem; background: rgba(255,255,255,0.25); backdrop-filter: blur(4px); cursor:pointer;">
                                            Interior
                                        </button>
                                        <button type="button" class="btn btn-xs py-0.5 px-2 text-white border-0 fw-semibold rounded-pill photo-slot-pill" 
                                                onclick="switchCardPhoto('<?php echo $flat_card_id; ?>', '<?php echo $amn_img; ?>', this)" 
                                                style="font-size:0.65rem; background: rgba(255,255,255,0.25); backdrop-filter: blur(4px); cursor:pointer;">
                                            Amenities
                                        </button>
                                        <button type="button" class="btn btn-xs py-0.5 px-2 text-white border-0 fw-semibold rounded-pill photo-slot-pill" 
                                                onclick="switchCardPhoto('<?php echo $flat_card_id; ?>', '<?php echo $lux_img; ?>', this)" 
                                                style="font-size:0.65rem; background: rgba(255,255,255,0.25); backdrop-filter: blur(4px); cursor:pointer;">
                                            Luxury
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Card Details -->
                                <div class="card-body">
                                    <div class="mb-2 d-flex align-items-center justify-content-between">
                                        <span class="bhk-badge"><?php echo $flat['bhk']; ?> BHK Layout</span>
                                        <?php if ($flat['status'] === 'Available'): ?>
                                            <span class="badge badge-status badge-vacant">
                                                Available
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Star Rating stars -->
                                    <div class="mb-2 text-warning" style="font-size: 0.7rem;">
                                        <i class="fa-solid fa-star"></i>
                                        <i class="fa-solid fa-star"></i>
                                        <i class="fa-solid fa-star"></i>
                                        <i class="fa-solid fa-star"></i>
                                        <i class="fa-solid fa-star-half-stroke"></i>
                                        <span class="text-muted ms-1 small">(4.8 rating)</span>
                                    </div>

                                    <h4 class="fw-bold mb-2" style="font-size:1.15rem; color:var(--blue-brand);"><?php echo htmlspecialchars($flat['block']); ?> – Flat <span style="color:var(--orange-brand); font-weight:bold;">✦</span> <?php echo htmlspecialchars($flat['flat_no']); ?></h4>
                                    <div class="text-muted small mb-2"><i class="fa-solid fa-location-dot me-1 text-danger"></i> <?php echo htmlspecialchars($flat['city']); ?></div>
                                    
                                    <!-- Dynamic Spec Badges (Item 4) -->
                                    <?php 
                                        $area = $flat['bhk'] == 1 ? '650 Sq.ft' : ($flat['bhk'] == 2 ? '950 Sq.ft' : ($flat['bhk'] == 3 ? '1250 Sq.ft' : '1850 Sq.ft'));
                                        $baths = $flat['bhk'] == 1 ? 1 : ($flat['bhk'] == 2 ? 2 : 3);
                                    ?>
                                    <div class="row g-0 mb-3 text-muted small border rounded py-2 my-2 text-center" style="background-color: #f8fafc; border-color: rgba(16, 49, 120, 0.08) !important; border-radius: 6px;">
                                         <div class="col-4 border-end border-secondary border-opacity-10" style="border-color: rgba(16, 49, 120, 0.08) !important;"><i class="fa-solid fa-ruler-combined me-1.5" style="color: var(--orange-brand) !important;"></i><?php echo $area; ?></div>
                                         <div class="col-4 border-end border-secondary border-opacity-10" style="border-color: rgba(16, 49, 120, 0.08) !important;"><i class="fa-solid fa-bed me-1.5" style="color: var(--orange-brand) !important;"></i><?php echo $flat['bhk']; ?> Bed</div>
                                         <div class="col-4"><i class="fa-solid fa-bath me-1.5" style="color: var(--orange-brand) !important;"></i><?php echo $baths; ?> Bath</div>
                                    </div>

                                    <p class="text-muted small mb-3">
                                        <?php echo htmlspecialchars($flat['description'] ?? 'Designer suite featuring modular finishes, private balconies, and premium smart integrations.'); ?>
                                    </p>
                                    <span class="price-tag">₹<?php echo number_format($flat['price'], 2); ?></span>
                                </div>
                            </div>

                            <!-- Card Footer Action: Book Flat -->
                            <div class="p-4 pt-0 border-0 text-center">
                                <div class="d-flex gap-2">
                                    <a href="resident/view_flat.php?flat_id=<?php echo $flat['id']; ?>" class="btn btn-outline-primary-custom w-50 py-2 fw-semibold" style="border-color:var(--blue-brand); color:var(--blue-brand); border: 1.5px solid var(--blue-brand); border-radius: 4px;">
                                        <i class="fa-solid fa-circle-info me-1"></i> View Details
                                    </a>
                                    <?php if ($flat['status'] === 'Available'): ?>
                                        <a href="resident/checkout.php?flat_id=<?php echo $flat['id']; ?>" class="btn btn-primary-custom w-50 py-2 text-white fw-bold" style="background-color:var(--blue-brand); border-color:var(--blue-brand); border-radius: 4px;">
                                            <i class="fa-solid fa-house-circle-check me-1"></i> Book
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-secondary-custom w-50 py-2" style="border-radius: 4px;" disabled>
                                            Reserved
                                        </button>
                                    <?php endif; ?>
                                </div>

                                <!-- Property Comparison checkbox option -->
                                <div class="form-check mt-3 text-start">
                                    <input class="form-check-input compare-check" type="checkbox" value="<?php echo $flat['id']; ?>" 
                                           data-name="<?php echo htmlspecialchars($flat['block']); ?> - Unit <?php echo $flat['flat_no']; ?>" 
                                           data-price="₹<?php echo number_format($flat['price'], 2); ?>"
                                           data-bhk="<?php echo $flat['bhk']; ?> BHK"
                                           data-city="<?php echo htmlspecialchars($flat['city']); ?>"
                                           id="compare_<?php echo $flat['id']; ?>" onchange="updateCompareCount()">
                                    <label class="form-check-label compare-checkbox-label" for="compare_<?php echo $flat['id']; ?>">
                                        <i class="fa-solid fa-right-left me-1"></i> Add to Compare
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php 
                // Fill remaining white space in 3-column grid row
                if ($rendered_count > 0 && $rendered_count % 3 !== 0): 
                    $needed = 3 - ($rendered_count % 3);
                ?>
                    <?php if ($needed >= 1): ?>
                    <!-- Filler Card 1: Pre-Launch Luxury Penthouse Teaser -->
                    <div class="col-md-6 col-lg-4">
                        <div class="card card-premium h-100 d-flex flex-column justify-content-between position-relative border-warning border-opacity-50" style="background: linear-gradient(180deg, #ffffff 0%, #fffdf9 100%);">
                            <div>
                                <div class="card-image-wrapper position-relative">
                                    <img src="<?php echo $base_path; ?>images/bedroom.png" alt="Pre-Launch Teaser Preview" style="object-fit: cover; filter: brightness(0.92);">
                                    <span class="position-absolute top-0 start-0 m-3 badge bg-dark bg-opacity-80 text-white fw-bold d-flex align-items-center gap-1.5 px-2.5 py-1.5 rounded-pill backdrop-blur shadow-sm z-2" style="font-size:0.68rem; letter-spacing:0.5px; border: 1px solid rgba(242,161,34,0.4);">
                                        <i class="fa-solid fa-crown text-warning" style="font-size: 0.75rem;"></i> JARVIS PRE-LAUNCH
                                    </span>
                                </div>
                                <div class="card-body">
                                    <div class="mb-2 d-flex align-items-center justify-content-between">
                                        <span class="badge bg-warning text-dark fw-bold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;"><i class="fa-solid fa-crown me-1"></i> Priority Access</span>
                                        <span class="badge bg-light text-dark border border-secondary border-opacity-20"><i class="fa-solid fa-clock me-1 text-warning"></i> Phase 2</span>
                                    </div>
                                    <h4 class="fw-bold mb-2" style="font-size:1.15rem; color:var(--blue-brand);">Jarvis Sky Mansions – Phase 2</h4>
                                    <div class="text-muted small mb-2"><i class="fa-solid fa-location-dot me-1 text-danger"></i> Prime Waterfront Precinct</div>
                                    <div class="row g-0 mb-3 text-muted small border rounded py-2 my-2 text-center" style="background-color: #f8fafc; border-color: rgba(16, 49, 120, 0.08) !important;">
                                         <div class="col-4 border-end border-secondary border-opacity-10"><div style="font-size: 0.72rem;"><i class="fa-solid fa-ruler-combined me-1" style="color: var(--orange-brand) !important;"></i>2250 Sq.ft</div></div>
                                         <div class="col-4 border-end border-secondary border-opacity-10"><div style="font-size: 0.72rem;"><i class="fa-solid fa-bed me-1" style="color: var(--orange-brand) !important;"></i>4 Bed</div></div>
                                         <div class="col-4"><div style="font-size: 0.72rem;"><i class="fa-solid fa-swimming-pool me-1" style="color: var(--orange-brand) !important;"></i>Plunge Pool</div></div>
                                    </div>
                                    <p class="text-muted small mb-3">
                                        Exclusive pre-launch expressions of interest now open. Italian marble flooring & private elevator lobby.
                                    </p>
                                    <span class="price-tag" style="font-size: 1.1rem; color: #d97706;">Starting from ₹1.25 Cr</span>
                                </div>
                            </div>
                            <div class="p-4 pt-0 border-0 text-center">
                                <button class="btn btn-warning text-dark w-100 py-2.5 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#inquiryModal" style="background-color: var(--orange-brand); border: none;">
                                    <i class="fa-solid fa-paper-plane me-1.5"></i> Register Pre-Launch EOI
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($needed >= 2): ?>
                    <!-- Filler Card 2: Bespoke VIP Concierge Desk -->
                    <div class="col-md-6 col-lg-4">
                        <div class="card card-premium h-100 d-flex flex-column justify-content-between position-relative border-primary border-opacity-25 shadow-sm" style="background: linear-gradient(145deg, #0f172a 0%, #1e293b 100%); color: #ffffff;">
                            <div class="p-4">
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <div class="position-relative d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: linear-gradient(135deg, #f2a122 0%, #d97706 100%); border-radius: 50%; box-shadow: 0 4px 15px rgba(242, 161, 34, 0.4); flex-shrink: 0;">
                                        <i class="fa-solid fa-crown text-white fs-4"></i>
                                        <span style="position: absolute; top: -1px; right: -1px; width: 10px; height: 10px; background-color: #103178; border: 1.5px solid #ffffff; border-radius: 50%; box-shadow: 0 0 10px rgba(16, 49, 120, 0.5);"></span>
                                    </div>
                                    <div>
                                        <span class="badge bg-warning text-dark fw-bold text-uppercase" style="font-size: 0.65rem; letter-spacing: 1px;"><i class="fa-solid fa-crown me-1"></i> Direct Developer Desk</span>
                                        <h5 class="fw-bold text-white mb-0 mt-1" style="font-size: 1.15rem;">Bespoke VIP Concierge</h5>
                                    </div>
                                </div>
                                <p class="text-white-50 small mb-3" style="line-height: 1.5; font-size: 0.85rem;">
                                    Looking for specific floor levels, custom layouts, or unlisted penthouse inventory? Speak directly with our senior relationship manager.
                                </p>
                                <div class="p-3 rounded-3 bg-dark bg-opacity-50 border border-secondary border-opacity-30 mb-3 small text-white-50">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fa-solid fa-circle-check text-warning me-2"></i>
                                        <span><strong>1-on-1 Virtual Site Tour</strong> & 3D walkthrough</span>
                                    </div>
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fa-solid fa-circle-check text-warning me-2"></i>
                                        <span><strong>Direct Price Lock</strong> & 0% processing fees</span>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <i class="fa-solid fa-circle-check text-warning me-2"></i>
                                        <span><strong>NRI Tax Structuring</strong> & legal consultation</span>
                                    </div>
                                </div>
                            </div>
                            <div class="p-4 pt-0 text-center">
                                <a href="tel:+918056210606" class="btn btn-outline-light w-100 py-2.5 fw-bold mb-2">
                                    <i class="fa-solid fa-phone me-1.5 text-warning"></i> Call +91 80562 10606
                                </a>
                                <button class="btn btn-warning text-dark w-100 py-2.5 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#inquiryModal" style="background-color: var(--orange-brand); border: none;">
                                    <i class="fa-solid fa-calendar-check me-1.5"></i> Request Callback
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>

            <?php else: ?>
                <div class="col-12">
                    <div class="card card-premium p-5 text-center text-muted">
                        <i class="fa-solid fa-circle-xmark fa-3x text-danger mb-3"></i>
                        <h5 class="fw-bold text-dark">No Matching Apartments</h5>
                        <p class="mb-0">There are no apartments registered under this category at the moment.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- 5. AMENITIES SECTION (🏊 Swim, 🏋 Gym, 🚗 Parking, 🌳 Garden, 🛡 Security, 🎮 Play, 📶 Wi-Fi, ⚡ Backup, 🛗 Lift) -->
    <?php if (isset($only_amenities) || !$any_only): ?>
    <section id="amenities" class="container my-5 py-5 bg-white rounded shadow-sm border border-secondary border-opacity-10">
        <div class="text-center mb-5">
            <h3 class="fw-bold text-uppercase" style="letter-spacing: 2px; color: var(--blue-brand);">World-Class Amenities</h3>
            <p class="text-muted">Designed for luxury, comfort, and sustainable living.</p>
        </div>
        <div class="row g-4 text-center">
            <div class="col-md-4 col-6">
                <div class="p-4 border rounded-3 h-100 shadow-hover transition-transform bg-white">
                    <i class="fa-solid fa-person-swimming fa-3x text-warning mb-3"></i>
                    <h5 class="fw-bold mb-1">Swimming Pool</h5>
                    <p class="text-muted small mb-0">Temperature-controlled infinity pool.</p>
                </div>
            </div>
            <div class="col-md-4 col-6">
                <div class="p-4 border rounded-3 h-100 shadow-hover transition-transform bg-white">
                    <i class="fa-solid fa-dumbbell fa-3x text-warning mb-3"></i>
                    <h5 class="fw-bold mb-1">Fitness Gym</h5>
                    <p class="text-muted small mb-0">Fully equipped with cardio & weights.</p>
                </div>
            </div>
            <div class="col-md-4 col-6">
                <div class="p-4 border rounded-3 h-100 shadow-hover transition-transform bg-white">
                    <i class="fa-solid fa-car fa-3x text-warning mb-3"></i>
                    <h5 class="fw-bold mb-1">Parking</h5>
                    <p class="text-muted small mb-0">Covered multi-level slot allocation.</p>
                </div>
            </div>
            <div class="col-md-4 col-6">
                <div class="p-4 border rounded-3 h-100 shadow-hover transition-transform bg-white">
                    <i class="fa-solid fa-tree fa-3x text-warning mb-3"></i>
                    <h5 class="fw-bold mb-1">Garden</h5>
                    <p class="text-muted small mb-0">Landscaped parks and walkways.</p>
                </div>
            </div>
            <div class="col-md-4 col-6">
                <div class="p-4 border rounded-3 h-100 shadow-hover transition-transform bg-white">
                    <i class="fa-solid fa-shield-halved fa-3x text-warning mb-3"></i>
                    <h5 class="fw-bold mb-1">24x7 Security</h5>
                    <p class="text-muted small mb-0">CCTV monitoring & security guards.</p>
                </div>
            </div>
            <div class="col-md-4 col-6">
                <div class="p-4 border rounded-3 h-100 shadow-hover transition-transform bg-white">
                    <i class="fa-solid fa-gamepad fa-3x text-warning mb-3"></i>
                    <h5 class="fw-bold mb-1">Children Play Area</h5>
                    <p class="text-muted small mb-0">Safe children activity playground.</p>
                </div>
            </div>
            <div class="col-md-4 col-6">
                <div class="p-4 border rounded-3 h-100 shadow-hover transition-transform bg-white">
                    <i class="fa-solid fa-wifi fa-3x text-warning mb-3"></i>
                    <h5 class="fw-bold mb-1">High-Speed Wi-Fi</h5>
                    <p class="text-muted small mb-0">Community broadband access.</p>
                </div>
            </div>
            <div class="col-md-4 col-6">
                <div class="p-4 border rounded-3 h-100 shadow-hover transition-transform bg-white">
                    <i class="fa-solid fa-bolt fa-3x text-warning mb-3"></i>
                    <h5 class="fw-bold mb-1">Power Backup</h5>
                    <p class="text-muted small mb-0">100% generator facility support.</p>
                </div>
            </div>
            <div class="col-md-4 col-6">
                <div class="p-4 border rounded-3 h-100 shadow-hover transition-transform bg-white">
                    <i class="fa-solid fa-elevator fa-3x text-warning mb-3"></i>
                    <h5 class="fw-bold mb-1">Elevator Lifts</h5>
                    <p class="text-muted small mb-0">High-speed passenger elevators.</p>
                </div>
            </div>
            <div class="col-md-4 col-6">
                <div class="p-4 border rounded-3 h-100 shadow-hover transition-transform bg-white">
                    <i class="fa-solid fa-hotel fa-3x text-warning mb-3"></i>
                    <h5 class="fw-bold mb-1">Club House</h5>
                    <p class="text-muted small mb-0">Elite lounge and community space.</p>
                </div>
            </div>
            <div class="col-md-4 col-6">
                <div class="p-4 border rounded-3 h-100 shadow-hover transition-transform bg-white">
                    <i class="fa-solid fa-charging-station fa-3x text-warning mb-3"></i>
                    <h5 class="fw-bold mb-1">EV Charging</h5>
                    <p class="text-muted small mb-0">Dedicated electric vehicle docks.</p>
                </div>
            </div>
            <div class="col-md-4 col-6">
                <div class="p-4 border rounded-3 h-100 shadow-hover transition-transform bg-white">
                    <i class="fa-solid fa-fire-extinguisher fa-3x text-warning mb-3"></i>
                    <h5 class="fw-bold mb-1">Fire Safety</h5>
                    <p class="text-muted small mb-0">Modern smoke detectors & alarms.</p>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- 6. GRAND APARTMENT GALLERY SHOWCASE SECTION (WHITE LIGHT LUXURY REDESIGN) -->
    <?php if (isset($only_gallery) || !$any_only): ?>
    <section id="gallery" class="container my-5 p-4 p-md-5 rounded-4 shadow-lg position-relative overflow-hidden" style="background: #ffffff !important; border: 1px solid rgba(0, 0, 0, 0.08); border-left: 6px solid var(--orange-brand) !important;">
        <!-- Ambient Soft Background Bubbles -->
        <div class="position-absolute top-0 end-0 opacity-05" style="width: 400px; height: 400px; background: radial-gradient(circle, var(--orange-brand) 0%, transparent 70%); pointer-events: none;"></div>
        <div class="position-absolute bottom-0 start-0 opacity-05" style="width: 300px; height: 300px; background: radial-gradient(circle, var(--blue-brand) 0%, transparent 70%); pointer-events: none;"></div>

        <!-- Section Header -->
        <div class="text-center mb-5 position-relative z-1">
            <span class="badge bg-warning text-dark fw-bold px-3.5 py-2 mb-3 text-uppercase shadow-sm" style="font-size: 0.75rem; letter-spacing: 2px; background-color: var(--orange-brand) !important;">
                <i class="fa-solid fa-camera-retro me-1.5"></i> Luxury Architectural Showcase
            </span>
            <h2 class="fw-extrabold text-dark text-uppercase display-6 mb-3" style="font-family: 'Playfair Display', serif; letter-spacing: 1px; color: #0f172a !important;">
                Grand Residence Gallery & Visual Tour
            </h2>
            <p class="text-secondary mx-auto" style="max-width: 680px; font-size: 0.95rem; line-height: 1.6; color: #475569 !important;">
                Step inside the opulent living spaces of Jarvis Residences. Experience handcrafted Italian marble interiors, floor-to-ceiling panoramic glass, and world-class resort amenities.
            </p>
        </div>

        <!-- Dynamic Category Filter Pills -->
        <div class="d-flex flex-wrap justify-content-center gap-2 mb-5 position-relative z-1">
            <button type="button" onclick="filterGallery('all', this)" class="btn gallery-filter-btn btn-warning text-dark fw-bold rounded-pill px-4 py-2 text-uppercase shadow-sm active-filter" style="font-size: 0.78rem; letter-spacing: 0.5px;">All Spaces</button>
            <button type="button" onclick="filterGallery('living', this)" class="btn gallery-filter-btn btn-outline-dark rounded-pill px-3.5 py-2 text-uppercase" style="font-size: 0.78rem; letter-spacing: 0.5px;">Living Suites</button>
            <button type="button" onclick="filterGallery('bedroom', this)" class="btn gallery-filter-btn btn-outline-dark rounded-pill px-3.5 py-2 text-uppercase" style="font-size: 0.78rem; letter-spacing: 0.5px;">Master Bedrooms</button>
            <button type="button" onclick="filterGallery('kitchen', this)" class="btn gallery-filter-btn btn-outline-dark rounded-pill px-3.5 py-2 text-uppercase" style="font-size: 0.78rem; letter-spacing: 0.5px;">Modular Kitchens</button>
            <button type="button" onclick="filterGallery('pool', this)" class="btn gallery-filter-btn btn-outline-dark rounded-pill px-3.5 py-2 text-uppercase" style="font-size: 0.78rem; letter-spacing: 0.5px;">Infinity Pool & Spa</button>
            <button type="button" onclick="filterGallery('fitness', this)" class="btn gallery-filter-btn btn-outline-dark rounded-pill px-3.5 py-2 text-uppercase" style="font-size: 0.78rem; letter-spacing: 0.5px;">Fitness Center</button>
        </div>

        <!-- 8-Card High-Definition Masonry Gallery Grid -->
        <div class="row g-4 position-relative z-1 mb-5" id="gallery-grid-container">
            <!-- 1. Luxury Living Area -->
            <div class="col-md-6 col-lg-4 gallery-item-col" data-category="living" style="transition: all 0.3s ease-in-out;">
                <div class="gallery-card-grand position-relative overflow-hidden rounded-3 shadow-md border border-light-subtle" style="height: 290px;">
                    <img src="<?php echo $base_path; ?>images/living.png" class="w-100 h-100 gallery-zoom-img" style="object-fit: cover; transition: transform 0.4s ease-in-out;" alt="Luxury Living Area">
                    <div class="gallery-overlay-gradient position-absolute inset-0 d-flex flex-column justify-content-between p-3.5" style="background: linear-gradient(180deg, rgba(0,0,0,0.05) 0%, rgba(0,0,0,0.85) 100%);">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-warning text-dark fw-bold text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">Living Suite</span>
                            <span class="badge bg-dark bg-opacity-75 text-white border border-secondary border-opacity-30"><i class="fa-solid fa-expand me-1"></i> HD Preview</span>
                        </div>
                        <div>
                            <h5 class="fw-bold text-white mb-1" style="font-size: 1.1rem;">Luxury Living Room</h5>
                            <p class="text-white-50 small mb-0" style="font-size: 0.8rem;">Italian marble flooring & acoustic panoramic glass</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Master Bedroom Suite -->
            <div class="col-md-6 col-lg-4 gallery-item-col" data-category="bedroom" style="transition: all 0.3s ease-in-out;">
                <div class="gallery-card-grand position-relative overflow-hidden rounded-3 shadow-md border border-light-subtle" style="height: 290px;">
                    <img src="<?php echo $base_path; ?>images/bedroom.png" class="w-100 h-100 gallery-zoom-img" style="object-fit: cover; transition: transform 0.4s ease-in-out;" alt="Master Bedroom Suite">
                    <div class="gallery-overlay-gradient position-absolute inset-0 d-flex flex-column justify-content-between p-3.5" style="background: linear-gradient(180deg, rgba(0,0,0,0.05) 0%, rgba(0,0,0,0.85) 100%);">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-warning text-dark fw-bold text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">Master Suite</span>
                            <span class="badge bg-dark bg-opacity-75 text-white border border-secondary border-opacity-30"><i class="fa-solid fa-expand me-1"></i> HD Preview</span>
                        </div>
                        <div>
                            <h5 class="fw-bold text-white mb-1" style="font-size: 1.1rem;">Master Bedroom Suite</h5>
                            <p class="text-white-50 small mb-0" style="font-size: 0.8rem;">Teak wood paneling & private walk-in wardrobe</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. High-End Modular Kitchen -->
            <div class="col-md-6 col-lg-4 gallery-item-col" data-category="kitchen" style="transition: all 0.3s ease-in-out;">
                <div class="gallery-card-grand position-relative overflow-hidden rounded-3 shadow-md border border-light-subtle" style="height: 290px;">
                    <img src="<?php echo $base_path; ?>images/kitchen.png" class="w-100 h-100 gallery-zoom-img" style="object-fit: cover; transition: transform 0.4s ease-in-out;" alt="High-End Modular Kitchen">
                    <div class="gallery-overlay-gradient position-absolute inset-0 d-flex flex-column justify-content-between p-3.5" style="background: linear-gradient(180deg, rgba(0,0,0,0.05) 0%, rgba(0,0,0,0.85) 100%);">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-warning text-dark fw-bold text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">Kitchen</span>
                            <span class="badge bg-dark bg-opacity-75 text-white border border-secondary border-opacity-30"><i class="fa-solid fa-expand me-1"></i> HD Preview</span>
                        </div>
                        <div>
                            <h5 class="fw-bold text-white mb-1" style="font-size: 1.1rem;">German Modular Kitchen</h5>
                            <p class="text-white-50 small mb-0" style="font-size: 0.8rem;">Quartz countertops & built-in Hafele appliances</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Infinity Swimming Pool -->
            <div class="col-md-6 col-lg-4 gallery-item-col" data-category="pool" style="transition: all 0.3s ease-in-out;">
                <div class="gallery-card-grand position-relative overflow-hidden rounded-3 shadow-md border border-light-subtle" style="height: 290px;">
                    <img src="<?php echo $base_path; ?>images/pool.png" class="w-100 h-100 gallery-zoom-img" style="object-fit: cover; transition: transform 0.4s ease-in-out;" alt="Rooftop Infinity Swimming Pool">
                    <div class="gallery-overlay-gradient position-absolute inset-0 d-flex flex-column justify-content-between p-3.5" style="background: linear-gradient(180deg, rgba(0,0,0,0.05) 0%, rgba(0,0,0,0.85) 100%);">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-info text-dark fw-bold text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">Resort Amenity</span>
                            <span class="badge bg-dark bg-opacity-75 text-white border border-secondary border-opacity-30"><i class="fa-solid fa-expand me-1"></i> HD Preview</span>
                        </div>
                        <div>
                            <h5 class="fw-bold text-white mb-1" style="font-size: 1.1rem;">Rooftop Infinity Pool</h5>
                            <p class="text-white-50 small mb-0" style="font-size: 0.8rem;">Temperature-controlled water with skyline deck lounge</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. Modern Fitness Center -->
            <div class="col-md-6 col-lg-4 gallery-item-col" data-category="fitness" style="transition: all 0.3s ease-in-out;">
                <div class="gallery-card-grand position-relative overflow-hidden rounded-3 shadow-md border border-light-subtle" style="height: 290px;">
                    <img src="<?php echo $base_path; ?>images/gym.png" class="w-100 h-100 gallery-zoom-img" style="object-fit: cover; transition: transform 0.4s ease-in-out;" alt="Modern Fitness Center">
                    <div class="gallery-overlay-gradient position-absolute inset-0 d-flex flex-column justify-content-between p-3.5" style="background: linear-gradient(180deg, rgba(0,0,0,0.05) 0%, rgba(0,0,0,0.85) 100%);">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-success text-white fw-bold text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">Fitness</span>
                            <span class="badge bg-dark bg-opacity-75 text-white border border-secondary border-opacity-30"><i class="fa-solid fa-expand me-1"></i> HD Preview</span>
                        </div>
                        <div>
                            <h5 class="fw-bold text-white mb-1" style="font-size: 1.1rem;">Health & Fitness Club</h5>
                            <p class="text-white-50 small mb-0" style="font-size: 0.8rem;">Technogym equipment, yoga studio & sauna room</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 6. Grand Entrance Lobby -->
            <div class="col-md-6 col-lg-4 gallery-item-col" data-category="living" style="transition: all 0.3s ease-in-out;">
                <div class="gallery-card-grand position-relative overflow-hidden rounded-3 shadow-md border border-light-subtle" style="height: 290px;">
                    <img src="<?php echo $base_path; ?>images/lobby.png" class="w-100 h-100 gallery-zoom-img" style="object-fit: cover; transition: transform 0.4s ease-in-out;" alt="Grand Entrance Lobby">
                    <div class="gallery-overlay-gradient position-absolute inset-0 d-flex flex-column justify-content-between p-3.5" style="background: linear-gradient(180deg, rgba(0,0,0,0.05) 0%, rgba(0,0,0,0.85) 100%);">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-warning text-dark fw-bold text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">Concierge</span>
                            <span class="badge bg-dark bg-opacity-75 text-white border border-secondary border-opacity-30"><i class="fa-solid fa-expand me-1"></i> HD Preview</span>
                        </div>
                        <div>
                            <h5 class="fw-bold text-white mb-1" style="font-size: 1.1rem;">Grand Entrance Lobby</h5>
                            <p class="text-white-50 small mb-0" style="font-size: 0.8rem;">Triple-height atrium with 24/7 concierge reception desk</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
        function filterGallery(category, clickedBtn) {
            // Update button active styling
            const buttons = document.querySelectorAll('.gallery-filter-btn');
            buttons.forEach(btn => {
                btn.classList.remove('btn-warning', 'text-dark', 'fw-bold', 'shadow-sm', 'active-filter');
                btn.classList.add('btn-outline-dark');
            });

            clickedBtn.classList.remove('btn-outline-dark');
            clickedBtn.classList.add('btn-warning', 'text-dark', 'fw-bold', 'shadow-sm', 'active-filter');

            // Filter gallery grid items
            const items = document.querySelectorAll('.gallery-item-col');
            items.forEach(item => {
                const itemCat = item.getAttribute('data-category');
                if (category === 'all' || itemCat === category) {
                    item.style.display = 'block';
                    setTimeout(() => {
                        item.style.opacity = '1';
                        item.style.transform = 'scale(1)';
                    }, 10);
                } else {
                    item.style.opacity = '0';
                    item.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        item.style.display = 'none';
                    }, 250);
                }
            });
        }
        </script>

        <!-- Architectural Statistics Bar -->
        <div class="p-4 rounded-3 bg-light border border-light-subtle position-relative z-1 shadow-sm">
            <div class="row g-3 text-center text-dark">
                <div class="col-6 col-md-3">
                    <h3 class="fw-extrabold text-warning mb-0" style="font-size: 1.8rem; color: #d97706 !important;">1.5 M Sq.Ft</h3>
                    <span class="text-muted small text-uppercase" style="font-size: 0.7rem; letter-spacing: 1px;">Constructed Area</span>
                </div>
                <div class="col-6 col-md-3">
                    <h3 class="fw-extrabold text-warning mb-0" style="font-size: 1.8rem; color: #d97706 !important;">80% Green</h3>
                    <span class="text-muted small text-uppercase" style="font-size: 0.7rem; letter-spacing: 1px;">Open Landscaped Area</span>
                </div>
                <div class="col-6 col-md-3">
                    <h3 class="fw-extrabold text-warning mb-0" style="font-size: 1.8rem; color: #d97706 !important;">IGBC Gold</h3>
                    <span class="text-muted small text-uppercase" style="font-size: 0.7rem; letter-spacing: 1px;">Certified Green Building</span>
                </div>
                <div class="col-6 col-md-3">
                    <h3 class="fw-extrabold text-warning mb-0" style="font-size: 1.8rem; color: #d97706 !important;">24x7 Smart</h3>
                    <span class="text-muted small text-uppercase" style="font-size: 0.7rem; letter-spacing: 1px;">Automated Security</span>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- 7. FLOOR PLANS & 8. PRICING PLANS (WHITE LIGHT LUXURY REDESIGN) -->
    <?php if (isset($only_pricing) || !$any_only): ?>
    <section id="pricing" class="container my-5 p-4 p-md-5 rounded-4 shadow-lg position-relative overflow-hidden" style="background: #ffffff !important; border: 1px solid rgba(0, 0, 0, 0.08); border-left: 6px solid var(--orange-brand) !important;">
        <!-- Ambient Soft Background Bubbles -->
        <div class="position-absolute top-0 start-0 opacity-05" style="width: 350px; height: 350px; background: radial-gradient(circle, var(--orange-brand) 0%, transparent 70%); pointer-events: none;"></div>
        <div class="position-absolute bottom-0 end-0 opacity-05" style="width: 350px; height: 350px; background: radial-gradient(circle, var(--blue-brand) 0%, transparent 70%); pointer-events: none;"></div>

        <!-- Section Header -->
        <div class="text-center mb-5 position-relative z-1">
            <span class="badge bg-warning text-dark fw-bold px-3.5 py-2 mb-3 text-uppercase shadow-sm" style="font-size: 0.75rem; letter-spacing: 2px; background-color: var(--orange-brand) !important;">
                <i class="fa-solid fa-drafting-compass me-1.5"></i> Official Architectural Schematics
            </span>
            <h2 class="fw-extrabold text-dark text-uppercase display-6 mb-3" style="font-family: 'Playfair Display', serif; letter-spacing: 1px; color: #0f172a !important;">
                Architectural Floor Plans & Transparent Pricing
            </h2>
            <p class="text-secondary mx-auto" style="max-width: 680px; font-size: 0.95rem; line-height: 1.6; color: #475569 !important;">
                Explore modular structural floor layouts engineered for optimal natural ventilation, zero space wastage, and transparent upfront pricing without hidden charges.
            </p>
        </div>

        <div class="row g-5 position-relative z-1 mb-4">
            <!-- Left Column: Floor Plans -->
            <div class="col-lg-6">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold text-dark mb-0" style="color: #0f172a !important;"><i class="fa-solid fa-draw-polygon text-warning me-2"></i>Interactive Floor Plans</h4>
                    <span class="badge bg-light text-warning border border-warning px-2.5 py-1">HD Vector Blueprints</span>
                </div>
                <p class="text-secondary small mb-4" style="color: #475569 !important;">Switch between layout configurations to preview carpet dimensions & room allocations.</p>
                
                <!-- Floor Plan Tabs Switcher -->
                <ul class="nav nav-pills mb-3 border-bottom border-light-subtle pb-2.5 gap-2" id="floorPlanTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold text-uppercase px-3.5 py-2 rounded-pill" id="bhk1-tab" data-bs-toggle="pill" data-bs-target="#bhk1" type="button" role="tab" aria-controls="bhk1" aria-selected="true" style="font-size: 0.8rem;">1 BHK Plan</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-uppercase px-3.5 py-2 rounded-pill text-dark" id="bhk2-tab" data-bs-toggle="pill" data-bs-target="#bhk2" type="button" role="tab" aria-controls="bhk2" aria-selected="false" style="font-size: 0.8rem;">2 BHK Plan</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-uppercase px-3.5 py-2 rounded-pill text-dark" id="bhk3-tab" data-bs-toggle="pill" data-bs-target="#bhk3" type="button" role="tab" aria-controls="bhk3" aria-selected="false" style="font-size: 0.8rem;">3 BHK Plan</button>
                    </li>
                </ul>

                <!-- High-Tech Blueprint Canvas -->
                <div class="tab-content border border-light-subtle p-4 rounded-3 bg-light text-dark shadow-sm" id="floorPlanTabContent">
                    <div class="tab-pane fade show active text-center" id="bhk1" role="tabpanel" aria-labelledby="bhk1-tab">
                        <svg viewBox="0 0 100 80" class="w-100 mx-auto" style="max-height: 250px;">
                            <rect x="5" y="5" width="90" height="70" fill="#f8fafc" stroke="#d97706" stroke-width="1.2" rx="3"/>
                            <line x1="50" y1="5" x2="50" y2="75" stroke="#cbd5e1" stroke-width="0.8" stroke-dasharray="2"/>
                            <!-- Living Room -->
                            <text x="25" y="24" font-family="'Font Awesome 6 Free'" font-weight="900" font-size="6.5" fill="#d97706" text-anchor="middle">&#xf4b8;</text>
                            <text x="25" y="33" font-size="4" fill="#0f172a" font-weight="bold" text-anchor="middle">LIVING ROOM</text>
                            <text x="25" y="41" font-size="3.2" fill="#64748b" text-anchor="middle">20' x 14' (280 Sq.Ft)</text>
                            <!-- Bedroom -->
                            <text x="75" y="24" font-family="'Font Awesome 6 Free'" font-weight="900" font-size="6.5" fill="#0284c7" text-anchor="middle">&#xf236;</text>
                            <text x="75" y="33" font-size="4" fill="#0f172a" font-weight="bold" text-anchor="middle">BEDROOM</text>
                            <text x="75" y="41" font-size="3.2" fill="#64748b" text-anchor="middle">16' x 12' (192 Sq.Ft)</text>
                            <!-- Kitchen -->
                            <text x="25" y="58" font-family="'Font Awesome 6 Free'" font-weight="900" font-size="5" fill="#16a34a" text-anchor="middle">&#xf2e7;</text>
                            <text x="25" y="66" font-size="3.6" fill="#334155" font-weight="bold" text-anchor="middle">KITCHEN</text>
                            <!-- Bath -->
                            <text x="75" y="58" font-family="'Font Awesome 6 Free'" font-weight="900" font-size="5" fill="#e11d48" text-anchor="middle">&#xf2cd;</text>
                            <text x="75" y="66" font-size="3.6" fill="#334155" font-weight="bold" text-anchor="middle">BATHROOM</text>
                        </svg>
                        <div class="mt-3 d-flex justify-content-between align-items-center bg-white p-2.5 rounded border border-light-subtle shadow-xs">
                            <span class="small text-secondary"><i class="fa-solid fa-ruler-combined text-warning me-1.5"></i>1 BHK Suite Carpet Area:</span>
                            <strong class="text-warning fs-6" style="color: #d97706 !important;">650 Sq.Ft Total</strong>
                        </div>
                    </div>

                    <div class="tab-pane fade text-center" id="bhk2" role="tabpanel" aria-labelledby="bhk2-tab">
                        <svg viewBox="0 0 100 80" class="w-100 mx-auto" style="max-height: 250px;">
                            <rect x="5" y="5" width="90" height="70" fill="#f8fafc" stroke="#d97706" stroke-width="1.2" rx="3"/>
                            <line x1="50" y1="5" x2="50" y2="75" stroke="#cbd5e1" stroke-width="0.8" stroke-dasharray="2"/>
                            <line x1="5" y1="40" x2="95" y2="40" stroke="#cbd5e1" stroke-width="0.8" stroke-dasharray="2"/>
                            <!-- Living Room -->
                            <text x="25" y="19" font-family="'Font Awesome 6 Free'" font-weight="900" font-size="5.5" fill="#d97706" text-anchor="middle">&#xf4b8;</text>
                            <text x="25" y="27" font-size="3.8" fill="#0f172a" font-weight="bold" text-anchor="middle">LIVING ROOM</text>
                            <!-- Master Bedroom -->
                            <text x="75" y="19" font-family="'Font Awesome 6 Free'" font-weight="900" font-size="5.5" fill="#0284c7" text-anchor="middle">&#xf236;</text>
                            <text x="75" y="27" font-size="3.6" fill="#0f172a" font-weight="bold" text-anchor="middle">MASTER BEDROOM</text>
                            <!-- Bedroom 2 -->
                            <text x="25" y="54" font-family="'Font Awesome 6 Free'" font-weight="900" font-size="5.5" fill="#0284c7" text-anchor="middle">&#xf236;</text>
                            <text x="25" y="62" font-size="3.8" fill="#0f172a" font-weight="bold" text-anchor="middle">BEDROOM 2</text>
                            <!-- Kitchen & Bath -->
                            <text x="70" y="54" font-family="'Font Awesome 6 Free'" font-weight="900" font-size="4.8" fill="#16a34a" text-anchor="middle">&#xf2e7;</text>
                            <text x="80" y="54" font-family="'Font Awesome 6 Free'" font-weight="900" font-size="4.8" fill="#e11d48" text-anchor="middle">&#xf2cd;</text>
                            <text x="75" y="62" font-size="3.4" fill="#334155" font-weight="bold" text-anchor="middle">KITCHEN & BATH</text>
                        </svg>
                        <div class="mt-3 d-flex justify-content-between align-items-center bg-white p-2.5 rounded border border-light-subtle shadow-xs">
                            <span class="small text-secondary"><i class="fa-solid fa-ruler-combined text-warning me-1.5"></i>2 BHK Deluxe Carpet Area:</span>
                            <strong class="text-warning fs-6" style="color: #d97706 !important;">950 Sq.Ft Total</strong>
                        </div>
                    </div>

                    <div class="tab-pane fade text-center" id="bhk3" role="tabpanel" aria-labelledby="bhk3-tab">
                        <svg viewBox="0 0 100 80" class="w-100 mx-auto" style="max-height: 250px;">
                            <rect x="5" y="5" width="90" height="70" fill="#f8fafc" stroke="#d97706" stroke-width="1.2" rx="3"/>
                            <line x1="33" y1="5" x2="33" y2="75" stroke="#cbd5e1" stroke-width="0.8" stroke-dasharray="2"/>
                            <line x1="66" y1="5" x2="66" y2="75" stroke="#cbd5e1" stroke-width="0.8" stroke-dasharray="2"/>
                            <line x1="5" y1="40" x2="95" y2="40" stroke="#cbd5e1" stroke-width="0.8" stroke-dasharray="2"/>
                            <!-- Living Room -->
                            <text x="19" y="20" font-family="'Font Awesome 6 Free'" font-weight="900" font-size="5" fill="#d97706" text-anchor="middle">&#xf4b8;</text>
                            <text x="19" y="29" font-size="3.2" fill="#0f172a" font-weight="bold" text-anchor="middle">LIVING ROOM</text>
                            <!-- Master Bed -->
                            <text x="50" y="20" font-family="'Font Awesome 6 Free'" font-weight="900" font-size="5" fill="#0284c7" text-anchor="middle">&#xf236;</text>
                            <text x="50" y="29" font-size="3.2" fill="#0f172a" font-weight="bold" text-anchor="middle">MASTER BED</text>
                            <!-- Bedroom 2 -->
                            <text x="82" y="20" font-family="'Font Awesome 6 Free'" font-weight="900" font-size="5" fill="#0284c7" text-anchor="middle">&#xf236;</text>
                            <text x="82" y="29" font-size="3.2" fill="#0f172a" font-weight="bold" text-anchor="middle">BEDROOM 2</text>
                            <!-- Bedroom 3 -->
                            <text x="19" y="55" font-family="'Font Awesome 6 Free'" font-weight="900" font-size="5" fill="#0284c7" text-anchor="middle">&#xf236;</text>
                            <text x="19" y="64" font-size="3.2" fill="#0f172a" font-weight="bold" text-anchor="middle">BEDROOM 3</text>
                            <!-- Kitchen -->
                            <text x="50" y="55" font-family="'Font Awesome 6 Free'" font-weight="900" font-size="5" fill="#16a34a" text-anchor="middle">&#xf2e7;</text>
                            <text x="50" y="64" font-size="3.2" fill="#334155" font-weight="bold" text-anchor="middle">KITCHEN</text>
                            <!-- Balcony -->
                            <text x="82" y="55" font-family="'Font Awesome 6 Free'" font-weight="900" font-size="5" fill="#d97706" text-anchor="middle">&#xf06c;</text>
                            <text x="82" y="64" font-size="3.2" fill="#334155" font-weight="bold" text-anchor="middle">BALCONY</text>
                        </svg>
                        <div class="mt-3 d-flex justify-content-between align-items-center bg-white p-2.5 rounded border border-light-subtle shadow-xs">
                            <span class="small text-secondary"><i class="fa-solid fa-ruler-combined text-warning me-1.5"></i>3 BHK Luxury Carpet Area:</span>
                            <strong class="text-warning fs-6" style="color: #d97706 !important;">1,250 Sq.Ft Total</strong>
                        </div>
                    </div>
                </div>
                <div class="mt-3 text-center">
                    <a href="<?php echo $base_path; ?>Jarvis_Premium_Residences_Map.pdf" download="Jarvis_Premium_Residences_Map.pdf" class="btn btn-warning text-dark fw-bold px-4 py-2 rounded-pill shadow-sm"><i class="fa-solid fa-file-pdf text-danger me-2"></i> Download Official High-Res Floorplans (PDF)</a>
                </div>
            </div>

            <!-- Right Column: Pricing Plan Cards Grid -->
            <div class="col-lg-6">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold text-dark mb-0" style="color: #0f172a !important;"><i class="fa-solid fa-tags text-warning me-2"></i>Transparent Pricing Table</h4>
                    <span class="badge bg-success text-white fw-bold">Zero Hidden Charges</span>
                </div>
                <p class="text-secondary small mb-4" style="color: #475569 !important;">Upfront direct developer pricing with attractive bank loan EMI interest rates.</p>

                <!-- High-Contrast Light Luxury Pricing Table -->
                <div class="table-responsive rounded-3 overflow-hidden border border-light-subtle shadow-sm mb-4">
                    <table class="table table-light table-hover align-middle text-center mb-0" style="background: #ffffff !important;">
                        <thead>
                            <tr class="text-dark text-uppercase border-bottom border-light-subtle" style="font-size: 0.8rem; letter-spacing: 1px; background-color: #f1f5f9 !important;">
                                <th class="py-3 text-start ps-3 text-dark">Flat Configuration</th>
                                <th class="py-3 text-dark">Carpet Size</th>
                                <th class="py-3 text-dark">Est. Bank EMI</th>
                                <th class="py-3 text-end pe-3 text-dark">Starting Price</th>
                            </tr>
                        </thead>
                        <tbody style="font-size: 0.9rem;">
                            <tr>
                                <td class="py-3 text-start ps-3 fw-bold text-dark">
                                    <i class="fa-solid fa-hotel text-warning me-2"></i>1 BHK Suite
                                </td>
                                <td class="py-3 text-secondary">650 Sq.Ft</td>
                                <td class="py-3 text-primary fw-bold">₹17,350/mo</td>
                                <td class="py-3 text-end pe-3"><span class="badge bg-warning text-dark fs-6 font-bold">₹25 Lakhs</span></td>
                            </tr>
                            <tr>
                                <td class="py-3 text-start ps-3 fw-bold text-dark">
                                    <i class="fa-solid fa-building text-warning me-2"></i>2 BHK Deluxe
                                </td>
                                <td class="py-3 text-secondary">950 Sq.Ft</td>
                                <td class="py-3 text-primary fw-bold">₹29,150/mo</td>
                                <td class="py-3 text-end pe-3"><span class="badge bg-warning text-dark fs-6 font-bold">₹42 Lakhs</span></td>
                            </tr>
                            <tr>
                                <td class="py-3 text-start ps-3 fw-bold text-dark">
                                    <i class="fa-solid fa-crown text-warning me-2"></i>3 BHK Ultra-Luxury
                                </td>
                                <td class="py-3 text-secondary">1,250 Sq.Ft</td>
                                <td class="py-3 text-primary fw-bold">₹41,650/mo</td>
                                <td class="py-3 text-end pe-3"><span class="badge bg-warning text-dark fs-6 font-bold">₹60 Lakhs</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Custom Layouts & Launch Benefit Callout Box -->
                <div class="p-4 rounded-3 bg-light border border-light-subtle text-center shadow-xs">
                    <span class="badge bg-danger text-white fw-bold mb-2 text-uppercase" style="font-size: 0.68rem; letter-spacing: 1px;"><i class="fa-solid fa-fire me-1"></i> Special Launch Benefit</span>
                    <h5 class="fw-bold text-dark mb-2" style="color: #0f172a !important;">Looking for Customized Penthouse Layouts?</h5>
                    <p class="small text-secondary mb-3" style="line-height: 1.5; color: #475569 !important;">Book this week to enjoy <strong>0% Processing Fee</strong>, free reserved basement EV parking slot, and personalized interior customization.</p>
                    <div class="d-flex flex-wrap justify-content-center gap-2">
                        <a href="flats.php" class="btn btn-warning text-dark fw-bold px-4 py-2.5 rounded-pill shadow-sm" style="background-color: var(--orange-brand); border: none;">
                            <i class="fa-solid fa-compass me-2"></i> Explore Available Catalog Units
                        </a>
                        <a href="#contact" class="btn btn-outline-dark font-semibold px-4 py-2.5 rounded-pill">
                            <i class="fa-solid fa-phone me-2"></i> Speak with Relationship Manager
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- 10. ABOUT BUILDER & PROJECT (WHITE LIGHT LUXURY REDESIGN) -->
    <?php if (isset($only_about) || !$any_only): 
        $sec_badge_text    = get_setting('about_badge_text', 'Architectural Heritage & Builder Legacy');
        $sec_heading_text  = get_setting('about_heading_text', 'About Jarvis Residences & Developer Legacy');
        $sec_desc_text     = get_setting('about_desc_text', 'Jarvis Residences represents the pinnacle of smart-home architectural construction, engineered for families seeking modular luxury comfort, eco-friendly green systems, and strategic urban access.');
        $sec_builder_name  = get_setting('about_builder_name', 'Jarvis Real Estate Developers');
        $sec_builder_badge = get_setting('about_builder_badge', 'ICRA (A+ Stable) Certified');
        $sec_builder_bio   = get_setting('about_builder_bio', "Developed by Jarvis Real Estate Developers, India's leading luxury home builder certified by ICRA (A+ Stable). Over 25 years of engineering excellence with more than 32 Million Square Feet of delivered residential space across South India.");
        $sec_stat1_val     = get_setting('about_stat1_val', '32M+');
        $sec_stat1_lbl     = get_setting('about_stat1_lbl', 'Sq.Ft Delivered');
        $sec_stat2_val     = get_setting('about_stat2_val', '15,000+');
        $sec_stat2_lbl     = get_setting('about_stat2_lbl', 'Happy Families');
        $sec_stat3_val     = get_setting('about_stat3_val', '28+');
        $sec_stat3_lbl     = get_setting('about_stat3_lbl', 'National Awards');
        $sec_stat4_val     = get_setting('about_stat4_val', '100%');
        $sec_stat4_lbl     = get_setting('about_stat4_lbl', 'RERA Approved');
    ?>
    <section id="about" class="container my-5 p-4 p-md-5 rounded-4 shadow-lg position-relative overflow-hidden" style="background: #ffffff !important; border: 1px solid rgba(0, 0, 0, 0.08); border-left: 6px solid var(--orange-brand) !important;">
        <!-- Ambient Soft Background Bubbles -->
        <div class="position-absolute top-0 start-0 opacity-05" style="width: 400px; height: 400px; background: radial-gradient(circle, var(--orange-brand) 0%, transparent 70%); pointer-events: none;"></div>
        <div class="position-absolute bottom-0 end-0 opacity-05" style="width: 400px; height: 400px; background: radial-gradient(circle, var(--blue-brand) 0%, transparent 70%); pointer-events: none;"></div>

        <!-- Section Header -->
        <div class="text-center mb-5 position-relative z-1">
            <span class="badge bg-warning text-dark fw-bold px-3.5 py-2 mb-3 text-uppercase shadow-sm" style="font-size: 0.75rem; letter-spacing: 2px; background-color: var(--orange-brand) !important;">
                <i class="fa-solid fa-building-user me-1.5"></i> <?php echo htmlspecialchars($sec_badge_text); ?>
            </span>
            <h2 class="fw-extrabold text-dark text-uppercase display-6 mb-3" style="font-family: var(--font-heading, 'Playfair Display', serif); letter-spacing: 1px; color: #0f172a !important;">
                <?php echo htmlspecialchars($sec_heading_text); ?>
            </h2>
            <p class="text-secondary mx-auto" style="max-width: 680px; font-size: 0.95rem; line-height: 1.6; color: #475569 !important;">
                <?php echo nl2br(htmlspecialchars($sec_desc_text)); ?>
            </p>
        </div>

        <!-- Builder Information Card -->
        <div class="p-4 rounded-3 border mb-5 position-relative z-1 shadow-sm" style="background: #f8fafc !important; border: 1px solid rgba(242, 161, 34, 0.4) !important; border-left: 6px solid var(--orange-brand) !important;">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <div class="d-flex align-items-center mb-3">
                        <div class="d-flex align-items-center justify-content-center rounded-circle me-3" style="width: 52px; height: 52px; background: rgba(242, 161, 34, 0.15); border: 1.5px solid rgba(242, 161, 34, 0.4); flex-shrink: 0;">
                            <i class="fa-solid fa-building-flag" style="font-size: 1.35rem; color: var(--orange-brand);"></i>
                        </div>
                        <div>
                            <span class="badge bg-warning text-dark fw-bold text-uppercase" style="font-size: 0.68rem; letter-spacing: 1px;"><?php echo htmlspecialchars($sec_builder_badge); ?></span>
                            <h4 class="fw-bold text-dark mb-0 mt-1" style="color: #0f172a !important; font-family: var(--font-heading, inherit);"><?php echo htmlspecialchars($sec_builder_name); ?></h4>
                        </div>
                    </div>
                    <p class="text-secondary mb-0" style="line-height: 1.65; font-size: 0.92rem; color: #475569 !important;">
                        <?php echo nl2br(htmlspecialchars($sec_builder_bio)); ?>
                    </p>
                </div>
                <div class="col-lg-4">
                    <div class="row g-2 text-center">
                        <div class="col-6">
                            <div class="p-2.5 rounded bg-white border border-light-subtle shadow-xs">
                                <h4 class="fw-extrabold text-warning mb-0" style="font-size: 1.4rem; color: var(--orange-brand) !important;"><?php echo htmlspecialchars($sec_stat1_val); ?></h4>
                                <span class="text-muted small" style="font-size: 0.72rem;"><?php echo htmlspecialchars($sec_stat1_lbl); ?></span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2.5 rounded bg-white border border-light-subtle shadow-xs">
                                <h4 class="fw-extrabold text-warning mb-0" style="font-size: 1.4rem; color: var(--orange-brand) !important;"><?php echo htmlspecialchars($sec_stat2_val); ?></h4>
                                <span class="text-muted small" style="font-size: 0.72rem;"><?php echo htmlspecialchars($sec_stat2_lbl); ?></span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2.5 rounded bg-white border border-light-subtle shadow-xs">
                                <h4 class="fw-extrabold text-warning mb-0" style="font-size: 1.4rem; color: var(--orange-brand) !important;"><?php echo htmlspecialchars($sec_stat3_val); ?></h4>
                                <span class="text-muted small" style="font-size: 0.72rem;"><?php echo htmlspecialchars($sec_stat3_lbl); ?></span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2.5 rounded bg-white border border-light-subtle shadow-xs">
                                <h4 class="fw-extrabold text-warning mb-0" style="font-size: 1.4rem; color: var(--orange-brand) !important;"><?php echo htmlspecialchars($sec_stat4_val); ?></h4>
                                <span class="text-muted small" style="font-size: 0.72rem;"><?php echo htmlspecialchars($sec_stat4_lbl); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Location Advantages Cards Grid (Grand Luxury Redesign) -->
        <div class="mb-5 position-relative z-1">
            <div class="mb-4">
                <span class="badge bg-warning text-dark fw-bold text-uppercase px-3 py-1.5 rounded-pill mb-2 shadow-xs" style="font-size:0.7rem; letter-spacing:1.5px; background:var(--orange-brand) !important;">
                    <i class="fa-solid fa-compass me-1"></i> Urban Connectivity & Proximity
                </span>
                <h3 class="fw-extrabold text-dark display-6 mb-1" style="font-family: 'Playfair Display', serif; color: #0f172a !important; letter-spacing: 0.5px;">
                    Strategic Location Advantages
                </h3>
                <p class="text-secondary small mb-0" style="color: #475569 !important;">Direct access to major transit hubs, premier educational campuses, and express highways.</p>
            </div>

            <div class="row g-4">
                <!-- 1. Rapid Transit Access -->
                <div class="col-md-4">
                    <div class="p-4 border rounded-4 bg-white h-100 shadow-sm shadow-hover transition-transform text-dark position-relative overflow-hidden" style="border-color: rgba(16, 49, 120, 0.12) !important; border-top: 4px solid var(--orange-brand) !important; background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-20 px-2.5 py-1 rounded-pill small fw-semibold">
                                5 Mins Walk
                            </span>
                        </div>
                        <h5 class="fw-bold text-dark mb-2" style="font-size: 1.1rem; color: #0f172a !important; font-family: 'Playfair Display', serif;">Rapid Transit Access</h5>
                        <p class="text-secondary small mb-0" style="line-height: 1.6; color: #475569 !important;">5 Mins walk to <strong>Metro Station</strong> and Central Bus Terminal. Direct link to Suburban Rail & Airport networks.</p>
                    </div>
                </div>

                <!-- 2. Educational Corridor -->
                <div class="col-md-4">
                    <div class="p-4 border rounded-4 bg-white h-100 shadow-sm shadow-hover transition-transform text-dark position-relative overflow-hidden" style="border-color: rgba(16, 49, 120, 0.12) !important; border-top: 4px solid var(--blue-brand) !important; background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-20 px-2.5 py-1 rounded-pill small fw-semibold">
                                Premier Hub
                            </span>
                        </div>
                        <h5 class="fw-bold text-dark mb-2" style="font-size: 1.1rem; color: #0f172a !important; font-family: 'Playfair Display', serif;">Education Hub</h5>
                        <p class="text-secondary small mb-0" style="line-height: 1.6; color: #475569 !important;">Immediate proximity to <strong>Madras Christian College (MCC)</strong>, Corley High School, and top International Play Schools.</p>
                    </div>
                </div>

                <!-- 3. Arterial Connectivity -->
                <div class="col-md-4">
                    <div class="p-4 border rounded-4 bg-white h-100 shadow-sm shadow-hover transition-transform text-dark position-relative overflow-hidden" style="border-color: rgba(16, 49, 120, 0.12) !important; border-top: 4px solid var(--orange-brand) !important; background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="badge bg-warning bg-opacity-15 text-dark border border-warning border-opacity-30 px-2.5 py-1 rounded-pill small fw-semibold">
                                Direct Highway
                            </span>
                        </div>
                        <h5 class="fw-bold text-dark mb-2" style="font-size: 1.1rem; color: #0f172a !important; font-family: 'Playfair Display', serif;">Arterial Connectivity</h5>
                        <p class="text-secondary small mb-0" style="line-height: 1.6; color: #475569 !important;">Connected directly to <strong>Outer Ring Road</strong>, GST National Highway, and OMR IT Express Corridor networks.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- External Building Features & Infrastructure Grid (Grand Engineering Redesign) -->
        <div class="position-relative z-1 mb-5">
            <div class="mb-4 text-center">
                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-3 py-1.5 rounded-pill mb-2 fw-semibold" style="font-size: 0.78rem;">
                    <i class="fa-solid fa-microchip me-1"></i> High-Tech Engineering Standards
                </span>
                <h3 class="fw-extrabold text-dark display-6 mb-1" style="font-family: 'Playfair Display', serif; color: #0f172a !important; letter-spacing: 0.5px;">
                    External Building & Infrastructure Specifications
                </h3>
                <p class="text-secondary small mb-0" style="color: #475569 !important;">Industrial-grade building systems designed for eco-sustainability, security, and continuous power reliability.</p>
            </div>

            <div class="row g-4">
                <!-- 1. Solar Energy Grid -->
                <div class="col-md-4">
                    <div class="p-4 border rounded-3 bg-white h-100 shadow-sm shadow-hover transition-transform text-dark position-relative border-start border-4 border-warning" style="border-color: rgba(16, 49, 120, 0.1) !important; border-left-color: var(--orange-brand) !important;">
                        <h6 class="fw-bold text-dark mb-2" style="font-size: 1.05rem; color: #0f172a !important;">Solar Energy Grid</h6>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-20 mb-2 small" style="font-size: 0.68rem;">100% Eco Solar</span>
                        <p class="text-secondary small mb-0" style="line-height: 1.5; color: #475569 !important;">Rooftop solar installation powering common illumination & elevator backup.</p>
                    </div>
                </div>
                <!-- 2. Water Treatment Plant -->
                <div class="col-md-4">
                    <div class="p-4 border rounded-3 bg-white h-100 shadow-sm shadow-hover transition-transform text-dark position-relative border-start border-4 border-info" style="border-color: rgba(16, 49, 120, 0.1) !important; border-left-color: #0ea5e9 !important;">
                        <h6 class="fw-bold text-dark mb-2" style="font-size: 1.05rem; color: #0f172a !important;">Water Treatment Plant</h6>
                        <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-20 mb-2 small" style="font-size: 0.68rem;">Zero Waste STP</span>
                        <p class="text-secondary small mb-0" style="line-height: 1.5; color: #475569 !important;">Rainwater harvesting with 24x7 hydro-pneumatic treated water supply.</p>
                    </div>
                </div>
                <!-- 3. 3-Tier Security -->
                <div class="col-md-4">
                    <div class="p-4 border rounded-3 bg-white h-100 shadow-sm shadow-hover transition-transform text-dark position-relative border-start border-4 border-danger" style="border-color: rgba(16, 49, 120, 0.1) !important; border-left-color: #ef4444 !important;">
                        <h6 class="fw-bold text-dark mb-2" style="font-size: 1.05rem; color: #0f172a !important;">3-Tier Smart Security</h6>
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-20 mb-2 small" style="font-size: 0.68rem;">24x7 AI Fencing</span>
                        <p class="text-secondary small mb-0" style="line-height: 1.5; color: #475569 !important;">24x7 infra-red perimeter fencing, boom barriers, & HD CCTV monitoring.</p>
                    </div>
                </div>
                <!-- 4. Dedicated EV Docks -->
                <div class="col-md-4">
                    <div class="p-4 border rounded-3 bg-white h-100 shadow-sm shadow-hover transition-transform text-dark position-relative border-start border-4 border-success" style="border-color: rgba(16, 49, 120, 0.1) !important; border-left-color: #10b981 !important;">
                        <h6 class="fw-bold text-dark mb-2" style="font-size: 1.05rem; color: #0f172a !important;">Dedicated EV Docks</h6>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-20 mb-2 small" style="font-size: 0.68rem;">Fast AC/DC Docks</span>
                        <p class="text-secondary small mb-0" style="line-height: 1.5; color: #475569 !important;">Fast AC/DC electric vehicle charging docks at multi-level parking bays.</p>
                    </div>
                </div>
                <!-- 5. Botanical Parks & Track -->
                <div class="col-md-4">
                    <div class="p-4 border rounded-3 bg-white h-100 shadow-sm shadow-hover transition-transform text-dark position-relative border-start border-4 border-success" style="border-color: rgba(16, 49, 120, 0.1) !important; border-left-color: #059669 !important;">
                        <h6 class="fw-bold text-dark mb-2" style="font-size: 1.05rem; color: #0f172a !important;">Botanical Parks & Track</h6>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-20 mb-2 small" style="font-size: 0.68rem;">60% Open Flora</span>
                        <p class="text-secondary small mb-0" style="line-height: 1.5; color: #475569 !important;">Over 60% open green space with reflexology jogging pathways & gardens.</p>
                    </div>
                </div>
                <!-- 6. Auto-Rescue Lifts -->
                <div class="col-md-4">
                    <div class="p-4 border rounded-3 bg-white h-100 shadow-sm shadow-hover transition-transform text-dark position-relative border-start border-4 border-primary" style="border-color: rgba(16, 49, 120, 0.1) !important; border-left-color: var(--blue-brand) !important;">
                        <h6 class="fw-bold text-dark mb-2" style="font-size: 1.05rem; color: #0f172a !important;">Auto-Rescue Lifts</h6>
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-20 mb-2 small" style="font-size: 0.68rem;">ARD Emergency Tech</span>
                        <p class="text-secondary small mb-0" style="line-height: 1.5; color: #475569 !important;">Dual-speed elevators equipped with Automatic Rescue Devices (ARD).</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Architectural Heritage & Legacy Banner -->
        <div class="p-4 rounded-3 bg-light border border-light-subtle position-relative z-1 shadow-sm">
            <div class="row g-3 text-center text-dark">
                <div class="col-6 col-md-3">
                    <h3 class="fw-extrabold text-warning mb-0" style="font-size: 1.7rem; color: #d97706 !important;">25+ Years</h3>
                    <span class="text-muted small text-uppercase" style="font-size: 0.7rem; letter-spacing: 1px;">Architectural Excellence</span>
                </div>
                <div class="col-6 col-md-3">
                    <h3 class="fw-extrabold text-warning mb-0" style="font-size: 1.7rem; color: #d97706 !important;">4.9 / 5.0</h3>
                    <span class="text-muted small text-uppercase" style="font-size: 0.7rem; letter-spacing: 1px;">Resident Review Score</span>
                </div>
                <div class="col-6 col-md-3">
                    <h3 class="fw-extrabold text-warning mb-0" style="font-size: 1.7rem; color: #d97706 !important;">80% Green</h3>
                    <span class="text-muted small text-uppercase" style="font-size: 0.7rem; letter-spacing: 1px;">Landscaped Eco-Park</span>
                </div>
                <div class="col-6 col-md-3">
                    <h3 class="fw-extrabold text-warning mb-0" style="font-size: 1.7rem; color: #d97706 !important;">100% Smart</h3>
                    <span class="text-muted small text-uppercase" style="font-size: 0.7rem; letter-spacing: 1px;">Automated Living</span>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- 11. GOOGLE MAP & SURROUNDINGS (WHITE LIGHT LUXURY REDESIGN) -->
    <?php if (isset($only_contact) || !$any_only): ?>
    <section id="map-section" class="container my-5 p-4 p-md-5 rounded-4 shadow-lg position-relative overflow-hidden" style="background: #ffffff !important; border: 1px solid rgba(0, 0, 0, 0.08); border-left: 6px solid var(--orange-brand) !important;">
        <!-- Ambient Background Bubbles -->
        <div class="position-absolute top-0 end-0 opacity-05" style="width: 350px; height: 350px; background: radial-gradient(circle, var(--orange-brand) 0%, transparent 70%); pointer-events: none;"></div>
        <div class="position-absolute bottom-0 start-0 opacity-05" style="width: 350px; height: 350px; background: radial-gradient(circle, var(--blue-brand) 0%, transparent 70%); pointer-events: none;"></div>

        <!-- Section Header -->
        <div class="text-center mb-5 position-relative z-1">
            <span class="badge bg-warning text-dark fw-bold px-3.5 py-2 mb-3 text-uppercase shadow-sm" style="font-size: 0.75rem; letter-spacing: 2px; background-color: var(--orange-brand) !important;">
                <i class="fa-solid fa-map-location-dot me-1.5"></i> Strategic Urban Connectivity
            </span>
            <h2 class="fw-extrabold text-dark text-uppercase display-6 mb-3" style="font-family: 'Playfair Display', serif; letter-spacing: 1px; color: #0f172a !important;">
                Interactive Neighborhood Location & Local Points of Interest
            </h2>
            <p class="text-secondary mx-auto" style="max-width: 680px; font-size: 0.95rem; line-height: 1.6; color: #475569 !important;">
                Located at the prime arterial heart of Tambaram East, Jarvis Residences offers instant walkability to top educational institutions, multi-specialty healthcare, and express transit corridors.
            </p>
        </div>

        <div class="row g-4 position-relative z-1 mb-4">
            <!-- Left Column: Interactive Map Canvas -->
            <div class="col-lg-7">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold text-dark mb-0" style="color: #0f172a !important;"><i class="fa-solid fa-earth-asia text-warning me-2"></i>Live Satellite & Road Map</h4>
                    <span class="badge bg-light text-success border border-success px-2.5 py-1"><i class="fa-solid fa-circle text-success me-1" style="font-size: 0.5rem;"></i> MCC Tambaram Hub</span>
                </div>
                <div class="ratio ratio-16x9 rounded-3 overflow-hidden border border-light-subtle shadow-md">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3889.3735914619934!2d80.1207869750731!3d12.922849087311124!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3a525facf70a1e05%3A0x673a5a751684c375!2sMadras%20Christian%20College!5e0!3m2!1sen!2sin!4v1721803800000!5m2!1sen!2sin" title="Jarvis Location Map" allowfullscreen></iframe>
                </div>
                <div class="mt-3 p-3 rounded-3 bg-light border border-light-subtle d-flex align-items-center justify-content-between text-dark shadow-xs">
                    <div class="d-flex align-items-center">
                        <i class="fa-solid fa-location-crosshairs text-warning fs-5 me-2.5"></i>
                        <span class="small text-secondary">Exact Address: <strong class="text-dark">Jarvis Residences, Velachery Main Rd, Tambaram, Chennai - 600059</strong></span>
                    </div>
                    <a href="https://maps.google.com/?q=Madras+Christian+College+Tambaram" target="_blank" class="btn btn-sm btn-outline-warning text-uppercase font-bold" style="font-size: 0.72rem;"><i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Open Google Maps</a>
                </div>
            </div>

            <!-- Right Column: Nearby Local Points of Interest -->
            <div class="col-lg-5">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold text-dark mb-0" style="color: #0f172a !important;"><i class="fa-solid fa-list-check text-warning me-2"></i>Nearby Points of Interest</h4>
                    <span class="badge bg-warning text-dark font-bold">1-10 Mins Radius</span>
                </div>

                <div class="list-group list-group-flush border border-light-subtle rounded-3 overflow-hidden bg-white shadow-sm">
                    <!-- Nearby Schools -->
                    <div class="list-group-item d-flex justify-content-between align-items-center bg-transparent py-3 border-bottom border-light-subtle text-dark">
                        <div>
                            <h6 class="fw-bold text-dark mb-0" style="font-size: 0.95rem; color: #0f172a !important;">Nearby Schools & Colleges</h6>
                            <span class="text-secondary small" style="font-size: 0.78rem;">MCC & Corley Higher Sec. School</span>
                        </div>
                        <span class="badge bg-primary text-white rounded-pill px-3 py-1.5 font-bold" style="font-size: 0.75rem;">3 Mins</span>
                    </div>

                    <!-- Hospitals -->
                    <div class="list-group-item d-flex justify-content-between align-items-center bg-transparent py-3 border-bottom border-light-subtle text-dark">
                        <div>
                            <h6 class="fw-bold text-dark mb-0" style="font-size: 0.95rem; color: #0f172a !important;">Multispeciality Hospitals</h6>
                            <span class="text-secondary small" style="font-size: 0.78rem;">COSH Ortho & Global Health City</span>
                        </div>
                        <span class="badge bg-danger text-white rounded-pill px-3 py-1.5 font-bold" style="font-size: 0.75rem;">5 Mins</span>
                    </div>

                    <!-- Bus Stops -->
                    <div class="list-group-item d-flex justify-content-between align-items-center bg-transparent py-3 border-bottom border-light-subtle text-dark">
                        <div>
                            <h6 class="fw-bold text-dark mb-0" style="font-size: 0.95rem; color: #0f172a !important;">Central Bus Station</h6>
                            <span class="text-secondary small" style="font-size: 0.78rem;">Tambaram East Main Terminal</span>
                        </div>
                        <span class="badge bg-success text-white rounded-pill px-3 py-1.5 font-bold" style="font-size: 0.75rem;">2 Mins</span>
                    </div>

                    <!-- Railway Station -->
                    <div class="list-group-item d-flex justify-content-between align-items-center bg-transparent py-3 border-bottom border-light-subtle text-dark">
                        <div>
                            <h6 class="fw-bold text-dark mb-0" style="font-size: 0.95rem; color: #0f172a !important;">Railway Station & Metro</h6>
                            <span class="text-secondary small" style="font-size: 0.78rem;">Tambaram Junction Intercity Station</span>
                        </div>
                        <span class="badge bg-info text-dark rounded-pill px-3 py-1.5 font-bold" style="font-size: 0.75rem;">10 Mins</span>
                    </div>

                    <!-- Premium Restaurants -->
                    <div class="list-group-item d-flex justify-content-between align-items-center bg-transparent py-3 border-bottom border-light-subtle text-dark">
                        <div>
                            <h6 class="fw-bold text-dark mb-0" style="font-size: 0.95rem; color: #0f172a !important;">Fine Dining & Restaurants</h6>
                            <span class="text-secondary small" style="font-size: 0.78rem;">Grand Galada & Saravana Bhavan</span>
                        </div>
                        <span class="badge text-white rounded-pill px-3 py-1.5 font-bold" style="font-size: 0.75rem; background: #e91e63 !important;">4 Mins</span>
                    </div>

                    <!-- Grocery Supermarket -->
                    <div class="list-group-item d-flex justify-content-between align-items-center bg-transparent py-3 text-dark">
                        <div>
                            <h6 class="fw-bold text-dark mb-0" style="font-size: 0.95rem; color: #0f172a !important;">Supermarkets & Malls</h6>
                            <span class="text-secondary small" style="font-size: 0.78rem;">Reliance Smart & Grand Mall</span>
                        </div>
                        <span class="badge text-white rounded-pill px-3 py-1.5 font-bold" style="font-size: 0.75rem; background: #9c27b0 !important;">2 Mins</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Major Arterial Commute Distances Bar -->
        <div class="p-4 rounded-3 bg-light border border-light-subtle position-relative z-1 shadow-sm">
            <div class="row g-3 text-center text-dark">
                <div class="col-6 col-md-3">
                    <h3 class="fw-extrabold text-warning mb-0" style="font-size: 1.6rem; color: #d97706 !important;">25 Mins</h3>
                    <span class="text-muted small text-uppercase" style="font-size: 0.7rem; letter-spacing: 1px;">Chennai Int'l Airport (14 km)</span>
                </div>
                <div class="col-6 col-md-3">
                    <h3 class="fw-extrabold text-warning mb-0" style="font-size: 1.6rem; color: #d97706 !important;">5 Mins</h3>
                    <span class="text-muted small text-uppercase" style="font-size: 0.7rem; letter-spacing: 1px;">Outer Ring Road Expressway</span>
                </div>
                <div class="col-6 col-md-3">
                    <h3 class="fw-extrabold text-warning mb-0" style="font-size: 1.6rem; color: #d97706 !important;">15 Mins</h3>
                    <span class="text-muted small text-uppercase" style="font-size: 0.7rem; letter-spacing: 1px;">IT Corridor (OMR Hub)</span>
                </div>
                <div class="col-6 col-md-3">
                    <h3 class="fw-extrabold text-warning mb-0" style="font-size: 1.6rem; color: #d97706 !important;">2 Mins</h3>
                    <span class="text-muted small text-uppercase" style="font-size: 0.7rem; letter-spacing: 1px;">GST Arterial Highway</span>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- 12. CONTACT FORM SECTION (WHITE LIGHT LUXURY REDESIGN) -->
    <section id="contact" class="container my-5 p-4 p-md-5 rounded-4 shadow-lg position-relative overflow-hidden text-dark" style="background: #ffffff !important; border: 1px solid rgba(0, 0, 0, 0.08); border-left: 6px solid var(--orange-brand) !important;">
        <!-- Ambient background decorations -->
        <div class="position-absolute end-0 bottom-0 opacity-05" style="font-size: 16rem; transform: translate(15%, 15%) rotate(-15deg); pointer-events: none; color: #000000;">
            <i class="fa-solid fa-headset"></i>
        </div>
        <div class="position-absolute start-0 top-0 opacity-05" style="width: 250px; height: 250px; background: radial-gradient(circle, var(--orange-brand) 0%, transparent 70%); pointer-events: none;"></div>

        <div class="row g-5 align-items-center position-relative z-1">
            <div class="col-lg-5">
                <span class="badge bg-warning text-dark fw-bold px-3 py-2 mb-3 text-uppercase shadow-sm" style="font-size: 0.75rem; letter-spacing: 2px; background-color: var(--orange-brand) !important;">
                    <i class="fa-solid fa-comments me-1.5"></i> Direct Concierge Desk
                </span>
                <h2 class="fw-extrabold text-dark text-uppercase display-6 mb-3" style="font-family: 'Playfair Display', serif; letter-spacing: 1px; color: #0f172a !important;">
                    Connect With Our Property Experts
                </h2>
                <p class="text-secondary mb-4" style="font-size: 0.95rem; line-height: 1.65; color: #475569 !important;">
                    Submit your personal requirements, and our dedicated Relationship Manager will get in touch with you within 2 hours to assist with site visits, loan pre-approvals, and unit reservations.
                </p>
                
                <div class="d-flex flex-column gap-3 mt-4">
                    <!-- Email Badge -->
                    <div class="d-flex align-items-center bg-light rounded-pill p-2 pe-4 border border-light-subtle shadow-xs" style="width: fit-content;">
                        <div class="d-flex align-items-center justify-content-center bg-warning text-dark rounded-circle me-3" style="width: 42px; height: 42px; background-color: var(--orange-brand) !important;">
                            <i class="fa-solid fa-envelope" style="font-size: 1.1rem;"></i>
                        </div>
                        <span class="fw-semibold text-dark" style="font-size: 0.9rem; letter-spacing: 0.5px; color: #0f172a !important;">sales@jarvisresidences.com</span>
                    </div>

                    <!-- Phone Badge -->
                    <div class="d-flex align-items-center bg-light rounded-pill p-2 pe-4 border border-light-subtle shadow-xs" style="width: fit-content;">
                        <div class="d-flex align-items-center justify-content-center bg-warning text-dark rounded-circle me-3" style="width: 42px; height: 42px; background-color: var(--orange-brand) !important;">
                            <i class="fa-solid fa-phone" style="font-size: 1.1rem;"></i>
                        </div>
                        <span class="fw-semibold text-dark" style="font-size: 0.9rem; letter-spacing: 0.5px; color: #0f172a !important;">+91 80562 10606</span>
                    </div>

                    <!-- Location Pill -->
                    <div class="d-flex align-items-center bg-light rounded-pill p-2 pe-4 border border-light-subtle shadow-xs" style="width: fit-content;">
                        <div class="d-flex align-items-center justify-content-center bg-info text-dark rounded-circle me-3" style="width: 42px; height: 42px;">
                            <i class="fa-solid fa-location-dot" style="font-size: 1.1rem;"></i>
                        </div>
                        <span class="fw-semibold text-dark" style="font-size: 0.9rem; letter-spacing: 0.5px; color: #0f172a !important;">Velachery Main Rd, Tambaram, Chennai</span>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-7">
                <div class="card border border-light-subtle p-4 p-md-5 shadow-lg bg-light text-dark rounded-4">
                    <div class="d-flex align-items-center mb-4 pb-2 border-bottom border-light-subtle">
                        <div class="d-flex align-items-center justify-content-center rounded-circle me-3" style="width: 46px; height: 46px; background: rgba(242, 161, 34, 0.15); border: 1.5px solid rgba(242, 161, 34, 0.35);">
                            <i class="fa-solid fa-paper-plane" style="font-size: 1.2rem; color: var(--orange-brand);"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold mb-0 text-dark" style="color: #0f172a !important;">Send An Priority Inquiry</h4>
                            <span class="text-muted small">Guaranteed callback within 120 minutes</span>
                        </div>
                    </div>

                    <form action="index.php" method="POST">
                        <div class="row g-3.5">
                            <div class="col-sm-6">
                                <label class="form-label small fw-bold text-dark text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">Full Name</label>
                                <input type="text" name="inq_name" class="form-control form-control-custom p-3 bg-white" placeholder="e.g. John Doe" required style="border-radius: 8px;">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label small fw-bold text-dark text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">Mobile Phone Number</label>
                                <input type="tel" name="inq_phone" class="form-control form-control-custom p-3 bg-white" placeholder="e.g. +91 98765 43210" required style="border-radius: 8px;">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold text-dark text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">Email Address</label>
                                <input type="email" name="inq_email" class="form-control form-control-custom p-3 bg-white" placeholder="e.g. john@example.com" required style="border-radius: 8px;">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold text-dark text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">Specific Requirements & Questions</label>
                                <textarea name="inq_message" rows="3" class="form-control form-control-custom p-3 bg-white" placeholder="Tell us about your requirements (e.g. 2 BHK layout, budget preferences, site visit schedule...)" required style="border-radius: 8px;"></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" name="submit_contact" class="btn btn-warning btn-grand-submit text-dark fw-bold w-100 py-3 mt-2 rounded-3 shadow-sm" style="font-size: 1rem; letter-spacing: 0.5px;">
                                    <i class="fa-solid fa-paper-plane me-2"></i> Submit Inquiry Request
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Home Loan Eligibility & EMI Calculator Suite -->
    <?php if (!$any_only || isset($only_pricing)): ?>
    <div class="container my-5 py-4 bg-light rounded shadow-sm border border-secondary border-opacity-10">
        <div class="row g-4">
            <!-- 1. EMI Calculator -->
            <div class="col-md-6">
                <div class="calc-card">
                    <h4 class="fw-bold mb-1 text-dark text-uppercase" style="letter-spacing: 1px;"><i class="fa-solid fa-calculator text-warning me-2"></i>EMI Calculator</h4>
                    <p class="text-muted small mb-4">Estimate monthly payments for your home loan instantly.</p>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Loan Amount (₹)</label>
                            <input type="number" id="emiAmount" class="form-control form-control-custom" value="120000" oninput="calculateEMI()">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Interest Rate (%)</label>
                            <input type="number" id="emiRate" class="form-control form-control-custom" value="7.5" step="0.1" oninput="calculateEMI()">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Duration (Years)</label>
                            <input type="number" id="emiYears" class="form-control form-control-custom" value="20" oninput="calculateEMI()">
                        </div>
                    </div>
                    <div class="calc-result-box d-flex justify-content-between align-items-center" style="margin-top: 1.5rem; padding: 1.1rem 1.25rem;">
                        <span class="text-muted fw-bold small text-uppercase" style="letter-spacing: 0.5px; font-size: 0.78rem;">Estimated Monthly Payment</span>
                        <h3 class="fw-bold text-dark mb-0 fs-4" id="emiOutput">₹966.71</h3>
                    </div>
                </div>
            </div>

            <!-- 2. Loan Eligibility Calculator -->
            <div class="col-md-6">
                <div class="calc-card">
                    <h4 class="fw-bold mb-1 text-dark text-uppercase" style="letter-spacing: 1px;"><i class="fa-solid fa-scale-balanced text-warning me-2"></i>Loan Eligibility</h4>
                    <p class="text-muted small mb-4">Verify the max amount bank lenders will issue based on income.</p>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Monthly Income (₹)</label>
                            <input type="number" id="incomeAmount" class="form-control form-control-custom" value="5000" oninput="calculateEligibility()">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Existing Monthly EMI (₹)</label>
                            <input type="number" id="incomeExisting" class="form-control form-control-custom" value="500" oninput="calculateEligibility()">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Tenure (Years)</label>
                            <input type="number" id="eligibilityYears" class="form-control form-control-custom" value="20" oninput="calculateEligibility()">
                        </div>
                    </div>
                    <div class="calc-result-box d-flex justify-content-between align-items-center" style="background-color:rgba(16,49,120,0.06); border-color:var(--blue-brand); margin-top: 1.5rem; padding: 1.1rem 1.25rem;">
                        <span class="text-muted fw-bold small text-uppercase" style="letter-spacing: 0.5px; font-size: 0.78rem;">Eligible Home Loan Amount</span>
                        <h3 class="fw-bold text-dark mb-0 fs-4" id="eligibilityOutput">₹220,000.00</h3>
                    </div>
                </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Book Site Visit Section -->
    <?php if (!$any_only): ?>
    <section id="book-site-visit" class="container my-5 py-5 text-center text-white rounded" style="background: linear-gradient(135deg, var(--blue-brand) 0%, #0d2354 100%); position: relative; overflow: hidden; border-left: 5px solid var(--orange-brand);">
        <div class="position-absolute opacity-10 end-0 bottom-0" style="font-size: 15rem; transform: rotate(-15deg); pointer-events: none;"><i class="fa-solid fa-calendar-days text-white"></i></div>
        <div class="position-relative z-1 max-width-600 mx-auto">
            <h3 class="fw-bold text-uppercase mb-2" style="color: #ffffff !important; letter-spacing: 2px; font-family:'Plus Jakarta Sans', sans-serif;">Schedule a VIP Site Visit</h3>
            <p class="text-white-50 small mb-4">Coordinate a free guided tour of any flat layout with our customer relations manager on your preferred date and time.</p>
            <button class="btn btn-warning text-dark fw-bold px-4 py-2.5" style="border-radius: 6px;" data-bs-toggle="modal" data-bs-target="#inquiryModal">
                <i class="fa-solid fa-calendar-days me-2"></i> Book Site Visit Now
            </button>
        </div>
    </section>
    <?php endif; ?>

    <?php if (!$any_only): ?>
    <!-- Trust & Social Proof Section -->
    <div class="container my-5 py-3">
        <div class="text-center mb-5">
            <h3 class="fw-bold text-uppercase" style="letter-spacing: 2px;">Why Trust Jarvis Real Estate</h3>
            <p class="text-muted">Industry recognition and banking certifications verifying our excellence.</p>
        </div>
        
        <!-- Badges Row -->
        <div class="row g-4 text-center mb-5">
            <div class="col-md-3 col-6">
                <div class="p-3 border rounded shadow-sm bg-white">
                    <i class="fa-solid fa-award fa-3x text-warning mb-2"></i>
                    <h6 class="fw-bold mb-1 text-dark">Best Luxury Project</h6>
                    <p class="text-muted small mb-0">Casagrand Awards 2025</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="p-3 border rounded shadow-sm bg-white">
                    <i class="fa-solid fa-shield-halved fa-3x text-warning mb-2"></i>
                    <h6 class="fw-bold mb-1 text-dark">100% RERA Certified</h6>
                    <p class="text-muted small mb-0">Verified Registry Listings</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="p-3 border rounded shadow-sm bg-white">
                    <i class="fa-solid fa-users-viewfinder fa-3x text-warning mb-2"></i>
                    <h6 class="fw-bold mb-1 text-dark">12,000+ Happy Buyers</h6>
                    <p class="text-muted small mb-0">Across Indian Metro Cities</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="p-3 border rounded shadow-sm bg-white">
                    <i class="fa-solid fa-handshake fa-3x text-warning mb-2"></i>
                    <h6 class="fw-bold mb-1 text-dark">8+ Partner Banks</h6>
                    <p class="text-muted small mb-0">Instant Loan Pre-approvals</p>
                </div>
            </div>
        </div>

        <!-- Client Testimonials Slider -->
        <div id="testimonialCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="4000">
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <div class="testimonial-card text-center max-width-600 mx-auto">
                        <div class="testimonial-stars"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></div>
                        <p class="text-muted italic">"Booking Unit 302 through the online resident portal was seamless. The checkout logged our credit deposit instantly and the sales representative coordinated the site visit next morning."</p>
                        <div class="testimonial-user justify-content-center">
                            <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&q=80&w=100" alt="Client Face">
                            <div class="text-start">
                                <h6 class="fw-bold text-dark mb-0">Arun Kumar</h6>
                                <span class="text-muted small">Resident, Casagrand Elan</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="carousel-item">
                    <div class="testimonial-card text-center max-width-600 mx-auto">
                        <div class="testimonial-stars"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></div>
                        <p class="text-muted italic">"The Commercial space layouts are top tier. Jarvis Concierge bot provided price metrics instantly, enabling our operations team to secure our tech park building unit on the spot."</p>
                        <div class="testimonial-user justify-content-center">
                            <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&q=80&w=100" alt="Client Face">
                            <div class="text-start">
                                <h6 class="fw-bold text-dark mb-0">Priya Sharma</h6>
                                <span class="text-muted small">Operations Director, Tech Hub</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FAQ Accordion section -->
    <div class="container my-5 py-3">
        <div class="text-center mb-5">
            <h3 class="fw-bold text-uppercase" style="letter-spacing: 2.5px;">Frequently Asked Questions</h3>
            <p class="text-muted">Quick replies regarding registrations, pricing, and home visits.</p>
        </div>
        
        <div class="accordion max-width-800 mx-auto" id="faqAccordion">
            <div class="accordion-item border-0 mb-3 shadow-sm rounded">
                <h2 class="accordion-header" id="faqHeading1">
                    <button class="accordion-button rounded" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse1" aria-expanded="true" aria-controls="faqCollapse1">
                        What documents are required to book an apartment online?
                    </button>
                </h2>
                <div id="faqCollapse1" class="accordion-collapse collapse show" aria-labelledby="faqHeading1" data-bs-parent="#faqAccordion">
                    <div class="accordion-body text-muted small bg-white">
                        To book online, you will need a valid government-issued ID (PAN Card, Passport, or Aadhaar Card) and salary details if seeking home loan pre-approval. You can upload these files securely during simulated checkout.
                    </div>
                </div>
            </div>
            
            <div class="accordion-item border-0 mb-3 shadow-sm rounded">
                <h2 class="accordion-header" id="faqHeading2">
                    <button class="accordion-button collapsed rounded" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse2" aria-expanded="false" aria-controls="faqCollapse2">
                        Are the bank loan offers pre-approved?
                    </button>
                </h2>
                <div id="faqCollapse2" class="accordion-collapse collapse" aria-labelledby="faqHeading2" data-bs-parent="#faqAccordion">
                    <div class="accordion-body text-muted small bg-white">
                        Yes! We partner with top institutions including HDFC, ICICI, SBI, and Axis Bank. You can compute eligibility estimates using our Home Loan Eligibility module above and request pre-approved sanctions.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Brochure Download PDF Panel -->
    <div class="container my-5 text-center py-4 bg-light border rounded">
        <h5 class="fw-bold text-dark text-uppercase mb-2"><i class="fa-solid fa-file-pdf text-danger me-2"></i>Download Official Project Brochure</h5>
        <p class="text-muted small">Save high-resolution maps, unit plans, pricing metrics, and modular building dimensions.</p>
        <a href="<?php echo $base_path; ?>Jarvis_Premium_Residences_Map.pdf" download="Jarvis_Premium_Residences_Map.pdf" class="btn btn-warning text-dark fw-bold px-4 py-2 mt-2"><i class="fa-solid fa-download me-1"></i> Download Brochure PDF</a>
    </div>

<?php elseif ($active_type === 'Commercial'): ?>
    <!-- ------------------------------------------------------------- -->
    <!-- COMMERCIAL PROJECTS VIEW (Screenshot 2 Layout) -->
    <!-- ------------------------------------------------------------- -->
    <div class="container my-5">
        <div class="text-center mb-5">
            <h1 class="fw-bold mb-2" style="font-family:'Plus Jakarta Sans', sans-serif; color:var(--orange-brand); letter-spacing:-0.5px;">Commercial Projects - Jarvis</h1>
            <p class="text-muted">Explore Grade-A tech plazas, executive suites, and corporate office parks built to international standards.</p>
        </div>
        
        <!-- City selectors block -->
        <div class="d-flex justify-content-center flex-wrap gap-3 mb-5">
            <a href="commercial.php" class="btn rounded-pill px-4 py-2 fw-semibold <?php echo $filter_city === '' ? 'btn-warning text-dark shadow-sm' : 'btn-light text-secondary border'; ?>">All Cities</a>
            <a href="commercial.php?city=Chennai" class="btn rounded-pill px-4 py-2 fw-semibold <?php echo $filter_city === 'Chennai' ? 'btn-warning text-dark shadow-sm' : 'btn-light text-secondary border'; ?>">Chennai</a>
            <a href="commercial.php?city=Coimbatore" class="btn rounded-pill px-4 py-2 fw-semibold <?php echo $filter_city === 'Coimbatore' ? 'btn-warning text-dark shadow-sm' : 'btn-light text-secondary border'; ?>">Coimbatore</a>
            <a href="commercial.php?city=Bengaluru" class="btn rounded-pill px-4 py-2 fw-semibold <?php echo $filter_city === 'Bengaluru' ? 'btn-warning text-dark shadow-sm' : 'btn-light text-secondary border'; ?>">Bangalore</a>
        </div>

        <!-- Commercial properties grid -->
        <div class="row g-4">
            <?php if (count($flats) > 0): ?>
                <?php foreach ($flats as $flat): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card card-premium h-100 border-0 shadow-sm d-flex flex-column justify-content-between">
                            <div>
                                <div class="position-relative overflow-hidden rounded-top" style="height:240px;">
                                    <?php $c_photos = parse_flat_photos($flat['image_url'] ?? ''); ?>
                                    <img src="<?php echo $base_path . htmlspecialchars($c_photos['exterior']); ?>" class="w-100 h-100" style="object-fit:cover;" alt="Commercial Property">
                                    <span class="position-absolute bottom-0 start-0 m-3 badge bg-warning text-dark fw-bold px-3 py-2 text-uppercase" style="font-size:0.7rem; background-color:#f59e0b !important;">
                                        Ready to Lease
                                    </span>
                                </div>
                                <div class="card-body p-4">
                                    <h4 class="fw-bold mb-2" style="color:var(--blue-brand);"><?php echo htmlspecialchars($flat['block']); ?></h4>
                                    <div class="text-muted small mb-2"><i class="fa-solid fa-location-dot me-1 text-danger"></i> <?php echo htmlspecialchars($flat['city']); ?></div>
                                    <p class="text-muted small"><?php echo htmlspecialchars($flat['description']); ?></p>
                                    <div class="d-flex justify-content-between align-items-center mt-3 border-top pt-3">
                                        <span class="fs-5 fw-bold text-success">₹<?php echo number_format($flat['price'], 2); ?></span>
                                        <span class="small text-muted"><i class="fa-solid fa-expand me-1"></i> Executive Suite</span>
                                    </div>
                                </div>
                            </div>
                            <div class="p-4 pt-0">
                                <a href="resident/checkout.php?flat_id=<?php echo $flat['id']; ?>" class="btn btn-primary-custom w-100 py-2 text-white fw-bold" style="background-color:var(--blue-brand); border-color:var(--blue-brand);">
                                    <i class="fa-solid fa-house-circle-check me-2"></i>Book Office Space
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <i class="fa-solid fa-folder-open fa-3x mb-3 text-secondary"></i>
                    <p class="text-muted">No commercial property listings found matching this location filter.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php elseif ($active_type === 'Industrial'): ?>
    <!-- ------------------------------------------------------------- -->
    <!-- INDUSTRIAL PROJECTS VIEW (Screenshot 3 Layout) -->
    <!-- ------------------------------------------------------------- -->
    <div class="position-relative w-100 overflow-hidden mb-5" style="height: 480px; background: linear-gradient(rgba(0,0,0,0.55), rgba(0,0,0,0.55)), url('<?php echo $base_path; ?>images/industrial_banner.png') center/cover no-repeat;">
        <div class="position-absolute start-50 top-50 translate-middle text-center w-100 px-3">
            <div class="mb-3">
                <i class="fa-solid fa-industry text-warning" style="font-size:3.5rem;"></i>
            </div>
            <h1 class="display-4 fw-bold text-uppercase text-white tracking-widest text-shadow mb-2" style="font-family:'Plus Jakarta Sans', sans-serif;">Industrial & Warehousing</h1>
            <p class="lead text-white text-shadow text-uppercase" style="letter-spacing:4px; font-size:0.95rem;">Integrated smart logistics estates & warehouses</p>
        </div>
    </div>

    <div class="container my-5">
        <div class="text-center mb-5">
            <h2 class="fw-bold text-uppercase" style="letter-spacing:1px; color:var(--blue-brand);">Grade-A Logistics Parks</h2>
            <p class="text-muted">RERA-certified spaces with heavy loading layouts, smart grid safety, and close sea port access corridors.</p>
        </div>

        <div class="row g-4">
            <?php if (count($flats) > 0): ?>
                <?php foreach ($flats as $flat): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card card-premium h-100 border-0 shadow-sm d-flex flex-column justify-content-between">
                            <div>
                                <div class="position-relative overflow-hidden rounded-top" style="height:240px;">
                                    <?php $i_photos = parse_flat_photos($flat['image_url'] ?? ''); ?>
                                    <img src="<?php echo $base_path . htmlspecialchars($i_photos['exterior']); ?>" class="w-100 h-100" style="object-fit:cover;" alt="Industrial Facility">
                                    <span class="position-absolute bottom-0 start-0 m-3 badge bg-danger text-white fw-bold px-3 py-2 text-uppercase" style="font-size:0.7rem;">
                                        Grade-A Logistics
                                    </span>
                                </div>
                                <div class="card-body p-4">
                                    <h4 class="fw-bold mb-2" style="color:var(--blue-brand);"><?php echo htmlspecialchars($flat['block']); ?></h4>
                                    <div class="text-muted small mb-2"><i class="fa-solid fa-location-dot me-1 text-danger"></i> <?php echo htmlspecialchars($flat['city']); ?></div>
                                    <p class="text-muted small"><?php echo htmlspecialchars($flat['description']); ?></p>
                                    <div class="d-flex justify-content-between align-items-center mt-3 border-top pt-3">
                                        <span class="fs-5 fw-bold text-success">₹<?php echo number_format($flat['price'], 2); ?></span>
                                        <span class="small text-muted"><i class="fa-solid fa-truck-ramp-box me-1"></i> Logistics Base</span>
                                    </div>
                                </div>
                            </div>
                            <div class="p-4 pt-0">
                                <a href="resident/checkout.php?flat_id=<?php echo $flat['id']; ?>" class="btn btn-primary-custom w-100 py-2 text-white fw-bold" style="background-color:var(--blue-brand); border-color:var(--blue-brand);">
                                    <i class="fa-solid fa-house-circle-check me-2"></i>Book Logistics Unit
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <p class="text-muted">No industrial facilities registered at this location at the moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php elseif ($active_type === 'NRI'): ?>
    <!-- ------------------------------------------------------------- -->
    <!-- NRI BUYER VIEW (Screenshot 4 Layout) -->
    <!-- ------------------------------------------------------------- -->
    <div class="position-relative w-100 d-flex align-items-center justify-content-center" style="min-height: 580px; background: linear-gradient(rgba(0,0,0,0.35), rgba(0,0,0,0.35)), url('<?php echo $base_path; ?>images/nri_banner.png') center/cover no-repeat;">
        <div class="container my-5">
            <div class="row g-4 align-items-center">
                <!-- Left Content Headline -->
                <div class="col-lg-7 text-white">
                    <h1 class="display-4 fw-bold text-shadow mb-3" style="font-family:'Plus Jakarta Sans', sans-serif;">Invest today to reap great returns tomorrow.</h1>
                    <p class="lead text-shadow mb-4" style="letter-spacing:1px; font-weight: 500;">Premium Luxury Homes ranging from ₹40L to ₹4Cr customized for non-resident buyers.</p>
                    <div class="d-flex gap-3">
                        <span class="badge bg-warning text-dark fw-bold px-3 py-2 text-uppercase"><i class="fa-solid fa-shield-halved me-1"></i> 100% Tax Compliant</span>
                        <span class="badge bg-warning text-dark fw-bold px-3 py-2 text-uppercase"><i class="fa-solid fa-globe me-1"></i> NRI Support Desk</span>
                    </div>
                </div>
                
                <!-- Right Contact Form Card -->
                <div class="col-lg-5">
                    <div class="card border-0 shadow-lg p-4" style="background-color: rgba(255,255,255,0.96); border-radius: 12px; border-top: 4px solid var(--orange-brand) !important;">
                        <h5 class="fw-bold text-dark text-center text-uppercase mb-1" style="font-size:0.95rem; letter-spacing:0.5px;">Interested to Know More</h5>
                        <p class="text-muted text-center small mb-4">Get in touch with our expert sales desk.</p>
                        
                        <form action="<?php echo $current_page; ?>" method="POST">
                            <input type="hidden" name="action" value="add_inquiry">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-dark mb-1">Name*</label>
                                <input type="text" name="name" class="form-control form-control-custom" placeholder="Enter your name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-dark mb-1">Email Address*</label>
                                <input type="email" name="email" class="form-control form-control-custom" placeholder="Enter your email" required>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-4">
                                    <label class="form-label small fw-semibold text-dark mb-1">Code*</label>
                                    <select class="form-select form-control-custom">
                                        <option value="+1">+1 (US)</option>
                                        <option value="+91">+91 (IN)</option>
                                        <option value="+971">+971 (UAE)</option>
                                        <option value="+44">+44 (UK)</option>
                                    </select>
                                </div>
                                <div class="col-8">
                                    <label class="form-label small fw-semibold text-dark mb-1">Phone Number*</label>
                                    <input type="tel" name="phone" class="form-control form-control-custom" placeholder="Enter phone" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-dark mb-1">Select City Location*</label>
                                <select name="city" class="form-select form-control-custom">
                                    <option value="Dubai">Dubai</option>
                                    <option value="Chennai">Chennai</option>
                                    <option value="Bengaluru">Bengaluru</option>
                                </select>
                            </div>
                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" id="nri_updates" checked>
                                <label class="form-check-label small text-muted" for="nri_updates">
                                    Get updates on WhatsApp
                                </label>
                            </div>
                            <button type="submit" class="btn btn-warning text-dark fw-bold w-100 py-2.5" style="border-radius:6px; background-color:#f59e0b; border-color:#f59e0b;">Enquire Now</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- NRI listings grid -->
    <div class="container my-5">
        <div class="text-center mb-5">
            <h2 class="fw-bold text-uppercase" style="letter-spacing:1px; color:var(--blue-brand);">Exclusive Luxury Suites</h2>
            <p class="text-muted">Oceanfront sky penthouses and high-yield properties curated for NRI investors.</p>
        </div>

        <div class="row g-4">
            <?php if (count($flats) > 0): ?>
                <?php foreach ($flats as $flat): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card card-premium h-100 border-0 shadow-sm d-flex flex-column justify-content-between">
                            <div>
                                <div class="position-relative overflow-hidden rounded-top" style="height:240px;">
                                    <?php $n_photos = parse_flat_photos($flat['image_url'] ?? ''); ?>
                                    <img src="<?php echo $base_path . htmlspecialchars($n_photos['luxury']); ?>" class="w-100 h-100" style="object-fit:cover;" alt="NRI suite">
                                    <span class="position-absolute bottom-0 start-0 m-3 badge bg-success text-white fw-bold px-3 py-2 text-uppercase" style="font-size:0.7rem;">
                                        High Yield Asset
                                    </span>
                                </div>
                                <div class="card-body p-4">
                                    <h4 class="fw-bold mb-2" style="color:var(--blue-brand);"><?php echo htmlspecialchars($flat['block']); ?></h4>
                                    <div class="text-muted small mb-2"><i class="fa-solid fa-location-dot me-1 text-danger"></i> <?php echo htmlspecialchars($flat['city']); ?></div>
                                    <p class="text-muted small"><?php echo htmlspecialchars($flat['description']); ?></p>
                                    <div class="d-flex justify-content-between align-items-center mt-3 border-top pt-3">
                                        <span class="fs-5 fw-bold text-success">₹<?php echo number_format($flat['price'], 2); ?></span>
                                        <span class="small text-muted"><i class="fa-solid fa-hotel me-1"></i> Penthouse</span>
                                    </div>
                                </div>
                            </div>
                            <div class="p-4 pt-0">
                                <a href="resident/checkout.php?flat_id=<?php echo $flat['id']; ?>" class="btn btn-primary-custom w-100 py-2 text-white fw-bold" style="background-color:var(--blue-brand); border-color:var(--blue-brand);">
                                    <i class="fa-solid fa-house-circle-check me-2"></i>Book Suite
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

<?php elseif ($active_type === 'Ventures'): ?>
    <!-- ------------------------------------------------------------- -->
    <!-- OUR VENTURES VIEW (Screenshot 5 Layout) -->
    <!-- ------------------------------------------------------------- -->
    <div class="position-relative w-100 overflow-hidden mb-5" style="height: 380px; background: linear-gradient(rgba(0,0,0,0.55), rgba(0,0,0,0.55)), url('<?php echo $base_path; ?>images/ventures_banner.png') center/cover no-repeat;">
        <div class="position-absolute start-50 top-50 translate-middle text-center w-100 px-3">
            <h1 class="display-4 fw-bold text-uppercase text-white tracking-widest text-shadow mb-2" style="font-family:'Plus Jakarta Sans', sans-serif;">Jarvis Group of Companies</h1>
            <p class="lead text-white text-shadow text-uppercase" style="letter-spacing:4px; font-size:0.95rem;">Our Diversified Ventures</p>
        </div>
    </div>

    <div class="container my-5 py-3">
        <div class="row g-4 text-center">
            <!-- Venture 1: staylogy -->
            <div class="col-md-6 col-lg-4">
                <div class="card card-premium p-4 h-100 shadow-sm border border-secondary border-opacity-10 d-flex flex-column justify-content-between">
                    <div class="text-center">
                        <div class="mb-3 d-flex justify-content-center align-items-center gap-2">
                            <i class="fa-solid fa-house-user text-warning fs-3"></i>
                            <h4 class="fw-bold mb-0 text-dark" style="font-family:'Plus Jakarta Sans', sans-serif;">staylogy</h4>
                        </div>
                        <span class="badge bg-light text-secondary border px-3 py-1.5 small mb-3">CO-LIVING LIFESTYLE</span>
                        <p class="text-muted small">High-end, fully managed co-living and community living spaces built for students and young corporate professionals.</p>
                    </div>
                    <div class="mt-3">
                        <a href="javascript:void(0)" class="btn btn-sm btn-outline-warning w-100 fw-bold">Visit Website</a>
                    </div>
                </div>
            </div>

            <!-- Venture 2: Jarvis Residential Development -->
            <div class="col-md-6 col-lg-4">
                <div class="card card-premium p-4 h-100 shadow-sm border border-secondary border-opacity-10 d-flex flex-column justify-content-between">
                    <div class="text-center">
                        <div class="mb-3 d-flex justify-content-center align-items-center gap-2">
                            <i class="fa-solid fa-house-chimney text-warning fs-3"></i>
                            <h4 class="fw-bold mb-0 text-dark" style="font-family:'Plus Jakarta Sans', sans-serif;">Jarvis Residences</h4>
                        </div>
                        <span class="badge bg-light text-secondary border px-3 py-1.5 small mb-3">RESIDENTIAL DEVELOPMENT</span>
                        <p class="text-muted small">Premium luxury villas, smart apartments, and beach mansions built with top-tier finishes and smart integrations.</p>
                    </div>
                    <div class="mt-3">
                        <a href="javascript:void(0)" class="btn btn-sm btn-outline-warning w-100 fw-bold">Visit Website</a>
                    </div>
                </div>
            </div>

            <!-- Venture 3: Jarvis Spaceintell Commercial -->
            <div class="col-md-6 col-lg-4">
                <div class="card card-premium p-4 h-100 shadow-sm border border-secondary border-opacity-10 d-flex flex-column justify-content-between">
                    <div class="text-center">
                        <div class="mb-3 d-flex justify-content-center align-items-center gap-2">
                            <i class="fa-solid fa-building text-warning fs-3"></i>
                            <h4 class="fw-bold mb-0 text-dark" style="font-family:'Plus Jakarta Sans', sans-serif;">Jarvis Spaceintell</h4>
                        </div>
                        <span class="badge bg-light text-secondary border px-3 py-1.5 small mb-3">COMMERCIAL REAL ESTATE</span>
                        <p class="text-muted small">Elite office spaces, software tech parks, and commercial suites equipped with executive meeting complexes.</p>
                    </div>
                    <div class="mt-3">
                        <a href="javascript:void(0)" class="btn btn-sm btn-outline-warning w-100 fw-bold">Visit Website</a>
                    </div>
                </div>
            </div>

            <!-- Venture 4: Jarvis Warehousing & Logistics -->
            <div class="col-md-6 col-lg-4">
                <div class="card card-premium p-4 h-100 shadow-sm border border-secondary border-opacity-10 d-flex flex-column justify-content-between">
                    <div class="text-center">
                        <div class="mb-3 d-flex justify-content-center align-items-center gap-2">
                            <i class="fa-solid fa-warehouse text-warning fs-3"></i>
                            <h4 class="fw-bold mb-0 text-dark" style="font-family:'Plus Jakarta Sans', sans-serif;">Jarvis Logistics</h4>
                        </div>
                        <span class="badge bg-light text-secondary border px-3 py-1.5 small mb-3">INDUSTRIAL & WAREHOUSING</span>
                        <p class="text-muted small">Heavy industrial logiparks, dock systems, high-ceiling cargo warehouses, and corporate manufacturing parks.</p>
                    </div>
                    <div class="mt-3">
                        <a href="javascript:void(0)" class="btn btn-sm btn-outline-warning w-100 fw-bold">Visit Website</a>
                    </div>
                </div>
            </div>

            <!-- Venture 5: Jarvis PropCare -->
            <div class="col-md-6 col-lg-4">
                <div class="card card-premium p-4 h-100 shadow-sm border border-secondary border-opacity-10 d-flex flex-column justify-content-between">
                    <div class="text-center">
                        <div class="mb-3 d-flex justify-content-center align-items-center gap-2">
                            <i class="fa-solid fa-screwdriver-wrench text-warning fs-3"></i>
                            <h4 class="fw-bold mb-0 text-dark" style="font-family:'Plus Jakarta Sans', sans-serif;">Jarvis PropCare</h4>
                        </div>
                        <span class="badge bg-light text-secondary border px-3 py-1.5 small mb-3">FACILITY MANAGEMENT</span>
                        <p class="text-muted small">Smart maintenance, 24/7 power backup controls, safety services, and property management facility care.</p>
                    </div>
                    <div class="mt-3">
                        <a href="javascript:void(0)" class="btn btn-sm btn-outline-warning w-100 fw-bold">Visit Website</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php elseif ($active_type === 'Investors'): ?>
    <!-- ------------------------------------------------------------- -->
    <!-- INVESTORS PAGE VIEW -->
    <!-- ------------------------------------------------------------- -->
    <div class="position-relative w-100 overflow-hidden mb-5" style="height: 380px; background: linear-gradient(rgba(0,0,0,0.55), rgba(0,0,0,0.55)), url('<?php echo $base_path; ?>images/nri_banner.png') center/cover no-repeat;">
        <div class="position-absolute start-50 top-50 translate-middle text-center w-100 px-3">
            <h1 class="display-4 fw-bold text-uppercase text-white tracking-widest text-shadow mb-2" style="font-family:'Plus Jakarta Sans', sans-serif;">Investor Relations</h1>
            <p class="lead text-white text-shadow text-uppercase" style="letter-spacing:4px; font-size:0.95rem;">Delivering Consistent Value & Market Leadership</p>
        </div>
    </div>

    <div class="container my-5 py-3">
        <!-- Key Stats Row -->
        <div class="row g-4 text-center mb-5">
            <div class="col-md-3">
                <div class="p-4 bg-white rounded shadow-sm border border-secondary border-opacity-10">
                    <h3 class="fw-extrabold text-primary mb-1">₹4,200 Cr+</h3>
                    <span class="text-muted small text-uppercase fw-bold">Market Capitalization</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-4 bg-white rounded shadow-sm border border-secondary border-opacity-10">
                    <h3 class="fw-extrabold text-success mb-1">22.4%</h3>
                    <span class="text-muted small text-uppercase fw-bold">Annual Return on Equity</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-4 bg-white rounded shadow-sm border border-secondary border-opacity-10">
                    <h3 class="fw-extrabold text-warning mb-1">32+ Million</h3>
                    <span class="text-muted small text-uppercase fw-bold">Sq. Ft. Area Delivered</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-4 bg-white rounded shadow-sm border border-secondary border-opacity-10">
                    <h3 class="fw-extrabold text-info mb-1">A+ Stable</h3>
                    <span class="text-muted small text-uppercase fw-bold">ICRA Credit Rating</span>
                </div>
            </div>
        </div>

        <div class="row g-5">
            <!-- Left Info Block -->
            <div class="col-lg-7">
                <h3 class="fw-bold mb-3" style="color:var(--blue-brand);">Investment Opportunities</h3>
                <p class="text-muted mb-4">Jarvis Real Estate provides high-growth commercial lease options, land development joint-ventures, and premium residential portfolios yielding strong capital appreciation and consistent dividend rental flows.</p>
                
                <div class="card card-premium p-4 border-0 shadow-sm mb-4">
                    <h5 class="fw-bold mb-3"><i class="fa-solid fa-chart-line text-warning me-2"></i>Financial Performance & Growth</h5>
                    <p class="small text-muted mb-0">Our structured property investments operate with zero debt-leverage on land acquisitions, ensuring low-risk downside protection for our institutional and retail shareholders.</p>
                </div>

                <div class="card card-premium p-4 border-0 shadow-sm">
                    <h5 class="fw-bold mb-3"><i class="fa-solid fa-file-invoice-dollar text-warning me-2"></i>Download Investor Kit</h5>
                    <p class="small text-muted mb-3">Get access to our latest Q4 Financial Audit Report, Project Growth Pipelines, and Shareholder presentation slides.</p>
                    <a href="javascript:void(0)" onclick="alert('Downloading Q4 Financial Report PDF...')" class="btn btn-sm btn-outline-primary fw-bold" style="max-width:220px;"><i class="fa-solid fa-download me-1"></i> Download Q4 Report (PDF)</a>
                </div>
            </div>

            <!-- Right ROI Calculator -->
            <div class="col-lg-5">
                <div class="card p-4 border-0 shadow-sm" style="background-color:rgba(16,49,120,0.03); border-radius:12px; border-top:4px solid var(--orange-brand) !important;">
                    <h5 class="fw-bold text-dark text-center text-uppercase mb-1" style="font-size:0.95rem; letter-spacing:0.5px;">ROI Yield Estimator</h5>
                    <p class="text-muted text-center small mb-4">Calculate estimated returns on commercial property portfolios.</p>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark mb-1">Investment Principal (₹)</label>
                        <input type="number" id="invPrincipal" class="form-control form-control-custom" value="1000000" oninput="calculateROI()">
                    </div>
                    <div class="row g-2 mb-4">
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-dark mb-1">Expected Yield (%)</label>
                            <input type="number" id="invYield" class="form-control form-control-custom" value="8.5" step="0.1" oninput="calculateROI()">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-dark mb-1">Tenure (Years)</label>
                            <input type="number" id="invYears" class="form-control form-control-custom" value="5" oninput="calculateROI()">
                        </div>
                    </div>

                    <div class="p-3 bg-white rounded shadow-sm text-center border">
                        <span class="text-muted small d-block">Estimated Total Rental Return</span>
                        <h4 class="fw-extrabold text-success mb-0 mt-1" id="roiOutput">₹425,000.00</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Script for ROI Calculator -->
    <script>
    function calculateROI() {
        const principal = parseFloat(document.getElementById('invPrincipal').value) || 0;
        const yieldRate = parseFloat(document.getElementById('invYield').value) || 0;
        const years = parseFloat(document.getElementById('invYears').value) || 0;
        const output = document.getElementById('roiOutput');
        if (!output) return;

        if (principal <= 0 || yieldRate <= 0 || years <= 0) {
            output.innerText = "₹0.00";
            return;
        }

        // Return = P * (yield / 100) * years
        const returns = principal * (yieldRate / 100) * years;
        output.innerText = "₹" + returns.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
    // Initial run
    document.addEventListener('DOMContentLoaded', () => {
        calculateROI();
    });
    </script>
<?php endif; ?>

<!-- Comparison Floating Drawer markup -->
<div class="comparison-drawer" id="comparisonDrawer">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <i class="fa-solid fa-right-left text-warning me-2 fs-5"></i> 
            <span class="fw-bold">Compare Properties (<span id="compareCount">0</span> selected)</span>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-light rounded-pill px-3" onclick="clearComparison()">Clear</button>
            <button class="btn btn-sm btn-warning text-dark fw-bold rounded-pill px-3" onclick="openComparisonModal()">Compare Now</button>
        </div>
    </div>
</div>

<!-- Comparison Detail Table Modal -->
<div class="modal fade" id="comparisonModal" tabindex="-1" aria-labelledby="comparisonModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="comparisonModalLabel"><i class="fa-solid fa-right-left text-warning me-2"></i>Compare Selected Units</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 overflow-auto">
                <table class="table table-bordered table-striped text-center align-middle" style="font-size:0.85rem;" id="comparisonTable">
                    <!-- Dynamic rendering in JS -->
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Site Visit Scheduler Modal -->
<div class="modal fade" id="visitModal" tabindex="-1" aria-labelledby="visitModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="visitModalLabel"><i class="fa-regular fa-calendar-check text-warning me-2"></i>Schedule Physical Site Visit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?php echo $current_page; ?>" method="POST">
                <input type="hidden" name="action" value="schedule_visit">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="v_name" class="form-label fw-bold">Your Name</label>
                        <input type="text" class="form-control form-control-custom" id="v_name" name="visit_name" placeholder="Enter your full name" required>
                    </div>
                    <div class="mb-3">
                        <label for="v_phone" class="form-label fw-bold">Phone Number</label>
                        <input type="tel" class="form-control form-control-custom" id="v_phone" name="visit_phone" placeholder="e.g. +91 80562 10606" required>
                    </div>
                    <div class="row g-2">
                        <div class="col-6 mb-3">
                            <label for="v_date" class="form-label fw-bold">Visit Date</label>
                            <input type="date" class="form-control form-control-custom" id="v_date" name="visit_date" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label for="v_time" class="form-label fw-bold">Visit Time</label>
                            <select class="form-select form-control-custom" id="v_time" name="visit_time">
                                <option value="10:00 AM">10:00 AM</option>
                                <option value="12:00 PM">12:00 PM</option>
                                <option value="02:00 PM">02:00 PM</option>
                                <option value="04:00 PM">04:00 PM</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary-custom btn-sm py-2 px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-call-dropdown py-2 px-4">Confirm Visit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Inquiry Modal -->
<div class="modal fade" id="inquiryModal" tabindex="-1" aria-labelledby="inquiryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="inquiryModalLabel"><i class="fa-regular fa-clipboard text-warning me-2"></i>Register Property Inquiry</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?php echo $current_page; ?>" method="POST">
                <input type="hidden" name="action" value="add_inquiry">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="inq_name" class="form-label fw-bold">Full Name</label>
                        <input type="text" class="form-control form-control-custom" id="inq_name" name="name" placeholder="Enter your name" required>
                    </div>
                    <div class="mb-3">
                        <label for="inq_phone" class="form-label fw-bold">Phone Number</label>
                        <input type="tel" class="form-control form-control-custom" id="inq_phone" name="phone" placeholder="e.g. +91 80562 10606" required>
                    </div>
                    <div class="mb-3">
                        <label for="inq_city" class="form-label fw-bold">Preferred City Location</label>
                        <select class="form-select form-control-custom" id="inq_city" name="city">
                            <option value="Chennai">Chennai</option>
                            <option value="Bengaluru">Bengaluru</option>
                            <option value="Coimbatore">Coimbatore</option>
                            <option value="Hyderabad">Hyderabad</option>
                            <option value="Dubai">Dubai</option>
                            <option value="Pune">Pune</option>
                            <option value="Delhi">Delhi</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary-custom btn-sm py-2 px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-call-dropdown py-2 px-4">Submit Inquiry</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Chatbot Assistant JavaScript Simulator -->
<script>
function toggleChat() {
    const box = document.getElementById('chatBox');
    if (box.style.display === 'none' || box.style.display === '') {
        box.style.display = 'flex';
        scrollChatToBottom();
    } else {
        box.style.display = 'none';
    }
}

function sendChatMessage() {
    const input = document.getElementById('chatInput');
    const msgText = input.value.trim();
    if (msgText === '') return;

    // Append User message bubble
    appendChatBubble(msgText, 'user');
    input.value = '';
    
    // Simulate Bot Response delay
    setTimeout(() => {
        const botReply = generateBotReply(msgText);
        appendChatBubble(botReply, 'bot');
    }, 900);
}

function appendChatBubble(text, sender) {
    const chatBody = document.getElementById('chatBody');
    
    // Create row container
    const row = document.createElement('div');
    row.className = `chat-msg-row ${sender}-row`;
    
    if (sender === 'bot') {
        const avatar = document.createElement('div');
        avatar.className = 'bot-avatar-mini';
        avatar.innerHTML = '<i class="fa-solid fa-atom"></i>';
        row.appendChild(avatar);
    }
    
    const bubble = document.createElement('div');
    bubble.className = `chat-msg ${sender}`;
    bubble.innerText = text;
    
    row.appendChild(bubble);
    chatBody.appendChild(row);
    scrollChatToBottom();
}

function scrollChatToBottom() {
    const chatBody = document.getElementById('chatBody');
    chatBody.scrollTop = chatBody.scrollHeight;
}

function generateBotReply(userText) {
    const txt = userText.toLowerCase();
    
    if (txt.includes('hi') || txt.includes('hello') || txt.includes('hey')) {
        return "Hello! I can guide you through our luxury apartment projects in Chennai, Bengaluru, and Dubai. What location are you interested in?";
    }
    if (txt.includes('chennai')) {
        return "In Chennai, we have premium 2 & 3 BHK suites at Casagrand Elan and Woodside, starting from ₹85,000. Would you like to schedule a call?";
    }
    if (txt.includes('bengaluru') || txt.includes('bangalore')) {
        return "In Bengaluru, we offer luxury units at Casagrand Lorenza and Zenith starting from ₹110,000. Bookings are open!";
    }
    if (txt.includes('price') || txt.includes('pricing') || txt.includes('cost') || txt.includes('rate')) {
        return "Our luxury apartments start from ₹75,000 in Coimbatore, up to ₹320,000 for our luxury penthouses in Dubai. You can view all pricing details in the grid catalog below!";
    }
    if (txt.includes('dubai')) {
        return "Our landmark in Dubai is the Casagrand Marina penthouse, priced at ₹320,000. It features scenic bay views and a private sky pool.";
    }
    return "Thanks for reaching out! You can easily register an inquiry by clicking the clipboard icon in the sidebar, or call us directly at +91 80562 10606.";
}

// -------------------------------------------------------------
// Interactive Suite Helper Routines (Calculators, Wishlist, Compare)
// -------------------------------------------------------------

// 1. EMI Calculator logic
function calculateEMI() {
    const amountInput = document.getElementById('emiAmount');
    const rateInput = document.getElementById('emiRate');
    const yearsInput = document.getElementById('emiYears');
    const output = document.getElementById('emiOutput');
    if (!amountInput || !rateInput || !yearsInput || !output) return;

    const P = parseFloat(amountInput.value) || 0;
    const r = (parseFloat(rateInput.value) / 12) / 100;
    const n = (parseFloat(yearsInput.value) || 0) * 12;
    
    if (P <= 0 || r <= 0 || n <= 0) {
        output.innerText = "₹0.00";
        return;
    }

    const emi = (P * r * Math.pow(1 + r, n)) / (Math.pow(1 + r, n) - 1);
    output.innerText = "₹" + emi.toFixed(2);
}

// 2. Loan Eligibility Calculator logic
function calculateEligibility() {
    const incomeInput = document.getElementById('incomeAmount');
    const existingInput = document.getElementById('incomeExisting');
    const yearsInput = document.getElementById('eligibilityYears');
    const output = document.getElementById('eligibilityOutput');
    if (!incomeInput || !existingInput || !yearsInput || !output) return;

    const income = parseFloat(incomeInput.value) || 0;
    const existing = parseFloat(existingInput.value) || 0;
    const years = parseFloat(yearsInput.value) || 0;

    // FOIR (Fixed Obligation to Income Ratio) calculation: assume max 50% salary goes to EMI
    const maxAllowedEMI = (income * 0.5) - existing;
    if (maxAllowedEMI <= 0 || years <= 0) {
        output.innerText = "₹0.00";
        return;
    }

    // Solve for P: EMI = P * r * (1+r)^n / ((1+r)^n - 1)
    const r = (7.5 / 12) / 100; // default standard interest rate
    const n = years * 12;
    const factor = (r * Math.pow(1 + r, n)) / (Math.pow(1 + r, n) - 1);
    
    const principal = maxAllowedEMI / factor;
    output.innerText = "₹" + principal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

// 3. Wishlist localStorage storage routines
function toggleWishlist(flatId, btn) {
    let wishlist = JSON.parse(localStorage.getItem('wishlist')) || [];
    const index = wishlist.indexOf(flatId);
    
    if (index > -1) {
        wishlist.splice(index, 1);
        btn.classList.remove('active');
    } else {
        wishlist.push(flatId);
        btn.classList.add('active');
    }
    localStorage.setItem('wishlist', JSON.stringify(wishlist));
}

// Pre-fill active wishlist states
document.addEventListener('DOMContentLoaded', () => {
    const wishlist = JSON.parse(localStorage.getItem('wishlist')) || [];
    const heartBtns = document.querySelectorAll('.wishlist-heart-btn');
    heartBtns.forEach(btn => {
        const id = parseInt(btn.getAttribute('data-flat-id'));
        if (wishlist.includes(id)) {
            btn.classList.add('active');
        }
    });
    
    // Trigger calculators default calculations
    calculateEMI();
    calculateEligibility();
});

// 4. Comparison logic
function updateCompareCount() {
    const checkboxes = document.querySelectorAll('.compare-check:checked');
    const drawer = document.getElementById('comparisonDrawer');
    const count = document.getElementById('compareCount');
    if (!drawer || !count) return;

    if (checkboxes.length > 0) {
        drawer.style.display = 'block';
        count.innerText = checkboxes.length;
    } else {
        drawer.style.display = 'none';
    }
}

// Clear checks
function clearComparison() {
    const checkboxes = document.querySelectorAll('.compare-check');
    checkboxes.forEach(c => c.checked = false);
    updateCompareCount();
}

function openComparisonModal() {
    const checkboxes = document.querySelectorAll('.compare-check:checked');
    const table = document.getElementById('comparisonTable');
    if (checkboxes.length === 0 || !table) return;

    let headers = '<tr><th class="text-start">Feature</th>';
    let names = '<tr><td class="text-start fw-bold">Name</td>';
    let prices = '<tr><td class="text-start fw-bold">Price</td>';
    let bhkList = '<tr><td class="text-start fw-bold">Layout</td>';
    let cities = '<tr><td class="text-start fw-bold">Location</td>';

    checkboxes.forEach((cb, idx) => {
        headers += `<th>Unit ${cb.value}</th>`;
        names += `<td>${cb.getAttribute('data-name')}</td>`;
        prices += `<td class="text-success fw-bold">${cb.getAttribute('data-price')}</td>`;
        bhkList += `<td>${cb.getAttribute('data-bhk')}</td>`;
        cities += `<td>${cb.getAttribute('data-city')}</td>`;
    });

    headers += '</tr>';
    names += '</tr>';
    prices += '</tr>';
    bhkList += '</tr>';
    cities += '</tr>';

    table.innerHTML = headers + names + prices + bhkList + cities;
    
    // Open modal
    const compModal = new bootstrap.Modal(document.getElementById('comparisonModal'));
    compModal.show();
}

// 5. Brochure Download Simulation
function simulateBrochureDownload() {
    alert("Project Brochure download started! Your system is downloading: Jarvis_Premium_Residences_Map.pdf");
}
</script>

<?php require_once 'footer.php'; ?>
