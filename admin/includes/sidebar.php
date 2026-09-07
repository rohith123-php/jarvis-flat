<?php
// Shared Standard Admin Sidebar Component - Clean Text-Only (No Symbols, No Section Headers)
$active_page = basename($_SERVER['PHP_SELF']);
?>
<style>
.admin-sidebar-card {
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid rgba(18, 59, 122, 0.08);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
    padding: 12px 8px;
    margin-bottom: 24px;
}
.admin-sidebar-nav {
    display: flex;
    flex-direction: column;
    gap: 3px;
}
.sidebar-link {
    display: block;
    padding: 9px 14px;
    border-radius: 6px;
    font-size: 0.88rem;
    font-weight: 600;
    color: #475569;
    text-decoration: none;
    transition: all 0.18s ease;
    border-left: 3px solid transparent;
}
.sidebar-link:hover {
    background: #f1f5f9;
    color: #123b7a;
    border-left-color: #cbd5e1;
}
.sidebar-link.active {
    background: #123b7a;
    color: #ffffff !important;
    font-weight: 700;
    border-left-color: #f59e0b;
    box-shadow: 0 3px 10px rgba(18, 59, 122, 0.2);
}
</style>

<div class="admin-sidebar-card">
    <div class="admin-sidebar-nav">
        <a href="dashboard.php" class="sidebar-link <?php echo ($active_page == 'dashboard.php') ? 'active' : ''; ?>">
            Dashboard
        </a>
        <a href="menu.php" class="sidebar-link <?php echo ($active_page == 'menu.php') ? 'active' : ''; ?>">
            Property Catalog
        </a>
        <a href="bookings.php" class="sidebar-link <?php echo ($active_page == 'bookings.php') ? 'active' : ''; ?>">
            Bookings Log
        </a>
        <a href="inquiries.php" class="sidebar-link <?php echo ($active_page == 'inquiries.php') ? 'active' : ''; ?>">
            Leads &amp; Inquiries
        </a>
        <a href="residents.php" class="sidebar-link <?php echo ($active_page == 'residents.php') ? 'active' : ''; ?>">
            Customers
        </a>
        <a href="buildings.php" class="sidebar-link <?php echo ($active_page == 'buildings.php') ? 'active' : ''; ?>">
            Buildings &amp; Towers
        </a>
        <a href="payments.php" class="sidebar-link <?php echo ($active_page == 'payments.php') ? 'active' : ''; ?>">
            Payments
        </a>
        <a href="maintenance.php" class="sidebar-link <?php echo ($active_page == 'maintenance.php') ? 'active' : ''; ?>">
            Maintenance
        </a>
        <a href="notices.php" class="sidebar-link <?php echo ($active_page == 'notices.php') ? 'active' : ''; ?>">
            Notice Board
        </a>
        <a href="reports.php" class="sidebar-link <?php echo ($active_page == 'reports.php') ? 'active' : ''; ?>">
            Reports &amp; Analytics
        </a>
        <a href="settings.php" class="sidebar-link <?php echo ($active_page == 'settings.php') ? 'active' : ''; ?>">
            System Settings
        </a>

        <hr class="my-2 border-secondary border-opacity-10">
        <a href="../index.php" target="_blank" class="sidebar-link text-primary">
            Live Showroom
        </a>
        <a href="../index.php?logout=admin" class="sidebar-link text-danger">
            Sign Out
        </a>
    </div>
</div>
