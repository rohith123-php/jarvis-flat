<?php
require_once 'includes/db.php';

try {
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    
    // Reset resident passwords
    $stmt = $pdo->prepare("UPDATE residents SET password = :pass WHERE email = 'john@example.com' OR email = 'jane@example.com'");
    $stmt->execute(['pass' => $hash]);
    
    // Reset admin password just in case
    $stmt_admin = $pdo->prepare("UPDATE admin SET password = :pass WHERE username = 'admin'");
    $stmt_admin->execute(['pass' => $hash]);
    
    echo "<div style='font-family:sans-serif; padding: 20px; background: #e0f2fe; color: #0369a1; border-radius: 8px; max-width: 600px; margin: 40px auto;'>";
    echo "<h3 style='margin-top:0;'>Success!</h3>";
    echo "Passwords successfully updated to <strong>admin123</strong> for admin, john@example.com, and jane@example.com!";
    echo "</div>";
} catch (PDOException $e) {
    echo "<div style='font-family:sans-serif; padding: 20px; background: #fee2e2; color: #b91c1c; border-radius: 8px; max-width: 600px; margin: 40px auto;'>";
    echo "<h3 style='margin-top:0;'>Error</h3>";
    echo "Could not update passwords: " . htmlspecialchars($e->getMessage());
    echo "</div>";
}
?>
