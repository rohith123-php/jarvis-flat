<?php
// Handle automatic redirect if someone visits index.php/admin
if (isset($_SERVER['PATH_INFO']) && (strpos(strtolower($_SERVER['PATH_INFO']), '/admin') === 0 || strpos(strtolower($_SERVER['PATH_INFO']), 'admin') !== false)) {
    header("Location: " . dirname($_SERVER['SCRIPT_NAME']) . "/admin/dashboard.php");
    exit;
}

$active_type = 'Residential';
require_once 'includes/properties_layout.php';
?>
