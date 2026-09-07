<?php
require_once '../includes/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

// 1. One-Click Full Database Backup Export
if (isset($_GET['action']) && $_GET['action'] === 'download_backup') {
    try {
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $sql_dump = "-- Jarvis Flat Management System Database Backup\n";
        $sql_dump .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";

        foreach ($tables as $table) {
            $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
            $sql_dump .= "DROP TABLE IF EXISTS `$table`;\n";
            $sql_dump .= $create['Create Table'] . ";\n\n";

            $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $keys = array_map(function($k) { return "`$k`"; }, array_keys($row));
                $vals = array_map(function($v) use ($pdo) { 
                    return is_null($v) ? "NULL" : $pdo->quote($v); 
                }, array_values($row));

                $sql_dump .= "INSERT INTO `$table` (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $vals) . ");\n";
            }
            $sql_dump .= "\n";
        }

        // Output download headers
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="jarvis_database_backup_' . date('Y-m-d_H-i') . '.sql"');
        header('Content-Length: ' . strlen($sql_dump));
        echo $sql_dump;
        exit;
    } catch (Exception $e) {
        die("Backup Error: " . $e->getMessage());
    }
}

$message = '';
$error = '';

// Handle save settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_settings') {
    $settings_to_save = [
        // 1. Global Branding & Currency
        'system_name'             => trim($_POST['system_name'] ?? 'Jarvis'),
        'brand_tagline'           => trim($_POST['brand_tagline'] ?? 'BUILDING ASPIRATIONS'),
        'currency_symbol'         => trim($_POST['currency_symbol'] ?? '₹'),
        'currency_code'           => trim($_POST['currency_code'] ?? 'INR'),

        // 2. Theme Colors & Typography Customizer
        'primary_color'           => trim($_POST['primary_color'] ?? '#103178'),
        'accent_color'            => trim($_POST['accent_color'] ?? '#f2a122'),
        'dark_heading_color'      => trim($_POST['dark_heading_color'] ?? '#0f172a'),
        'heading_font'            => trim($_POST['heading_font'] ?? "'Playfair Display', serif"),
        'body_font'               => trim($_POST['body_font'] ?? "'Plus Jakarta Sans', sans-serif"),

        // 3. About Section & Developer Legacy Customizer
        'about_badge_text'        => trim($_POST['about_badge_text'] ?? 'Architectural Heritage & Builder Legacy'),
        'about_heading_text'      => trim($_POST['about_heading_text'] ?? 'About Jarvis Residences & Developer Legacy'),
        'about_desc_text'         => trim($_POST['about_desc_text'] ?? 'Jarvis Residences represents the pinnacle of smart-home architectural construction, engineered for families seeking modular luxury comfort, eco-friendly green systems, and strategic urban access.'),
        'about_builder_name'      => trim($_POST['about_builder_name'] ?? 'Jarvis Real Estate Developers'),
        'about_builder_badge'     => trim($_POST['about_builder_badge'] ?? 'ICRA (A+ Stable) Certified'),
        'about_builder_bio'       => trim($_POST['about_builder_bio'] ?? 'Developed by Jarvis Real Estate Developers, India\'s leading luxury home builder certified by ICRA (A+ Stable). Over 25 years of engineering excellence with more than 32 Million Square Feet of delivered residential space across South India.'),
        'about_stat1_val'         => trim($_POST['about_stat1_val'] ?? '32M+'),
        'about_stat1_lbl'         => trim($_POST['about_stat1_lbl'] ?? 'Sq.Ft Delivered'),
        'about_stat2_val'         => trim($_POST['about_stat2_val'] ?? '15,000+'),
        'about_stat2_lbl'         => trim($_POST['about_stat2_lbl'] ?? 'Happy Families'),
        'about_stat3_val'         => trim($_POST['about_stat3_val'] ?? '28+'),
        'about_stat3_lbl'         => trim($_POST['about_stat3_lbl'] ?? 'National Awards'),
        'about_stat4_val'         => trim($_POST['about_stat4_val'] ?? '100%'),
        'about_stat4_lbl'         => trim($_POST['about_stat4_lbl'] ?? 'RERA Approved'),

        // 4. Financial & Booking Policies
        'default_deposit'         => trim($_POST['default_deposit'] ?? '25000'),
        'tax_rate'                => trim($_POST['tax_rate'] ?? '18'),
        'payment_grace_days'      => trim($_POST['payment_grace_days'] ?? '7'),

        // 5. Maintenance & Utility Rates
        'maintenance_rate_sqft'   => trim($_POST['maintenance_rate_sqft'] ?? '3.50'),
        'electricity_unit_rate'   => trim($_POST['electricity_unit_rate'] ?? '6.00'),
        'water_fixed_rate'        => trim($_POST['water_fixed_rate'] ?? '500'),

        // 6. Contacts & Helpdesk
        'contact_phone'           => trim($_POST['contact_phone'] ?? '+91 80562 10606'),
        'whatsapp_phone'          => trim($_POST['whatsapp_phone'] ?? '+91 80562 10606'),
        'contact_email'           => trim($_POST['contact_email'] ?? 'sales@jarvisresidences.com'),
        'contact_address'         => trim($_POST['contact_address'] ?? 'NPL Devi, 111 OMR Rd, Chennai'),

        // 7. Email & Notification Gateway
        'smtp_host'               => trim($_POST['smtp_host'] ?? 'smtp.gmail.com'),
        'smtp_port'               => trim($_POST['smtp_port'] ?? '587'),
        'smtp_user'               => trim($_POST['smtp_user'] ?? 'notifications@jarvisresidences.com'),
        'enable_email_alerts'     => isset($_POST['enable_email_alerts']) ? '1' : '0',
        'enable_sms_alerts'       => isset($_POST['enable_sms_alerts']) ? '1' : '0',

        // 8. Invoice & RERA Compliance
        'invoice_prefix'          => trim($_POST['invoice_prefix'] ?? 'INV-2026-'),
        'company_registration_no' => trim($_POST['company_registration_no'] ?? 'TN/01/Building/0241/2026'),
        'invoice_footer_note'     => trim($_POST['invoice_footer_note'] ?? 'Thank you for choosing Jarvis. Payment is due within 7 days.'),

        // 9. Portal Controls
        'allow_registration'      => isset($_POST['allow_registration']) ? '1' : '0',
        'auto_approve_bookings'   => isset($_POST['auto_approve_bookings']) ? '1' : '0',
        'maintenance_mode'        => isset($_POST['maintenance_mode']) ? '1' : '0'
    ];

    try {
        $stmt = $pdo->prepare("INSERT INTO settings (key_name, key_value) VALUES (:key, :val) ON DUPLICATE KEY UPDATE key_value = :val2");
        foreach ($settings_to_save as $k => $v) {
            $stmt->execute(['key' => $k, 'val' => $v, 'val2' => $v]);
        }

        // Log Activity
        $stmt_log = $pdo->prepare("INSERT INTO activity_logs (admin_username, action, module, details) VALUES (:user, 'Updated Enterprise Settings', 'Settings', :det)");
        $stmt_log->execute([
            'user' => $_SESSION['admin_username'] ?? 'admin',
            'det' => 'Updated system branding, frontend theme colors, typography, about content, and enterprise controls.'
        ]);

        $message = 'System configuration, themes, typography, and content saved successfully!';
    } catch (PDOException $e) {
        $error = 'Error saving settings: ' . $e->getMessage();
    }
}

// Ensure Indian Currency (₹ / INR) is set as default in database
try {
    $pdo->exec("INSERT INTO settings (key_name, key_value) VALUES ('currency_symbol', '₹') ON DUPLICATE KEY UPDATE key_value = IF(key_value = '$' OR key_value = '', '₹', key_value)");
    $pdo->exec("INSERT INTO settings (key_name, key_value) VALUES ('currency_code', 'INR') ON DUPLICATE KEY UPDATE key_value = IF(key_value = '' OR key_value IS NULL, 'INR', key_value)");
} catch (Exception $e) {}

// Fetch current setting values
$system_name             = get_setting('system_name', 'Jarvis');
$brand_tagline           = get_setting('brand_tagline', 'BUILDING ASPIRATIONS');
$currency_symbol         = get_setting('currency_symbol', '₹');
if ($currency_symbol === '$' || empty($currency_symbol)) { $currency_symbol = '₹'; }
$currency_code           = get_setting('currency_code', 'INR');

$primary_color           = get_setting('primary_color', '#103178');
$accent_color            = get_setting('accent_color', '#f2a122');
$dark_heading_color      = get_setting('dark_heading_color', '#0f172a');
$heading_font            = get_setting('heading_font', "'Playfair Display', serif");
$body_font               = get_setting('body_font', "'Plus Jakarta Sans', sans-serif");

$about_badge_text        = get_setting('about_badge_text', 'Architectural Heritage & Builder Legacy');
$about_heading_text      = get_setting('about_heading_text', 'About Jarvis Residences & Developer Legacy');
$about_desc_text         = get_setting('about_desc_text', 'Jarvis Residences represents the pinnacle of smart-home architectural construction, engineered for families seeking modular luxury comfort, eco-friendly green systems, and strategic urban access.');
$about_builder_name      = get_setting('about_builder_name', 'Jarvis Real Estate Developers');
$about_builder_badge     = get_setting('about_builder_badge', 'ICRA (A+ Stable) Certified');
$about_builder_bio       = get_setting('about_builder_bio', "Developed by Jarvis Real Estate Developers, India's leading luxury home builder certified by ICRA (A+ Stable). Over 25 years of engineering excellence with more than 32 Million Square Feet of delivered residential space across South India.");
$about_stat1_val         = get_setting('about_stat1_val', '32M+');
$about_stat1_lbl         = get_setting('about_stat1_lbl', 'Sq.Ft Delivered');
$about_stat2_val         = get_setting('about_stat2_val', '15,000+');
$about_stat2_lbl         = get_setting('about_stat2_lbl', 'Happy Families');
$about_stat3_val         = get_setting('about_stat3_val', '28+');
$about_stat3_lbl         = get_setting('about_stat3_lbl', 'National Awards');
$about_stat4_val         = get_setting('about_stat4_val', '100%');
$about_stat4_lbl         = get_setting('about_stat4_lbl', 'RERA Approved');

$default_deposit         = get_setting('default_deposit', '25000');
$tax_rate                = get_setting('tax_rate', '18');
$payment_grace_days      = get_setting('payment_grace_days', '7');

$maintenance_rate_sqft   = get_setting('maintenance_rate_sqft', '3.50');
$electricity_unit_rate   = get_setting('electricity_unit_rate', '6.00');
$water_fixed_rate        = get_setting('water_fixed_rate', '500');

$contact_phone           = get_setting('contact_phone', '+91 80562 10606');
$whatsapp_phone          = get_setting('whatsapp_phone', '+91 80562 10606');
$contact_email           = get_setting('contact_email', 'sales@jarvisresidences.com');
$contact_address         = get_setting('contact_address', 'NPL Devi, 111 OMR Rd, Chennai');

$smtp_host               = get_setting('smtp_host', 'smtp.gmail.com');
$smtp_port               = get_setting('smtp_port', '587');
$smtp_user               = get_setting('smtp_user', 'notifications@jarvisresidences.com');
$enable_email_alerts     = get_setting('enable_email_alerts', '1');
$enable_sms_alerts       = get_setting('enable_sms_alerts', '1');

$invoice_prefix          = get_setting('invoice_prefix', 'INV-2026-');
$company_registration_no = get_setting('company_registration_no', 'TN/01/Building/0241/2026');
$invoice_footer_note     = get_setting('invoice_footer_note', 'Thank you for choosing Jarvis. Payment is due within 7 days.');

$allow_registration      = get_setting('allow_registration', '1');
$auto_approve_bookings   = get_setting('auto_approve_bookings', '0');
$maintenance_mode        = get_setting('maintenance_mode', '0');

require_once 'includes/header.php';
?>

<div class="container-fluid px-4">
    <div class="row">
        <!-- Sidebar Navigation -->
        <div class="col-md-3 col-lg-2">
            <?php include 'includes/sidebar.php'; ?>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold" style="color:var(--blue-brand);">Enterprise Operations & Settings Control Hub</h2>
                    <p class="text-muted mb-0">Manage system parameters, financial policies, utility rates, notification gateways, invoice rules, and database backups.</p>
                </div>
                <a href="settings.php?action=download_backup" class="btn btn-outline-success fw-bold px-3 py-2">
                    <i class="fa-solid fa-database me-1.5"></i> Download SQL Backup
                </a>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-circle-check me-2"></i><?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-circle-xmark me-2"></i><?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form action="settings.php" method="POST">
                <input type="hidden" name="action" value="save_settings">

                <div class="row g-4">
                    <!-- 1. Global Branding & Currency -->
                    <div class="col-lg-6">
                        <div class="card card-premium p-4 h-100 border border-light-subtle shadow-sm rounded-3" style="background:#fff;">
                            <h5 class="fw-bold mb-3" style="color:var(--blue-brand);"><i class="fa-solid fa-crown text-warning me-2"></i>Branding & Currency</h5>
                            <div class="mb-3">
                                <label class="form-label fw-bold text-uppercase small">Application / Brand Name</label>
                                <input type="text" class="form-control" name="system_name" value="<?php echo htmlspecialchars($system_name); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold text-uppercase small">Brand Slogan / Subtitle</label>
                                <input type="text" class="form-control" name="brand_tagline" value="<?php echo htmlspecialchars($brand_tagline); ?>" required>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label fw-bold text-uppercase small">Currency Symbol</label>
                                    <input type="text" class="form-control" name="currency_symbol" value="<?php echo htmlspecialchars($currency_symbol); ?>" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-bold text-uppercase small">Currency ISO Code</label>
                                    <input type="text" class="form-control" name="currency_code" value="<?php echo htmlspecialchars($currency_code); ?>" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Frontend Theme Colors & Typography Customizer -->
                    <div class="col-lg-6">
                        <div class="card card-premium p-4 h-100 border border-light-subtle shadow-sm rounded-3" style="background:#fff;">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="fw-bold mb-0" style="color:var(--blue-brand);"><i class="fa-solid fa-palette text-warning me-2"></i>Theme Colors & Typography</h5>
                                <span class="badge bg-primary-subtle text-primary fw-semibold fs-8">Live Website Customizer</span>
                            </div>
                            
                            <!-- Colors Row -->
                            <div class="row g-3 mb-3">
                                <div class="col-4">
                                    <label class="form-label fw-bold text-uppercase small" style="font-size:0.75rem;">Primary Color</label>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="color" class="form-control form-control-color p-0 border-0 rounded-2" id="primary_picker" value="<?php echo htmlspecialchars($primary_color); ?>" oninput="document.getElementById('primary_color').value = this.value;" style="width:38px; height:38px; cursor:pointer;">
                                        <input type="text" class="form-control form-control-sm font-monospace" id="primary_color" name="primary_color" value="<?php echo htmlspecialchars($primary_color); ?>" oninput="document.getElementById('primary_picker').value = this.value;" required>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <label class="form-label fw-bold text-uppercase small" style="font-size:0.75rem;">Accent / Gold</label>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="color" class="form-control form-control-color p-0 border-0 rounded-2" id="accent_picker" value="<?php echo htmlspecialchars($accent_color); ?>" oninput="document.getElementById('accent_color').value = this.value;" style="width:38px; height:38px; cursor:pointer;">
                                        <input type="text" class="form-control form-control-sm font-monospace" id="accent_color" name="accent_color" value="<?php echo htmlspecialchars($accent_color); ?>" oninput="document.getElementById('accent_picker').value = this.value;" required>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <label class="form-label fw-bold text-uppercase small" style="font-size:0.75rem;">Heading Dark</label>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="color" class="form-control form-control-color p-0 border-0 rounded-2" id="dark_picker" value="<?php echo htmlspecialchars($dark_heading_color); ?>" oninput="document.getElementById('dark_heading_color').value = this.value;" style="width:38px; height:38px; cursor:pointer;">
                                        <input type="text" class="form-control form-control-sm font-monospace" id="dark_heading_color" name="dark_heading_color" value="<?php echo htmlspecialchars($dark_heading_color); ?>" oninput="document.getElementById('dark_picker').value = this.value;" required>
                                    </div>
                                </div>
                            </div>

                            <!-- Typography & Google Fonts Row -->
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label fw-bold text-uppercase small">Headings Font</label>
                                    <select class="form-select" name="heading_font">
                                        <option value="'Playfair Display', serif" <?php echo ($heading_font === "'Playfair Display', serif") ? 'selected' : ''; ?> style="font-family:'Playfair Display', serif;">Playfair Display (Luxury Serif)</option>
                                        <option value="'Cinzel', serif" <?php echo ($heading_font === "'Cinzel', serif") ? 'selected' : ''; ?> style="font-family:'Cinzel', serif;">Cinzel (Royal Heritage)</option>
                                        <option value="'Plus Jakarta Sans', sans-serif" <?php echo ($heading_font === "'Plus Jakarta Sans', sans-serif") ? 'selected' : ''; ?> style="font-family:'Plus Jakarta Sans', sans-serif;">Plus Jakarta Sans (Modern Geometric)</option>
                                        <option value="'Outfit', sans-serif" <?php echo ($heading_font === "'Outfit', sans-serif") ? 'selected' : ''; ?> style="font-family:'Outfit', sans-serif;">Outfit (Contemporary Clean)</option>
                                        <option value="'Montserrat', sans-serif" <?php echo ($heading_font === "'Montserrat', sans-serif") ? 'selected' : ''; ?> style="font-family:'Montserrat', sans-serif;">Montserrat (Architectural Bold)</option>
                                        <option value="'Inter', sans-serif" <?php echo ($heading_font === "'Inter', sans-serif") ? 'selected' : ''; ?> style="font-family:'Inter', sans-serif;">Inter (Neutral Corporate)</option>
                                        <option value="'Lora', serif" <?php echo ($heading_font === "'Lora', serif") ? 'selected' : ''; ?> style="font-family:'Lora', serif;">Lora (Editorial Classic)</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-bold text-uppercase small">Body Font</label>
                                    <select class="form-select" name="body_font">
                                        <option value="'Plus Jakarta Sans', sans-serif" <?php echo ($body_font === "'Plus Jakarta Sans', sans-serif") ? 'selected' : ''; ?> style="font-family:'Plus Jakarta Sans', sans-serif;">Plus Jakarta Sans</option>
                                        <option value="'Inter', sans-serif" <?php echo ($body_font === "'Inter', sans-serif") ? 'selected' : ''; ?> style="font-family:'Inter', sans-serif;">Inter</option>
                                        <option value="'Roboto', sans-serif" <?php echo ($body_font === "'Roboto', sans-serif") ? 'selected' : ''; ?> style="font-family:'Roboto', sans-serif;">Roboto</option>
                                        <option value="'Outfit', sans-serif" <?php echo ($body_font === "'Outfit', sans-serif") ? 'selected' : ''; ?> style="font-family:'Outfit', sans-serif;">Outfit</option>
                                        <option value="'Poppins', sans-serif" <?php echo ($body_font === "'Poppins', sans-serif") ? 'selected' : ''; ?> style="font-family:'Poppins', sans-serif;">Poppins</option>
                                    </select>
                                </div>
                            </div>
                            <div class="p-2.5 rounded bg-light border text-center small text-muted">
                                Changes here instantly reflect on the public website headers, buttons, cards, and typography.
                            </div>
                        </div>
                    </div>

                    <!-- 3. About Section & Developer Legacy Customizer -->
                    <div class="col-12">
                        <div class="card card-premium p-4 border border-light-subtle shadow-sm rounded-3" style="background:#fff;">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="fw-bold mb-0" style="color:var(--blue-brand);"><i class="fa-solid fa-building-user text-warning me-2"></i>About Section & Developer Legacy Customizer</h5>
                                <span class="badge bg-warning-subtle text-dark fw-bold">Frontend Content Manager</span>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-5">
                                    <label class="form-label fw-bold text-uppercase small">Section Top Badge Text</label>
                                    <input type="text" class="form-control" name="about_badge_text" value="<?php echo htmlspecialchars($about_badge_text); ?>" required>
                                </div>
                                <div class="col-md-7">
                                    <label class="form-label fw-bold text-uppercase small">Section Main Heading</label>
                                    <input type="text" class="form-control" name="about_heading_text" value="<?php echo htmlspecialchars($about_heading_text); ?>" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold text-uppercase small">Overview Description Paragraph</label>
                                    <textarea class="form-control" name="about_desc_text" rows="2" required><?php echo htmlspecialchars($about_desc_text); ?></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-uppercase small">Developer / Builder Name</label>
                                    <input type="text" class="form-control" name="about_builder_name" value="<?php echo htmlspecialchars($about_builder_name); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-uppercase small">Developer Certification Badge</label>
                                    <input type="text" class="form-control" name="about_builder_badge" value="<?php echo htmlspecialchars($about_builder_badge); ?>" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold text-uppercase small">Developer Legacy & Track Record Bio</label>
                                    <textarea class="form-control" name="about_builder_bio" rows="2" required><?php echo htmlspecialchars($about_builder_bio); ?></textarea>
                                </div>
                                
                                <!-- 4 Counter Metric Stats -->
                                <div class="col-12">
                                    <label class="form-label fw-bold text-uppercase small text-primary mb-2"><i class="fa-solid fa-chart-simple me-1"></i> Developer Statistics & Milestones</label>
                                    <div class="row g-2">
                                        <div class="col-6 col-md-3">
                                            <div class="p-2 border rounded bg-light">
                                                <input type="text" class="form-control form-control-sm fw-bold text-warning mb-1" name="about_stat1_val" value="<?php echo htmlspecialchars($about_stat1_val); ?>" placeholder="e.g. 32M+" required>
                                                <input type="text" class="form-control form-control-sm text-muted" name="about_stat1_lbl" value="<?php echo htmlspecialchars($about_stat1_lbl); ?>" placeholder="Label" required>
                                            </div>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <div class="p-2 border rounded bg-light">
                                                <input type="text" class="form-control form-control-sm fw-bold text-warning mb-1" name="about_stat2_val" value="<?php echo htmlspecialchars($about_stat2_val); ?>" placeholder="e.g. 15,000+" required>
                                                <input type="text" class="form-control form-control-sm text-muted" name="about_stat2_lbl" value="<?php echo htmlspecialchars($about_stat2_lbl); ?>" placeholder="Label" required>
                                            </div>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <div class="p-2 border rounded bg-light">
                                                <input type="text" class="form-control form-control-sm fw-bold text-warning mb-1" name="about_stat3_val" value="<?php echo htmlspecialchars($about_stat3_val); ?>" placeholder="e.g. 28+" required>
                                                <input type="text" class="form-control form-control-sm text-muted" name="about_stat3_lbl" value="<?php echo htmlspecialchars($about_stat3_lbl); ?>" placeholder="Label" required>
                                            </div>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <div class="p-2 border rounded bg-light">
                                                <input type="text" class="form-control form-control-sm fw-bold text-warning mb-1" name="about_stat4_val" value="<?php echo htmlspecialchars($about_stat4_val); ?>" placeholder="e.g. 100%" required>
                                                <input type="text" class="form-control form-control-sm text-muted" name="about_stat4_lbl" value="<?php echo htmlspecialchars($about_stat4_lbl); ?>" placeholder="Label" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Financial & Booking Policies -->
                    <div class="col-lg-6">
                        <div class="card card-premium p-4 h-100 border border-light-subtle shadow-sm rounded-3" style="background:#fff;">
                            <h5 class="fw-bold mb-3" style="color:var(--blue-brand);"><i class="fa-solid fa-coins text-warning me-2"></i>Booking & Financial Policies</h5>
                            <div class="mb-3">
                                <label class="form-label fw-bold text-uppercase small">Standard Booking Deposit (₹)</label>
                                <input type="number" step="100" class="form-control" name="default_deposit" value="<?php echo htmlspecialchars($default_deposit); ?>" required>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label fw-bold text-uppercase small">GST / Tax Rate (%)</label>
                                    <input type="number" step="0.5" class="form-control" name="tax_rate" value="<?php echo htmlspecialchars($tax_rate); ?>" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-bold text-uppercase small">Payment Grace Days</label>
                                    <input type="number" class="form-control" name="payment_grace_days" value="<?php echo htmlspecialchars($payment_grace_days); ?>" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Maintenance & Utility Rates -->
                    <div class="col-lg-6">
                        <div class="card card-premium p-4 h-100 border border-light-subtle shadow-sm rounded-3" style="background:#fff;">
                            <h5 class="fw-bold mb-3" style="color:var(--blue-brand);"><i class="fa-solid fa-calculator text-warning me-2"></i>Maintenance & Utility Rates</h5>
                            <div class="mb-3">
                                <label class="form-label fw-bold text-uppercase small">Maintenance Fee (₹ per Sq.Ft)</label>
                                <input type="number" step="0.10" class="form-control" name="maintenance_rate_sqft" value="<?php echo htmlspecialchars($maintenance_rate_sqft); ?>" required>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label fw-bold text-uppercase small">Electricity (₹ / kWh)</label>
                                    <input type="number" step="0.10" class="form-control" name="electricity_unit_rate" value="<?php echo htmlspecialchars($electricity_unit_rate); ?>" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-bold text-uppercase small">Fixed Water Charge (₹)</label>
                                    <input type="number" step="10" class="form-control" name="water_fixed_rate" value="<?php echo htmlspecialchars($water_fixed_rate); ?>" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Contacts & Helpdesk Hotline -->
                    <div class="col-lg-6">
                        <div class="card card-premium p-4 h-100 border border-light-subtle shadow-sm rounded-3" style="background:#fff;">
                            <h5 class="fw-bold mb-3" style="color:var(--blue-brand);"><i class="fa-solid fa-headset text-warning me-2"></i>Corporate Support Contacts</h5>
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label fw-bold text-uppercase small">Sales Phone Number</label>
                                    <input type="text" class="form-control" name="contact_phone" value="<?php echo htmlspecialchars($contact_phone); ?>" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-bold text-uppercase small">WhatsApp Support</label>
                                    <input type="text" class="form-control" name="whatsapp_phone" value="<?php echo htmlspecialchars($whatsapp_phone); ?>" required>
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-bold text-uppercase small">Support Email</label>
                                <input type="email" class="form-control" name="contact_email" value="<?php echo htmlspecialchars($contact_email); ?>" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-bold text-uppercase small">Corporate HQ Address</label>
                                <input type="text" class="form-control" name="contact_address" value="<?php echo htmlspecialchars($contact_address); ?>" required>
                            </div>
                        </div>
                    </div>

                    <!-- 5. Email & SMS Notification Gateway Settings -->
                    <div class="col-lg-6">
                        <div class="card card-premium p-4 h-100 border border-light-subtle shadow-sm rounded-3" style="background:#fff;">
                            <h5 class="fw-bold mb-3" style="color:var(--blue-brand);"><i class="fa-solid fa-paper-plane text-warning me-2"></i>Notification Gateway (SMTP/SMS)</h5>
                            <div class="row g-2 mb-2">
                                <div class="col-8">
                                    <label class="form-label fw-bold text-uppercase small">SMTP Host</label>
                                    <input type="text" class="form-control" name="smtp_host" value="<?php echo htmlspecialchars($smtp_host); ?>" required>
                                </div>
                                <div class="col-4">
                                    <label class="form-label fw-bold text-uppercase small">Port</label>
                                    <input type="text" class="form-control" name="smtp_port" value="<?php echo htmlspecialchars($smtp_port); ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold text-uppercase small">Sender Email Address</label>
                                <input type="email" class="form-control" name="smtp_user" value="<?php echo htmlspecialchars($smtp_user); ?>" required>
                            </div>
                            <div class="d-flex flex-column gap-2 mt-2">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="enable_email_alerts" name="enable_email_alerts" <?php echo ($enable_email_alerts === '1') ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-semibold small" for="enable_email_alerts">Send Automated Email Receipts to Residents</label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="enable_sms_alerts" name="enable_sms_alerts" <?php echo ($enable_sms_alerts === '1') ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-semibold small" for="enable_sms_alerts">Send Real-Time WhatsApp/SMS Alerts</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 6. Invoice & RERA Compliance -->
                    <div class="col-lg-6">
                        <div class="card card-premium p-4 h-100 border border-light-subtle shadow-sm rounded-3" style="background:#fff;">
                            <h5 class="fw-bold mb-3" style="color:var(--blue-brand);"><i class="fa-solid fa-file-invoice text-warning me-2"></i>Invoice & RERA Template Rules</h5>
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label fw-bold text-uppercase small">Custom Invoice Prefix</label>
                                    <input type="text" class="form-control" name="invoice_prefix" value="<?php echo htmlspecialchars($invoice_prefix); ?>" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-bold text-uppercase small">RERA Reg. License No.</label>
                                    <input type="text" class="form-control" name="company_registration_no" value="<?php echo htmlspecialchars($company_registration_no); ?>" required>
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-bold text-uppercase small">PDF Invoice Footer Note</label>
                                <textarea class="form-control" name="invoice_footer_note" rows="2" required><?php echo htmlspecialchars($invoice_footer_note); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- 7. Security & System Controls -->
                    <div class="col-12">
                        <div class="card card-premium p-4 border border-light-subtle shadow-sm rounded-3" style="background:#fff;">
                            <h5 class="fw-bold mb-3" style="color:var(--blue-brand);"><i class="fa-solid fa-shield-halved text-warning me-2"></i>Security & Portal Access Controls</h5>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="form-check form-switch p-3 border rounded">
                                        <input class="form-check-input ms-0 me-2" type="checkbox" id="allow_registration" name="allow_registration" <?php echo ($allow_registration === '1') ? 'checked' : ''; ?>>
                                        <label class="form-check-label fw-bold small" for="allow_registration">Allow Online Resident Self-Registration</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check form-switch p-3 border rounded">
                                        <input class="form-check-input ms-0 me-2" type="checkbox" id="auto_approve_bookings" name="auto_approve_bookings" <?php echo ($auto_approve_bookings === '1') ? 'checked' : ''; ?>>
                                        <label class="form-check-label fw-bold small" for="auto_approve_bookings">Auto-Approve Customer Booking Deposits</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check form-switch p-3 border rounded">
                                        <input class="form-check-input ms-0 me-2" type="checkbox" id="maintenance_mode" name="maintenance_mode" <?php echo ($maintenance_mode === '1') ? 'checked' : ''; ?>>
                                        <label class="form-check-label fw-bold small text-danger" for="maintenance_mode">Enable System Maintenance Mode</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button Bar -->
                    <div class="col-12 text-end mt-4 mb-4">
                        <button type="submit" class="btn btn-warning text-dark fw-bold px-5 py-3 rounded-pill shadow-sm" style="background-color: var(--orange-brand); border: none; font-size: 1.1rem;">
                            <i class="fa-solid fa-floppy-disk me-2"></i> Save System Configuration
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
