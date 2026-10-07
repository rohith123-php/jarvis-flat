<?php
if (!ob_get_level()) {
    ob_start();
}
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Detect path level to find assets and relative links
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
$base_path = ($current_dir === 'admin' || $current_dir === 'resident') ? '../' : './';
$system_name   = get_setting('system_name', 'Jarvis');
$brand_tagline = get_setting('brand_tagline', 'BUILDING ASPIRATIONS');
$contact_phone = get_setting('contact_phone', '+91 80562 10606');
$primary_color = get_setting('primary_color', '#103178');
$accent_color  = get_setting('accent_color', '#f2a122');
$dark_heading_color = get_setting('dark_heading_color', '#0f172a');
$header_bg_color = get_setting('header_bg_color', '#ffffff');
$logo_bg_color      = get_setting('logo_bg_color', get_setting('logo_accent_color', '#f59e0b'));
$logo_shield_color  = get_setting('logo_shield_color', get_setting('logo_primary_color', '#103178'));
$logo_text_color    = get_setting('logo_text_color', get_setting('logo_accent_color', '#f59e0b'));
$logo_primary_color = $logo_shield_color;
$logo_accent_color  = $logo_bg_color;
$site_bg_color      = get_setting('site_bg_color', '#f8f9fa');
$trust_bg_color     = get_setting('trust_bg_color', '#ffffff');
$card_bg_color      = get_setting('card_bg_color', '#ffffff');

$heading_font  = get_setting('heading_font', "'Playfair Display', serif");
$body_font     = get_setting('body_font', "'Plus Jakarta Sans', sans-serif");

if (!isset($active_type)) {
    $active_type = isset($_GET['type']) ? $_GET['type'] : 'Residential';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($system_name); ?> - <?php echo htmlspecialchars($brand_tagline); ?></title>
    <!-- Google Fonts Preconnect & Multi-Font Stylesheet -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;700;900&family=Inter:wght@300;400;500;600;700;800&family=Lora:ital,wght@0,400;0,600;1,400&family=Montserrat:wght@400;600;700;800&family=Outfit:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,400;0,600;0,700;0,900;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome for Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom Stylesheet (with cache buster) -->
    <link href="<?php echo $base_path; ?>css/style.css?v=<?php echo time(); ?>" rel="stylesheet">
    <!-- Dynamic Customizer Stylesheet Override -->
    <style>        @font-face {
            font-family: 'NumberFontOverride';
            font-style: normal;
            font-weight: 400 900;
            font-display: swap;
            src: url(https://fonts.gstatic.com/s/montserrat/v31/JTUHjIg1_i6t8kCHKm4532VJOt5-QNFgpCuM70w-.ttf) format('truetype');
            unicode-range: U+30-39, U+20B9, U+2C, U+2E, U+0025; /* 0-9, ?, comma, period, % */
        }
        :root {
            --blue-brand: <?php echo htmlspecialchars($primary_color); ?> !important;
            --orange-brand: <?php echo htmlspecialchars($accent_color); ?> !important;
            --orange-hover: <?php echo htmlspecialchars($accent_color); ?>ee !important;
            --header-bg: <?php echo htmlspecialchars($header_bg_color); ?> !important;
            --logo-bg: <?php echo htmlspecialchars($logo_bg_color); ?> !important;
            --logo-shield: <?php echo htmlspecialchars($logo_shield_color); ?> !important;
            --logo-text: <?php echo htmlspecialchars($logo_text_color); ?> !important;
            --logo-primary: <?php echo htmlspecialchars($logo_shield_color); ?> !important;
            --logo-accent: <?php echo htmlspecialchars($logo_bg_color); ?> !important;
            --site-bg: <?php echo htmlspecialchars($site_bg_color); ?> !important;
            --trust-bg: <?php echo htmlspecialchars($trust_bg_color); ?> !important;
            --card-bg: <?php echo htmlspecialchars($card_bg_color); ?> !important;
            --font-heading: <?php echo $heading_font; ?> !important;
            --font-body: <?php echo $body_font; ?> !important;
        }
        body {
            font-family: 'NumberFontOverride', var(--font-body) !important;
            background-color: var(--site-bg) !important;
        }
        h1, h2, h3, h4, h5, h6, .font-heading, .display-1, .display-2, .display-3, .display-4, .display-5, .display-6, .navbar-brand {
            font-family: 'NumberFontOverride', var(--font-heading) !important;
        }
        .navbar-custom {
            background-color: var(--header-bg) !important;
        }
    
        .btn-warning, .btn-warning-custom {
            background-color: var(--orange-brand) !important;
            border-color: var(--orange-brand) !important;
            color: #1a1a1a !important;
        }
        .btn-warning:hover, .btn-warning:focus, .btn-warning:active {
            background-color: var(--orange-hover) !important;
            border-color: var(--orange-hover) !important;
            color: #1a1a1a !important;
        }
        .text-warning {
            color: var(--orange-brand) !important;
        }
        .bg-warning {
            background-color: var(--orange-brand) !important;
        }
        .border-warning {
            border-color: var(--orange-brand) !important;
        }
        .btn-call-dropdown {
            background-color: var(--orange-brand) !important;
            border-color: var(--orange-brand) !important;
        }

    
        #map-section,
        #about,
        #gallery,
        #pricing,
        #contact,
        #amenities,
        #apartments-showcase {
            background-color: var(--site-bg) !important;
        }
        /* Card and Panel Backgrounds */
        .bg-white:not(input):not(textarea):not(select),
        .bg-light,
        .calc-card,
        .testimonial-card,
        .card,
        .card-premium,
        .accordion-item,
        .accordion-button,
        .accordion-body,
        .p-4.border.rounded-3,
        .shadow-hover,
        .table,
        .table th,
        .table td,
        .table-light,
        .list-group-item,
        .modal-content {
            background-color: var(--card-bg) !important;
            background-image: none !important;
        }
    </style>
</head>
<body class="<?php echo ($current_dir === 'admin') ? 'admin-theme' : ''; ?>">

    <!-- Main Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light navbar-custom py-3">
        <div class="container-fluid px-4">
            <!-- Brand Logo: Royal Shield & Crown Crest -->
            <a class="navbar-brand d-flex align-items-center" href="<?php echo $base_path; ?>index.php" style="text-decoration: none;">
                <!-- Royal Shield Emblem -->
                <div class="logo-crest me-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px; background: <?php echo htmlspecialchars($logo_bg_color); ?>; border-radius: 14px; border: 2.5px solid <?php echo htmlspecialchars($logo_shield_color); ?>; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15), 0 0 14px <?php echo htmlspecialchars($logo_bg_color); ?>88; position: relative; transition: all 0.3s ease; cursor: pointer;">
                    <i class="fa-solid fa-shield" style="font-size: 1.75rem; color: <?php echo htmlspecialchars($logo_shield_color); ?>; filter: drop-shadow(0 2px 3px rgba(0,0,0,0.25));"></i>
                    <i class="fa-solid fa-crown" style="position: absolute; font-size: 0.88rem; color: #ffffff; top: 14px; filter: drop-shadow(0 1px 2px rgba(0,0,0,0.5));"></i>
                    
                </div>
                <div class="lh-1">
                    <div class="d-flex align-items-center" style="font-family: 'Playfair Display', serif; font-weight: 900; font-size: 2.2rem; color: <?php echo htmlspecialchars($logo_text_color); ?>; letter-spacing: 1.5px; text-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                        <?php echo htmlspecialchars(strtoupper($system_name)); ?>
                        <!-- Solid 4-Point Filled Sparkle Star -->
                        <svg class="logo-sparkle-star" width="22" height="22" viewBox="0 0 24 24" fill="<?php echo htmlspecialchars($logo_text_color); ?>" xmlns="http://www.w3.org/2000/svg" style="display:inline-block; vertical-align:middle; margin-left: 6px; filter: drop-shadow(0 0 4px <?php echo htmlspecialchars($logo_text_color); ?>88);">
                            <path d="M12 2L14.8 9.2L22 12L14.8 14.8L12 22L9.2 14.8L2 12L9.2 9.2L12 2Z" fill="<?php echo htmlspecialchars($logo_text_color); ?>"/>
                        </svg>
                    </div>
                    <span style="display:block; font-size:0.56rem; letter-spacing:5px; color:<?php echo htmlspecialchars($logo_text_color); ?>; font-weight:800; text-transform:uppercase; margin-top:3px; font-family:'Plus Jakarta Sans', sans-serif;"><?php echo htmlspecialchars($brand_tagline); ?></span>
                </div>
            </a>

            <!-- Toggle Button for Mobile -->
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Navigation Links -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link px-3 <?php echo (basename($_SERVER['PHP_SELF']) === 'index.php') ? 'active' : ''; ?>" href="<?php echo $base_path; ?>index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3 <?php echo (basename($_SERVER['PHP_SELF']) === 'about.php') ? 'active' : ''; ?>" href="<?php echo $base_path; ?>about.php">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3 <?php echo (basename($_SERVER['PHP_SELF']) === 'flats.php') ? 'active' : ''; ?>" href="<?php echo $base_path; ?>flats.php">Flats</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3 <?php echo (basename($_SERVER['PHP_SELF']) === 'amenities.php') ? 'active' : ''; ?>" href="<?php echo $base_path; ?>amenities.php">Amenities</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3 <?php echo (basename($_SERVER['PHP_SELF']) === 'gallery.php') ? 'active' : ''; ?>" href="<?php echo $base_path; ?>gallery.php">Gallery</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3 <?php echo (basename($_SERVER['PHP_SELF']) === 'pricing.php') ? 'active' : ''; ?>" href="<?php echo $base_path; ?>pricing.php">Pricing</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3 <?php echo (basename($_SERVER['PHP_SELF']) === 'contact.php') ? 'active' : ''; ?>" href="<?php echo $base_path; ?>contact.php">Contact Us</a>
                    </li>
                    <?php if (!isset($_SESSION['resident_logged_in'])): ?>
                        <li class="nav-item">
                            <a class="nav-link px-3 <?php echo (basename($_SERVER['PHP_SELF']) === 'login.php') ? 'active' : ''; ?>" href="<?php echo $base_path; ?>resident/login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link px-3 <?php echo (basename($_SERVER['PHP_SELF']) === 'register.php') ? 'active' : ''; ?>" href="<?php echo $base_path; ?>resident/register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>

                <!-- Right Side Actions & Call Us -->
                <div class="d-flex align-items-center gap-2">
                    <!-- Call Us orange button dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-call-dropdown dropdown-toggle px-3 py-2" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="border-radius:4px; background-color:var(--orange-brand); border:none; font-weight:700; color:#ffffff;">
                            CALL US <i class="fa-solid fa-phone ms-1" style="font-size: 0.8rem;"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow p-2" style="min-width: max-content; white-space: nowrap;">
                            <li><a class="dropdown-menu-item px-3 py-2 text-decoration-none d-block text-dark fw-bold" href="tel:<?php echo preg_replace('/[^0-9+]/', '', $contact_phone); ?>" style="white-space: nowrap;"><i class="fa-solid fa-phone text-success me-2"></i><?php echo htmlspecialchars($contact_phone); ?></a></li>
                        </ul>
                    </div>

                    <!-- Voice Assistant Icon -->
                    <i class="fa-solid fa-microphone ms-2 text-secondary" style="font-size:1.15rem; cursor:pointer;" data-bs-toggle="modal" data-bs-target="#voiceSearchModal" onclick="startVoiceRecognition()"></i>

                    <!-- Search Icon -->
                    <i class="fa-solid fa-magnifying-glass ms-3 text-secondary" style="font-size:1.15rem; cursor:pointer;" data-bs-toggle="modal" data-bs-target="#searchModal"></i>

                    <!-- Hamburger Menu Button -->
                    <div class="dropdown ms-3">
                        <a href="javascript:void(0)" class="text-secondary text-decoration-none" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-bars" style="font-size:1.25rem;"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow p-3" style="min-width:200px;">
                            <li class="dropdown-header fw-bold text-dark text-uppercase small border-bottom pb-2 mb-2">Member Portal</li>
                            <?php if (isset($_SESSION['resident_logged_in'])): ?>
                                <li><a class="dropdown-item py-2 text-danger rounded fw-bold" href="<?php echo $base_path; ?>index.php?logout=resident"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item py-2 text-dark rounded fw-bold" href="<?php echo $base_path; ?>resident/login.php"><i class="fa-solid fa-right-to-bracket me-2 text-primary"></i>Resident Login</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    <main>








