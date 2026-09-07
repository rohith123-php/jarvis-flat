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
    <style>
        :root {
            --blue-brand: <?php echo htmlspecialchars($primary_color); ?> !important;
            --orange-brand: <?php echo htmlspecialchars($accent_color); ?> !important;
            --orange-hover: <?php echo htmlspecialchars($accent_color); ?>ee !important;
            --font-heading: <?php echo $heading_font; ?> !important;
            --font-body: <?php echo $body_font; ?> !important;
        }
        body {
            font-family: var(--font-body) !important;
        }
        h1, h2, h3, h4, h5, h6, .font-heading, .display-1, .display-2, .display-3, .display-4, .display-5, .display-6, .navbar-brand {
            font-family: var(--font-heading) !important;
        }
    </style>
</head>
<body class="<?php echo ($current_dir === 'admin') ? 'admin-theme' : ''; ?>">

    <!-- Main Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light navbar-custom py-3">
        <div class="container-fluid px-4">
            <!-- Brand Logo: Royal Shield & Crown Crest -->
            <a class="navbar-brand d-flex align-items-center" href="<?php echo $base_path; ?>index.php" style="text-decoration: none;">
                <!-- Royal Shield & Crown Crest Emblem -->
                <div class="logo-crest me-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px; background: linear-gradient(135deg, #103178 0%, #1c4b9c 100%); border-radius: 14px; border: 2px solid #F59E0B; box-shadow: 0 4px 15px rgba(245, 158, 11, 0.35); position: relative; transition: all 0.3s ease; cursor: pointer;">
                    <i class="fa-solid fa-shield-halved" style="font-size: 1.6rem; color: #F59E0B; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));"></i>
                    <i class="fa-solid fa-crown" style="position: absolute; font-size: 0.85rem; color: #ffffff; top: 14px; filter: drop-shadow(0 1px 2px rgba(0,0,0,0.5));"></i>
                    <span style="position: absolute; top: -4px; right: -4px; width: 14px; height: 14px; background: linear-gradient(135deg, #ffd700 0%, #f59e0b 100%); border: 1.5px solid #ffffff; border-radius: 50%; box-shadow: 0 0 8px rgba(245, 158, 11, 0.8); display: flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-diamond" style="font-size: 7px; color: #103178;"></i>
                    </span>
                </div>
                <div class="lh-1">
                    <div style="font-family: 'Playfair Display', 'Plus Jakarta Sans', serif; font-weight: 900; font-size: 2.2rem; background: linear-gradient(135deg, #ffd700 0%, #f39c12 50%, #ffd700 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; letter-spacing: 1.5px; display: flex; align-items: center; text-shadow: 0.5px 0.5px 0px rgba(0,0,0,0.05); filter: drop-shadow(0px 2px 3px rgba(0,0,0,0.15));">
                        <?php echo htmlspecialchars(strtoupper($system_name)); ?>
                        <span style="color: var(--orange-brand); -webkit-text-fill-color: var(--orange-brand); font-weight: 300; font-size: 1.6rem; margin-left: 3px; margin-top: -6px;">✦</span>
                    </div>
                    <span style="display:block; font-size:0.58rem; letter-spacing:4.5px; color:var(--orange-brand); font-weight:700; text-transform:uppercase; margin-top:3px;"><?php echo htmlspecialchars($brand_tagline); ?></span>
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
                        <button class="btn btn-call-dropdown dropdown-toggle px-3 py-2" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="border-radius:4px; background-color:#f2a122; border:none; font-weight:700; color:#ffffff;">
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
                                <li><a class="dropdown-item py-2 text-dark rounded fw-bold" href="<?php echo $base_path; ?>admin/login.php"><i class="fa-solid fa-user-shield me-2 text-warning"></i>Admin Panel</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    <main>
