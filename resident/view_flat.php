<?php
require_once '../includes/db.php';
require_once '../includes/header.php';

// Accept both 'flat_id' and 'id' parameter names
$flat_id = 0;
if (isset($_GET['flat_id']) && intval($_GET['flat_id']) > 0) {
    $flat_id = intval($_GET['flat_id']);
} elseif (isset($_GET['id']) && intval($_GET['id']) > 0) {
    $flat_id = intval($_GET['id']);
}

$flat = null;

try {
    if ($flat_id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM flats WHERE id = :id");
        $stmt->execute(['id' => $flat_id]);
        $flat = $stmt->fetch();
    }
    
    // Fallback: If no valid ID was supplied or flat ID doesn't exist, load the first flat automatically
    if (!$flat) {
        $stmt = $pdo->query("SELECT * FROM flats ORDER BY id ASC LIMIT 1");
        $flat = $stmt->fetch();
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}

if (!$flat) {
    echo "<div class='container my-5 text-center py-5'><div class='card card-premium p-5 max-width-600 mx-auto border-0 shadow-sm rounded-4'><i class='fa-solid fa-building-circle-exclamation fa-4x text-warning mb-3'></i><h3 class='fw-bold text-dark mb-2'>Apartment Details Unavailable</h3><p class='text-muted mb-4'>Please explore our available residential inventory or return to the main catalog.</p><a href='../flats.php' class='btn btn-warning-custom px-4 py-2.5 font-semibold'><i class='fa-solid fa-list me-2'></i>Explore All Available Flats</a></div></div>";
    require_once '../includes/footer.php';
    exit;
}

// Set up image path helper
$img_base = '../images/';

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

// Ordinal floor formatter helper
function format_floor_number($floor_num) {
    $floor_num = intval($floor_num);
    if ($floor_num == 1) return "1st Floor";
    if ($floor_num == 2) return "2nd Floor";
    if ($floor_num == 3) return "3rd Floor";
    return $floor_num . "th Floor";
}

// Realistic EMI & Financial Calculations (Luxury 3 BHK Suite: ₹1.45 Crore benchmark)
$raw_price = floatval($flat['price']);
$total_flat_price = ($raw_price < 1000000) ? 14500000 : $raw_price; // ₹1,45,00,000 (1.45 Cr)
$down_payment = $total_flat_price * 0.20; // 20% = ₹29,00,000
$loan_amount = $total_flat_price * 0.80;  // 80% = ₹1,16,00,000
$annual_rate = 0.085; // 8.5% p.a.
$monthly_rate = $annual_rate / 12;
$tenure_years = 20;
$tenure_months = $tenure_years * 12; // 240 months

// Standard Indian Bank EMI Formula: P * r * (1+r)^n / ((1+r)^n - 1)
$emi_monthly = ($loan_amount * $monthly_rate * pow(1 + $monthly_rate, $tenure_months)) / (pow(1 + $monthly_rate, $tenure_months) - 1);
$total_payable = $down_payment + ($emi_monthly * $tenure_months);
?>

<style>
.thumb-card.active-thumb-glow {
    border: 3px solid #f2a122 !important;
    box-shadow: 0 0 12px rgba(242, 161, 34, 0.65) !important;
    transform: scale(1.03);
    transition: all 0.2s ease-in-out;
}
.thumb-card {
    transition: all 0.2s ease-in-out;
    opacity: 0.8;
}
.thumb-card:hover {
    opacity: 1;
    transform: translateY(-2px);
}
.financial-table, 
.financial-table tr, 
.financial-table th, 
.financial-table td {
    background: transparent !important;
    background-color: transparent !important;
    color: #ffffff !important;
    padding: 0.6rem 0.75rem;
    font-size: 0.88rem;
}
.financial-table td.text-white-50 {
    color: rgba(255, 255, 255, 0.7) !important;
}
.financial-table td.text-warning {
    color: #f2a122 !important;
}
</style>

<div class="container my-5">
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <a href="../flats.php" class="btn btn-secondary-custom btn-sm"><i class="fa-solid fa-arrow-left me-1"></i> Back to All Flats</a>
        <button id="wishlistBtn" class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="toggleWishlist()"><i class="fa-regular fa-heart me-1"></i> Save to Wishlist</button>
    </div>

    <div class="row g-4 align-items-stretch">
        <!-- 1. Interactive Room & Facility Showcase Column (Left - Full Height to Touch Footer) -->
        <div class="col-lg-6 d-flex flex-column">
            <div class="card card-premium p-4 bg-dark text-white border-0 shadow-lg h-100 d-flex flex-column">
                <div class="mb-3">
                    <h5 class="fw-bold mb-1 text-white"><i class="fa-solid fa-images text-warning me-2"></i>Interactive Room & Facility Showcase</h5>
                    <p class="text-white-50 small mb-0" style="font-size: 0.8rem;">Click any thumbnail below or use the Next/Prev buttons to preview room layouts.</p>
                </div>

                <!-- Main Featured Display Image (Grand 540px Height) -->
                <div class="position-relative overflow-hidden rounded-3 shadow-sm mb-3" style="height: 540px; background-color: #111;">
                    <img id="mainShowcaseImg" src="<?php echo $gallery_items[0]['src']; ?>" class="img-fluid w-100 h-100" style="object-fit: cover; transition: opacity 0.2s ease-in-out;" alt="Featured Room Preview" onerror="this.src='https://placehold.co/800x600/1e293b/ffffff?text=Room+Preview'">
                    
                    <!-- Compact Inset Next Button Overlay -->
                    <button class="btn btn-warning text-dark fw-bold position-absolute shadow-sm rounded-pill px-3 py-1.5 d-flex align-items-center gap-1.5" style="top: 14px; right: 14px; z-index: 12; cursor: pointer; opacity: 0.95; font-size: 0.78rem; background: #f59e0b; border:none;" onclick="nextShowcase()">
                        <span>Next Image</span>
                        <i class="fa-solid fa-chevron-right" style="font-size: 0.7rem;"></i>
                    </button>

                    <!-- Floating Previous & Next Slide Arrow Buttons -->
                    <button class="btn btn-dark bg-opacity-70 text-white rounded-circle position-absolute top-50 start-0 translate-middle-y ms-2.5 p-0 d-flex align-items-center justify-content-center border border-white border-opacity-30 shadow" style="width: 38px; height: 38px; z-index: 10; cursor: pointer;" onclick="prevShowcase()" title="Previous Image">
                        <i class="fa-solid fa-chevron-left fs-6"></i>
                    </button>
                    <button class="btn btn-dark bg-opacity-70 text-white rounded-circle position-absolute top-50 end-0 translate-middle-y me-2.5 p-0 d-flex align-items-center justify-content-center border border-white border-opacity-30 shadow" style="width: 38px; height: 38px; z-index: 10; cursor: pointer;" onclick="nextShowcase()" title="Next Image">
                        <i class="fa-solid fa-chevron-right fs-6"></i>
                    </button>

                    <!-- Bottom Title & Counter Overlay Bar -->
                    <div class="position-absolute bottom-0 start-0 w-100 p-3 text-white d-flex justify-content-between align-items-center" style="background: linear-gradient(0deg, rgba(0,0,0,0.88) 0%, rgba(0,0,0,0) 100%); z-index: 11;">
                        <div>
                            <span class="badge bg-warning text-dark fw-bold mb-1 text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px; background-color: #f59e0b !important;">Active View</span>
                            <h6 id="mainShowcaseTitle" class="fw-bold text-white mb-0" style="font-size: 0.98rem;"><?php echo htmlspecialchars($gallery_items[0]['title']); ?></h6>
                        </div>
                        <div>
                            <span id="showcaseCounter" class="badge bg-dark bg-opacity-85 text-warning border border-warning px-2.5 py-1 fw-bold fs-7 shadow">1 / 4</span>
                        </div>
                    </div>
                </div>

                <!-- Clickable Thumbnails Bar (4 Dedicated Slots: Exterior, Interior, Amenities, Luxury) -->
                <div class="row g-2 mb-4">
                    <?php foreach ($gallery_items as $index => $item): ?>
                        <div class="col-3">
                            <div class="thumb-card <?php echo $index === 0 ? 'active-thumb-glow' : ''; ?> position-relative overflow-hidden rounded-2" onclick="selectShowcase(<?php echo $index; ?>)" style="height: 75px; cursor: pointer;" title="<?php echo htmlspecialchars($item['label'] . ' - ' . $item['title']); ?>">
                                <img src="<?php echo $item['src']; ?>" class="w-100 h-100" style="object-fit: cover;" alt="<?php echo htmlspecialchars($item['title']); ?>">
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Architectural Room Dimensions & Interior Features -->
                <div class="border-top border-secondary border-opacity-25 pt-4 mt-auto">
                    <h5 class="fw-bold mb-3 text-white fs-6"><i class="fa-solid fa-ruler-combined text-warning me-2 fs-5"></i>Architectural Room Dimensions</h5>
                    <div class="row g-2 text-white small mb-3">
                        <div class="col-6">
                            <div class="p-3 rounded-3" style="background: rgba(255, 255, 255, 0.12); border: 1.5px solid rgba(245, 158, 11, 0.7); box-shadow: 0 4px 12px rgba(0,0,0,0.25);">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <i class="fa-solid fa-couch text-warning fs-6"></i>
                                    <span class="text-warning fw-bold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Living Area</span>
                                </div>
                                <strong class="text-white d-block fs-6 fw-bold">24' × 16' <span class="fw-bold text-warning fs-7">(384 Sq.Ft.)</span></strong>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded-3" style="background: rgba(255, 255, 255, 0.12); border: 1.5px solid rgba(245, 158, 11, 0.7); box-shadow: 0 4px 12px rgba(0,0,0,0.25);">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <i class="fa-solid fa-bed text-warning fs-6"></i>
                                    <span class="text-warning fw-bold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Master Bedroom</span>
                                </div>
                                <strong class="text-white d-block fs-6 fw-bold">18' × 14' <span class="fw-bold text-warning fs-7">(252 Sq.Ft.)</span></strong>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded-3" style="background: rgba(255, 255, 255, 0.12); border: 1.5px solid rgba(245, 158, 11, 0.7); box-shadow: 0 4px 12px rgba(0,0,0,0.25);">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <i class="fa-solid fa-utensils text-warning fs-6"></i>
                                    <span class="text-warning fw-bold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Modular Kitchen</span>
                                </div>
                                <strong class="text-white d-block fs-6 fw-bold">16' × 12' <span class="fw-bold text-warning fs-7">(192 Sq.Ft.)</span></strong>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded-3" style="background: rgba(255, 255, 255, 0.12); border: 1.5px solid rgba(245, 158, 11, 0.7); box-shadow: 0 4px 12px rgba(0,0,0,0.25);">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <i class="fa-solid fa-sun text-warning fs-6"></i>
                                    <span class="text-warning fw-bold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Private Balcony</span>
                                </div>
                                <strong class="text-white d-block fs-6 fw-bold">14' × 6' <span class="fw-bold text-warning fs-7">(84 Sq.Ft.)</span></strong>
                            </div>
                        </div>
                    </div>

                    <!-- Key Interior Specs Badges -->
                    <div class="p-3.5 rounded-3 bg-dark border border-secondary border-opacity-50 mb-3 shadow-sm">
                        <h6 class="fw-bold mb-2.5 text-warning fs-6 d-flex align-items-center gap-2">
                            <i class="fa-solid fa-gem text-warning fs-5"></i>
                            <span>Luxury Interior Highlights</span>
                        </h6>
                        <ul class="list-unstyled mb-0 small text-white d-flex flex-column gap-2">
                            <li class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-circle-check text-warning fs-6"></i>
                                <div><strong class="text-white">Italian Vitrified Marble Flooring</strong> <span class="text-white-75">throughout dry areas</span></div>
                            </li>
                            <li class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-circle-check text-warning fs-6"></i>
                                <div><strong class="text-white">Soundproof Double-Glazed Windows</strong> <span class="text-white-75">for zero urban noise</span></div>
                            </li>
                            <li class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-circle-check text-warning fs-6"></i>
                                <div><strong class="text-white">Floor-to-Ceiling Panoramic Glass</strong> <span class="text-white-75">with city skyline views</span></div>
                            </li>
                        </ul>
                    </div>

                    <!-- External Building & Community Features -->
                    <div class="p-3.5 rounded-3 bg-dark border border-secondary border-opacity-50 mb-3 shadow-sm">
                        <h6 class="fw-bold mb-2.5 text-warning fs-6 d-flex align-items-center gap-2">
                            <i class="fa-solid fa-building-circle-check text-warning fs-5"></i>
                            <span>External Building & Community Features</span>
                        </h6>
                        <ul class="list-unstyled mb-0 small text-white d-flex flex-column gap-2">
                            <li class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-bolt text-warning fs-6" style="width:20px; text-align:center;"></i>
                                <div><strong class="text-white">24x7 Full Power Backup Generator:</strong> <span class="text-white-75">Uninterrupted supply for lifts & common areas</span></div>
                            </li>
                            <li class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-shield-cat text-warning fs-6" style="width:20px; text-align:center;"></i>
                                <div><strong class="text-white">3-Tier Smart Security System:</strong> <span class="text-white-75">24/7 CCTV, RFID barriers & biometric entry</span></div>
                            </li>
                            <li class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-elevator text-warning fs-6" style="width:20px; text-align:center;"></i>
                                <div><strong class="text-white">Dual High-Speed Passenger & Service Lifts:</strong> <span class="text-white-75">ARD emergency auto-rescue enabled</span></div>
                            </li>
                            <li class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-faucet-drip text-warning fs-6" style="width:20px; text-align:center;"></i>
                                <div><strong class="text-white">Central Water Softener & STP:</strong> <span class="text-white-75">Continuous 24/7 pressurized water supply</span></div>
                            </li>
                            <li class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-swimming-pool text-warning fs-6" style="width:20px; text-align:center;"></i>
                                <div><strong class="text-white">Rooftop Sports & Infinity Pool:</strong> <span class="text-white-75">Floodlit tennis court & health club access</span></div>
                            </li>
                            <li class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-square-parking text-warning fs-6" style="width:20px; text-align:center;"></i>
                                <div><strong class="text-white">Covered Multi-Level Basement Parking:</strong> <span class="text-white-75">Reserved slot with EV charging point</span></div>
                            </li>
                        </ul>
                    </div>

                    <!-- External Neighborhood & Nearby Infrastructure -->
                    <div class="p-3.5 rounded-3 bg-dark border border-secondary border-opacity-50 mb-3 shadow-sm">
                        <h6 class="fw-bold mb-2.5 text-warning fs-6 d-flex align-items-center gap-2">
                            <i class="fa-solid fa-map-location-dot text-warning fs-5"></i>
                            <span>External Neighborhood & Nearby Infrastructure</span>
                        </h6>
                        <div class="row g-2 text-white small mb-0">
                            <div class="col-6">
                                <div class="p-2.5 rounded bg-black bg-opacity-40 border border-secondary border-opacity-40">
                                    <span class="d-block text-white fw-bold"><i class="fa-solid fa-graduation-cap text-warning me-1.5"></i>MCC Tambaram</span>
                                    <span class="small text-white-75 fw-medium">2.1 km (8 Mins Drive)</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2.5 rounded bg-black bg-opacity-40 border border-secondary border-opacity-40">
                                    <span class="d-block text-white fw-bold"><i class="fa-solid fa-train text-warning me-1.5"></i>Tambaram Station</span>
                                    <span class="small text-white-75 fw-medium">3.4 km (12 Mins Drive)</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2.5 rounded bg-black bg-opacity-40 border border-secondary border-opacity-40">
                                    <span class="d-block text-white fw-bold"><i class="fa-solid fa-hospital text-warning me-1.5"></i>Global Health City</span>
                                    <span class="small text-white-75 fw-medium">5.2 km (15 Mins Drive)</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2.5 rounded bg-black bg-opacity-40 border border-secondary border-opacity-40">
                                    <span class="d-block text-white fw-bold"><i class="fa-solid fa-bag-shopping text-warning me-1.5"></i>Grand Galada Mall</span>
                                    <span class="small text-white-75 fw-medium">4.1 km (14 Mins Drive)</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- External Resident Reviews & IGBC Green Certification -->
                    <div class="p-3.5 rounded-3 bg-dark border border-secondary border-opacity-50 shadow-sm">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold mb-0 text-success fs-6"><i class="fa-solid fa-leaf me-1.5 text-success"></i>IGBC Gold Green Certified</h6>
                            <span class="badge bg-warning text-dark fw-bold px-2.5 py-1.5"><i class="fa-solid fa-star me-1 text-dark"></i>4.9 / 5.0 (128 Reviews)</span>
                        </div>
                        <p class="text-white small mb-2 fst-italic">"Exceptional 24/7 security, solar-powered common areas, zero noise pollution, and lush landscaped walkways."</p>
                        <div class="d-flex gap-3 text-white small" style="font-size: 0.8rem;">
                            <span><i class="fa-solid fa-charging-station text-warning me-1"></i><strong>EV Fast Charging</strong></span>
                            <span>•</span>
                            <span><i class="fa-solid fa-solar-panel text-warning me-1"></i><strong>Solar Power Backup</strong></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Flat Details & Pricing Column (Right - Sleek Black Luxury Theme) -->
        <div class="col-lg-6 d-flex flex-column">
            <div class="card card-premium p-4 bg-dark text-white border-0 shadow-lg d-flex flex-column">
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge bg-warning text-dark px-3 py-2 fw-bold fs-7 shadow-xs"><?php echo $flat['bhk']; ?> BHK Size</span>
                        <!-- Formatted Floor Number -->
                        <span class="badge bg-secondary text-white px-3 py-2 fw-semibold fs-7"><?php echo format_floor_number($flat['floor']); ?></span>
                        <span class="badge badge-status <?php echo $flat['status'] === 'Available' ? 'badge-vacant' : 'badge-pending'; ?> px-3 py-2 fs-7">
                            <?php echo $flat['status']; ?>
                        </span>
                    </div>

                    <h2 class="fw-extrabold text-white mb-1"><?php echo htmlspecialchars($flat['block']); ?></h2>
                    <p class="text-warning fw-semibold mb-3 fs-6">Unit <?php echo htmlspecialchars($flat['flat_no']); ?> ✦ <?php echo htmlspecialchars($flat['city']); ?></p>

                    <!-- Key Spec Cards -->
                    <div class="row g-2 mb-4 text-white">
                        <div class="col-6">
                            <div class="p-3 rounded bg-black bg-opacity-40 border border-secondary border-opacity-50">
                                <span class="d-block text-warning text-uppercase fw-bold" style="font-size: 0.72rem; letter-spacing: 0.5px;">Carpet Area</span>
                                <strong class="text-white fs-5 fw-bold"><?php echo ($flat['bhk'] * 450 + 350); ?> Sq.Ft.</strong>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded bg-black bg-opacity-40 border border-secondary border-opacity-50">
                                <span class="d-block text-warning text-uppercase fw-bold" style="font-size: 0.72rem; letter-spacing: 0.5px;">Facing Direction</span>
                                <strong class="text-white fs-6 fw-bold">East (Vaastu Compliant)</strong>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded bg-black bg-opacity-40 border border-secondary border-opacity-50">
                                <span class="d-block text-warning text-uppercase fw-bold" style="font-size: 0.72rem; letter-spacing: 0.5px;">Total Structure</span>
                                <strong class="text-white fs-6 fw-bold">G + 14 Floors</strong>
                            </div>
                        </div>
                    </div>

                    <!-- 1. Apartment Amenities in Consistent Title Case -->
                    <div class="border-top border-secondary border-opacity-25 pt-4 mb-4">
                        <h5 class="fw-bold mb-3 text-white fs-6 d-flex align-items-center gap-2">
                            <i class="fa-solid fa-circle-check text-warning fs-5"></i>
                            <span>Apartment Amenities</span>
                        </h5>
                        <ul class="list-unstyled text-white small row g-2.5 mb-0 fw-medium">
                            <li class="col-6"><i class="fa-solid fa-chevron-right me-1.5 text-warning"></i> 24/7 Water Supply</li>
                            <li class="col-6"><i class="fa-solid fa-chevron-right me-1.5 text-warning"></i> Covered Parking Slot</li>
                            <li class="col-6"><i class="fa-solid fa-chevron-right me-1.5 text-warning"></i> Modular Kitchen</li>
                            <li class="col-6"><i class="fa-solid fa-chevron-right me-1.5 text-warning"></i> Ambient LED Lighting</li>
                            <li class="col-6"><i class="fa-solid fa-chevron-right me-1.5 text-warning"></i> Gym & Pool Access</li>
                            <li class="col-6"><i class="fa-solid fa-chevron-right me-1.5 text-warning"></i> Private Balcony Area</li>
                        </ul>
                    </div>

                    <!-- Realistic Financial & Comprehensive EMI Breakdown -->
                    <div class="border-top border-secondary border-opacity-25 pt-4 mb-4">
                        <h5 class="fw-bold mb-3 text-white fs-6 d-flex align-items-center gap-2">
                            <i class="fa-solid fa-calculator text-warning fs-5"></i>
                            <span>Financial & Realistic EMI Breakdown</span>
                        </h5>
                        <div class="p-3.5 rounded bg-black bg-opacity-40 border border-secondary border-opacity-50">
                            <table class="table table-dark table-borderless table-sm mb-0 financial-table text-white" style="background: transparent !important;">
                                <tbody>
                                    <tr>
                                        <td class="text-white fw-medium py-1.5">Total Flat Price</td>
                                        <td class="text-end fw-bold text-white py-1.5">₹<?php echo number_format($total_flat_price, 2); ?> (₹1.45 Cr)</td>
                                    </tr>
                                    <tr>
                                        <td class="text-white fw-medium py-1.5">Down Payment (20%)</td>
                                        <td class="text-end fw-bold text-white py-1.5">₹<?php echo number_format($down_payment, 2); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-white fw-medium py-1.5">Loan Amount (80% LTV)</td>
                                        <td class="text-end fw-bold text-white py-1.5">₹<?php echo number_format($loan_amount, 2); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-white fw-medium py-1.5">Interest Rate / Tenure</td>
                                        <td class="text-end fw-bold text-white py-1.5">8.5% p.a. / 20 Years (240 Mos)</td>
                                    </tr>
                                    <tr class="border-top border-secondary border-opacity-50">
                                        <td class="fw-bold text-white fs-6 pt-2.5">Estimated Monthly EMI</td>
                                        <td class="text-end fw-extrabold text-warning fs-5 pt-2.5">₹<?php echo number_format($emi_monthly, 2); ?> / mo</td>
                                    </tr>
                                    <tr>
                                        <td class="text-white-75 small py-1">Total Payable Amount</td>
                                        <td class="text-end small text-white-75 py-1">₹<?php echo number_format($total_payable, 2); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Smart Home & Security Systems -->
                    <div class="border-top border-secondary border-opacity-25 pt-4 mb-4">
                        <h5 class="fw-bold mb-3 text-white fs-6 d-flex align-items-center gap-2">
                            <i class="fa-solid fa-microchip text-warning fs-5"></i>
                            <span>Smart Home & Security Specs</span>
                        </h5>
                        <ul class="list-unstyled text-white small mb-0 d-flex flex-column gap-2.5">
                            <li class="d-flex align-items-center">
                                <i class="fa-solid fa-fingerprint text-warning me-2.5 fs-6" style="width:22px; text-align:center; flex-shrink:0;"></i>
                                <div><strong class="text-white">Biometric Smart Door Locks:</strong> <span class="text-white-75">Fingerprint & Passcode Entry</span></div>
                            </li>
                            <li class="d-flex align-items-center">
                                <i class="fa-solid fa-video text-warning me-2.5 fs-6" style="width:22px; text-align:center; flex-shrink:0;"></i>
                                <div><strong class="text-white">Video Door Phone:</strong> <span class="text-white-75">24x7 HD Screen Intercom Connection</span></div>
                            </li>
                            <li class="d-flex align-items-center">
                                <i class="fa-solid fa-bell-concierge text-warning me-2.5 fs-6" style="width:22px; text-align:center; flex-shrink:0;"></i>
                                <div><strong class="text-white">Smart Sensors:</strong> <span class="text-white-75">Automated Gas Leak & Motion Alarms</span></div>
                            </li>
                        </ul>
                    </div>

                    <!-- Key Neighborhood Connectivity -->
                    <div class="border-top border-secondary border-opacity-25 pt-4 mb-4">
                        <h5 class="fw-bold mb-3 text-white fs-6 d-flex align-items-center gap-2">
                            <i class="fa-solid fa-route text-warning fs-5" style="width:20px; text-align:center;"></i>
                            <span>Location & Transit Distance</span>
                        </h5>
                        <ul class="list-unstyled text-white small mb-0 d-flex flex-column gap-2.5">
                            <li class="d-flex align-items-center">
                                <i class="fa-solid fa-train-subway text-warning me-2.5 fs-6" style="width:22px; text-align:center; flex-shrink:0;"></i>
                                <div><strong class="text-white">Metro Station:</strong> <span class="text-white-75">5 Mins Walk (800m)</span></div>
                            </li>
                            <li class="d-flex align-items-center">
                                <i class="fa-solid fa-graduation-cap text-warning me-2.5 fs-6" style="width:22px; text-align:center; flex-shrink:0;"></i>
                                <div><strong class="text-white">Top Schools & MCC:</strong> <span class="text-white-75">8 Mins Drive (2.1 km)</span></div>
                            </li>
                            <li class="d-flex align-items-center">
                                <i class="fa-solid fa-plane-departure text-warning me-2.5 fs-6" style="width:22px; text-align:center; flex-shrink:0;"></i>
                                <div><strong class="text-white">International Airport:</strong> <span class="text-white-75">25 Mins Expressway (14 km)</span></div>
                            </li>
                        </ul>
                    </div>

                    <!-- Verified Certifications & Pre-Approved Banks -->
                    <div class="border-top border-secondary border-opacity-25 pt-4 mb-4">
                        <h5 class="fw-bold mb-3 text-white fs-6 d-flex align-items-center gap-2">
                            <i class="fa-solid fa-shield-halved text-warning fs-5" style="width:20px; text-align:center;"></i>
                            <span>Verified Trust & Approvals</span>
                        </h5>
                        <ul class="list-unstyled text-white small mb-0 d-flex flex-column gap-2.5">
                            <li class="d-flex align-items-center">
                                <i class="fa-solid fa-award text-warning me-2.5 fs-6" style="width:22px; text-align:center; flex-shrink:0;"></i>
                                <div><strong class="text-white">RERA Registered:</strong> <span class="text-white-75">TN/01/Building/0248/2026</span></div>
                            </li>
                            <li class="d-flex align-items-center">
                                <i class="fa-solid fa-building-columns text-warning me-2.5 fs-6" style="width:22px; text-align:center; flex-shrink:0;"></i>
                                <div><strong class="text-white">Pre-Approved Banks:</strong> <span class="text-white-75">HDFC, SBI, ICICI, Axis</span></div>
                            </li>
                            <li class="d-flex align-items-center">
                                <i class="fa-solid fa-handshake text-warning me-2.5 fs-6" style="width:22px; text-align:center; flex-shrink:0;"></i>
                                <div><strong class="text-white">10-Year Structural Guarantee</strong> <span class="text-white-75">Included</span></div>
                            </li>
                        </ul>
                    </div>

                    <!-- Glowing Booking Offers Card -->
                    <div class="p-3.5 mb-4 rounded-3 text-start animate-pulse" style="background-color: rgba(34, 197, 94, 0.15); border: 1.5px solid #22c55e;">
                        <h6 class="fw-bold mb-2 text-success d-flex align-items-center gap-2" style="font-size:0.92rem;">
                            <i class="fa-solid fa-gift text-success fs-5"></i>
                            <span>Special Booking Privileges Included:</span>
                        </h6>
                        <ul class="list-unstyled mb-0 small text-white d-flex flex-column gap-2">
                            <li class="d-flex align-items-center gap-2"><i class="fa-solid fa-circle-check text-success fs-7"></i><div><strong class="text-white">Free High-Speed Wi-Fi</strong> <span class="text-white-75">(1-Year Plan)</span></div></li>
                            <li class="d-flex align-items-center gap-2"><i class="fa-solid fa-circle-check text-success fs-7"></i><div><strong class="text-white">Reserved Covered Car Parking Slot</strong></div></li>
                            <li class="d-flex align-items-center gap-2"><i class="fa-solid fa-circle-check text-success fs-7"></i><div><strong class="text-white">Zero Processing Fees</strong> <span class="text-white-75">on Home Loans</span></div></li>
                        </ul>
                    </div>
                </div>

                <!-- Comprehensive Multi-Action CTAs Section -->
                <div class="border-top border-secondary border-opacity-25 pt-4 mt-auto">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <small class="text-white-75 d-block">Reservation Token Fee</small>
                            <span class="fs-2 fw-extrabold text-white">₹<?php echo number_format($raw_price, 2); ?></span>
                        </div>
                        <span class="badge bg-success text-white px-3 py-2 font-bold shadow-xs">100% Refundable</span>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-12">
                            <?php if ($flat['status'] === 'Available'): ?>
                                <?php if (isset($_SESSION['resident_logged_in'])): ?>
                                    <a href="checkout.php?id=<?php echo $flat['id']; ?>" class="btn btn-warning text-dark btn-lg w-100 py-3 font-bold shadow-sm" style="background: linear-gradient(135deg, #ffd700 0%, #f39c12 100%); border: none;">
                                        <i class="fa-solid fa-wallet me-2"></i> Book Now & Reserve Flat
                                    </a>
                                <?php else: ?>
                                    <a href="login.php?redirect=checkout&flat_id=<?php echo $flat['id']; ?>" class="btn btn-warning text-dark btn-lg w-100 py-3 font-bold shadow-sm" style="background: linear-gradient(135deg, #ffd700 0%, #f39c12 100%); border: none;">
                                        <i class="fa-solid fa-right-to-bracket me-2"></i> Login to Book Flat
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <button class="btn btn-secondary btn-lg w-100 py-3 font-bold" disabled>
                                    <i class="fa-solid fa-circle-check me-2"></i> This Flat is Booked
                                </button>
                            <?php endif; ?>
                        </div>
                        <div class="col-6">
                            <a href="tel:+918056210606" class="btn btn-outline-warning w-100 py-2.5 font-semibold text-warning">
                                <i class="fa-solid fa-phone me-1.5 text-warning"></i> Call Manager
                            </a>
                        </div>
                        <div class="col-6">
                            <button class="btn btn-outline-light w-100 py-2.5 font-semibold" onclick="scheduleVisitModal()">
                                <i class="fa-solid fa-calendar-check me-1.5"></i> Schedule Visit
                            </button>
                        </div>
                    </div>

                    <div class="text-center">
                        <a href="<?php echo $base_path; ?>Jarvis_Premium_Residences_Map.pdf" download="Jarvis_Premium_Residences_Map.pdf" class="text-white-50 small text-decoration-none"><i class="fa-solid fa-file-pdf text-danger me-1"></i> Download Official Project Brochure PDF</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const showcaseItems = <?php echo json_encode($gallery_items); ?>;

let currentIndex = 0;
let showcaseTimer = null;

function startAutoRotation() {
    stopAutoRotation();
    showcaseTimer = setInterval(() => {
        nextShowcase(false);
    }, 3000); // Move slowly every 3 seconds (3000ms) // Automatically move slide every 2 seconds (2000ms)
}

function stopAutoRotation() {
    if (showcaseTimer) {
        clearInterval(showcaseTimer);
        showcaseTimer = null;
    }
}

function updateShowcase(index) {
    currentIndex = (index + showcaseItems.length) % showcaseItems.length;
    const mainImg = document.getElementById('mainShowcaseImg');
    const mainTitle = document.getElementById('mainShowcaseTitle');
    const counterBadge = document.getElementById('showcaseCounter');
    
    if (mainImg) {
        mainImg.style.opacity = '0.3';
        setTimeout(() => {
            mainImg.src = showcaseItems[currentIndex].src;
            if (mainTitle) mainTitle.innerText = showcaseItems[currentIndex].title;
            if (counterBadge) counterBadge.innerText = (currentIndex + 1) + ' / ' + showcaseItems.length;
            mainImg.style.opacity = '1';
        }, 150);
    }

    document.querySelectorAll('.thumb-card').forEach((card, idx) => {
        if (idx === currentIndex) {
            card.classList.add('active-thumb-glow');
        } else {
            card.classList.remove('active-thumb-glow');
        }
    });
}

function selectShowcase(index) {
    updateShowcase(index);
    startAutoRotation(); // Reset 2s timer on click
}

function nextShowcase(isManual = true) {
    updateShowcase(currentIndex + 1);
    if (isManual) startAutoRotation(); // Reset 2s timer on manual click
}

function prevShowcase(isManual = true) {
    updateShowcase(currentIndex - 1);
    if (isManual) startAutoRotation(); // Reset 2s timer on manual click
}

function toggleWishlist() {
    const btn = document.getElementById('wishlistBtn');
    if (btn.classList.contains('btn-outline-danger')) {
        btn.classList.remove('btn-outline-danger');
        btn.classList.add('btn-danger', 'text-white');
        btn.innerHTML = '<i class="fa-solid fa-heart me-1"></i> Saved to Wishlist';
        alert('Flat added to your personal wishlist!');
    } else {
        btn.classList.remove('btn-danger', 'text-white');
        btn.classList.add('btn-outline-danger');
        btn.innerHTML = '<i class="fa-regular fa-heart me-1"></i> Save to Wishlist';
    }
}

function scheduleVisitModal() {
    alert('Thank you! Our relationship manager will contact you at +91 80562 10606 to confirm your site visit timing.');
}

// Support Left/Right keyboard arrow navigation
document.addEventListener('keydown', function(e) {
    if (e.key === 'ArrowRight') nextShowcase(true);
    if (e.key === 'ArrowLeft') prevShowcase(true);
});

// Start automatic 2-second slideshow timer & handle hover pause
document.addEventListener('DOMContentLoaded', function() {
    startAutoRotation();

    const showcaseBox = document.getElementById('mainShowcaseImg');
    if (showcaseBox) {
        showcaseBox.addEventListener('mouseenter', stopAutoRotation);
        showcaseBox.addEventListener('mouseleave', startAutoRotation);
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
