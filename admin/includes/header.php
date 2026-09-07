<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Force check admin login
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}
$system_name   = get_setting('system_name', 'Jarvis');
$brand_tagline = get_setting('brand_tagline', 'BUILDING ASPIRATIONS');
$primary_color = get_setting('primary_color', '#123B7A');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - <?php echo htmlspecialchars($system_name); ?></title>
    <!-- Google Fonts: Inter & Playfair Display -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,700;0,900;1,700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js for Data Visualizations -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom Stylesheet -->
    <link href="../css/style.css?v=<?php echo time(); ?>" rel="stylesheet">
    <style>
        @keyframes pulse-ring {
            0% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
            }
            70% {
                transform: scale(1.08);
                box-shadow: 0 0 0 8px rgba(239, 68, 68, 0);
            }
            100% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
            }
        }
        .grand-notif-btn:hover {
            background: rgba(255, 255, 255, 0.22) !important;
            border-color: #ffd700 !important;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.4) !important;
        }
    </style>
</head>
<body class="admin-theme" style="background-color: #F5F7FA; color: #1F2937; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">

    <!-- Compact Top Navbar -->
    <nav class="navbar navbar-expand-lg sticky-top shadow-sm py-2.5" style="background-color: <?php echo htmlspecialchars($primary_color); ?>;">
        <div class="container-fluid px-4">
            <!-- Brand Logo: Exact Match to Frontend Page -->
            <a class="navbar-brand d-flex align-items-center me-4" href="dashboard.php" style="text-decoration: none;">
                <!-- Royal Shield & Crown Crest Emblem -->
                <div class="logo-crest me-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background: linear-gradient(135deg, #103178 0%, #1c4b9c 100%); border-radius: 14px; border: 2px solid #F59E0B; box-shadow: 0 4px 15px rgba(245, 158, 11, 0.35); position: relative; transition: all 0.3s ease; cursor: pointer;">
                    <i class="fa-solid fa-shield-halved" style="font-size: 1.55rem; color: #F59E0B; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));"></i>
                    <i class="fa-solid fa-crown" style="position: absolute; font-size: 0.82rem; color: #ffffff; top: 13px; filter: drop-shadow(0 1px 2px rgba(0,0,0,0.5));"></i>
                    <span style="position: absolute; top: -4px; right: -4px; width: 14px; height: 14px; background: linear-gradient(135deg, #ffd700 0%, #f59e0b 100%); border: 1.5px solid #ffffff; border-radius: 50%; box-shadow: 0 0 8px rgba(245, 158, 11, 0.8); display: flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-diamond" style="font-size: 7px; color: #103178;"></i>
                    </span>
                </div>
                <div class="lh-1">
                    <div class="d-flex align-items-center" style="font-family: 'Playfair Display', 'Plus Jakarta Sans', serif; font-weight: 900; font-size: 2.1rem; background: linear-gradient(135deg, #ffd700 0%, #f39c12 50%, #ffd700 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; letter-spacing: 1.5px; text-shadow: 0.5px 0.5px 0px rgba(0,0,0,0.05); filter: drop-shadow(0px 2px 3px rgba(0,0,0,0.15));">
                        <?php echo htmlspecialchars(strtoupper($system_name)); ?>
                        <span style="color: #f59e0b; -webkit-text-fill-color: #f59e0b; font-weight: 300; font-size: 1.5rem; margin-left: 3px; margin-top: -5px;">✦</span>
                        <span class="badge bg-amber text-dark ms-2.5 px-2 py-0.5 fw-bold align-middle" style="background-color: #F59E0B; font-size: 0.68rem; letter-spacing: 1px; font-family: 'Inter', sans-serif; -webkit-text-fill-color: #0f172a; border-radius: 6px; box-shadow: 0 2px 6px rgba(245, 158, 11, 0.4);">ADMIN</span>
                    </div>
                    <span style="display:block; font-size:0.56rem; letter-spacing:4px; color:#f59e0b; font-weight:700; text-transform:uppercase; margin-top:3px;"><?php echo htmlspecialchars($brand_tagline); ?></span>
                </div>
            </a>

            <button class="navbar-toggler border-0 text-white" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar" aria-controls="adminNavbar" aria-expanded="false">
                <i class="fa-solid fa-bars fs-5"></i>
            </button>

            <!-- Right Controls: Admin User Dropdown & Notification Hub -->
            <div class="collapse navbar-collapse" id="adminNavbar">
                <div class="ms-auto d-flex align-items-center gap-3 pe-2">
                    <!-- Grand Luxury Admin Profile Dropdown -->
                    <div class="dropdown">
                        <button class="btn rounded-pill px-3.5 py-1.5 fw-bold d-flex align-items-center gap-2 text-white" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="background: rgba(255, 255, 255, 0.12); border: 1.5px solid rgba(245, 158, 11, 0.45); box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); backdrop-filter: blur(8px); transition: all 0.3s ease;">
                            <div class="rounded-circle bg-warning text-dark fw-bold d-flex align-items-center justify-content-center shadow-xs" style="width: 28px; height: 28px; font-size: 0.85rem; border: 1px solid #ffffff;">
                                <i class="fa-solid fa-user-shield" style="font-size: 0.8rem; color: #103178;"></i>
                            </div>
                            <span class="d-none d-sm-inline" style="font-size: 0.88rem; letter-spacing: 0.3px;">Enterprise Admin</span>
                            <i class="fa-solid fa-chevron-down text-warning fs-8 ms-1"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg rounded-3 py-2 border-0 mt-2" style="border: 1px solid rgba(245, 158, 11, 0.25) !important;">
                            <li><a class="dropdown-item small py-2 fw-semibold" href="settings.php"><i class="fa-solid fa-palette me-2 text-warning"></i>Theme & Customizer</a></li>
                            <li><a class="dropdown-item small py-2 fw-semibold" href="logs.php"><i class="fa-solid fa-shield-halved me-2 text-primary"></i>Enterprise Audit Logs</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item small py-2 text-danger fw-bold" href="../index.php?logout=admin"><i class="fa-solid fa-right-from-bracket me-2"></i>Sign Out Session</a></li>
                        </ul>
                    </div>

                    <!-- Grand Luxury Notifications Hub (Positioned on the Right) -->
                    <div class="dropdown me-1">
                        <button class="btn grand-notif-btn position-relative d-flex align-items-center justify-content-center p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="width: 44px; height: 44px; background: rgba(255, 255, 255, 0.12); border: 1.5px solid rgba(245, 158, 11, 0.45); border-radius: 12px; box-shadow: 0 4px 15px rgba(245, 158, 11, 0.2); backdrop-filter: blur(8px); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); cursor: pointer;">
                            <i class="fa-solid fa-bell" style="font-size: 1.25rem; color: #F59E0B; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));"></i>
                            
                            <!-- Grand Glowing Notification Pulse Counter -->
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger text-white fw-bold shadow" style="font-size: 0.65rem; border: 2px solid #103178; padding: 4px 6px; box-shadow: 0 0 10px rgba(239, 68, 68, 0.9); animation: pulse-ring 2s infinite;">
                                3
                                <span class="visually-hidden">unread alerts</span>
                            </span>
                        </button>
                        
                        <!-- Grand Luxury Dropdown Menu -->
                        <div class="dropdown-menu dropdown-menu-end shadow-lg rounded-4 py-0 border-0 mt-2 overflow-hidden" style="min-width: 320px; border: 1px solid rgba(245, 158, 11, 0.25) !important;">
                            <!-- Header -->
                            <div class="p-3 text-white d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #103178 0%, #1c4b9c 100%); border-bottom: 2px solid #F59E0B;">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-bell text-warning fs-6"></i>
                                    <span class="fw-bold" style="font-size: 0.9rem; letter-spacing: 0.5px;">Live Activity Center</span>
                                </div>
                                <span class="badge bg-warning text-dark fw-bold px-2 py-0.5 rounded-pill" style="font-size: 0.65rem;">3 NEW</span>
                            </div>
                            
                            <!-- Items List -->
                            <div class="list-group list-group-flush" style="max-height: 280px; overflow-y: auto;">
                                <a href="bookings.php" class="list-group-item list-group-item-action p-3 d-flex align-items-start gap-3 border-bottom">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center shadow-xs mt-0.5" style="width: 38px; height: 38px; min-width: 38px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: 1.5px solid #34d399; box-shadow: 0 2px 8px rgba(16, 185, 129, 0.35);">
                                        <i class="fa-solid fa-file-invoice-dollar text-white" style="font-size: 1.1rem; color: #ffffff !important; filter: drop-shadow(0 1px 2px rgba(0,0,0,0.4));"></i>
                                    </div>
                                    <div class="lh-sm">
                                        <div class="fw-bold text-dark" style="font-size: 0.85rem;">New Booking Deposit Received</div>
                                        <div class="text-muted mt-0.5" style="font-size: 0.76rem;">Flat 402 • ₹25,000 Verified via UPI</div>
                                        <span class="text-secondary fw-semibold mt-1 d-block" style="font-size: 0.68rem;"><i class="fa-regular fa-clock me-1"></i>Just now</span>
                                    </div>
                                </a>
                                <a href="complaints.php" class="list-group-item list-group-item-action p-3 d-flex align-items-start gap-3 border-bottom">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center shadow-xs mt-0.5" style="width: 38px; height: 38px; min-width: 38px; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border: 1.5px solid #fbbf24; box-shadow: 0 2px 8px rgba(245, 158, 11, 0.35);">
                                        <i class="fa-solid fa-screwdriver-wrench text-white" style="font-size: 1.05rem; color: #ffffff !important; filter: drop-shadow(0 1px 2px rgba(0,0,0,0.4));"></i>
                                    </div>
                                    <div class="lh-sm">
                                        <div class="fw-bold text-dark" style="font-size: 0.85rem;">Maintenance Ticket Assigned</div>
                                        <div class="text-muted mt-0.5" style="font-size: 0.76rem;">Elevator Service (Block A)</div>
                                        <span class="text-secondary fw-semibold mt-1 d-block" style="font-size: 0.68rem;"><i class="fa-regular fa-clock me-1"></i>12 mins ago</span>
                                    </div>
                                </a>
                                <a href="notices.php" class="list-group-item list-group-item-action p-3 d-flex align-items-start gap-3">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center shadow-xs mt-0.5" style="width: 38px; height: 38px; min-width: 38px; background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); border: 1.5px solid #60a5fa; box-shadow: 0 2px 8px rgba(37, 99, 235, 0.35);">
                                        <i class="fa-solid fa-bullhorn text-white" style="font-size: 1.05rem; color: #ffffff !important; filter: drop-shadow(0 1px 2px rgba(0,0,0,0.4));"></i>
                                    </div>
                                    <div class="lh-sm">
                                        <div class="fw-bold text-dark" style="font-size: 0.85rem;">Monthly RERA Report Ready</div>
                                        <div class="text-muted mt-0.5" style="font-size: 0.76rem;">September Audit statement compiled</div>
                                        <span class="text-secondary fw-semibold mt-1 d-block" style="font-size: 0.68rem;"><i class="fa-regular fa-clock me-1"></i>1 hour ago</span>
                                    </div>
                                </a>
                            </div>

                            <!-- Footer -->
                            <div class="p-2 bg-light text-center border-top">
                                <a href="notices.php" class="text-decoration-none fw-bold small text-primary d-block py-1">
                                    View All System Notifications <i class="fa-solid fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    <main class="py-4">
