<?php
require_once 'includes/db.php';

try {
    // Generate new hash of 'admin123' at runtime
    $new_hash = password_hash('admin123', PASSWORD_DEFAULT);
    
    // Update it directly in the residents table
    $stmt = $pdo->prepare("UPDATE residents SET password = :pass WHERE email = 'john@example.com'");
    $stmt->execute(['pass' => $new_hash]);
    
    // Read it back
    $stmt_check = $pdo->prepare("SELECT password FROM residents WHERE email = 'john@example.com'");
    $stmt_check->execute();
    $hash_in_db = $stmt_check->fetchColumn();
    
    $verifies = password_verify('admin123', $hash_in_db);
    
    echo "<div style='font-family:sans-serif; padding: 20px; max-width: 600px; margin: 40px auto; background: #d1fae5; color: #065f46; border-radius: 8px; border: 1px solid #a7f3d0;'>";
    echo "<h3 style='margin-top:0;'>Force Password Reset Successful!</h3>";
    echo "<p>Password has been reset to <strong>admin123</strong>.</p>";
    echo "<p><strong>Hash stored:</strong> <code>{$hash_in_db}</code></p>";
    echo "<p><strong>Verification test:</strong> " . ($verifies ? "<span style='color:green;font-weight:bold;'>PASSED</span>" : "<span style='color:red;font-weight:bold;'>FAILED</span>") . "</p>";
    echo "<br><a href='resident/login.php' style='color:#059669; font-weight:bold;'>Go to Resident Login</a>";
    echo "</div>";
} catch (PDOException $e) {
    echo "<div style='font-family:sans-serif; padding: 20px; background: #fee2e2; color: #b91c1c; border-radius: 8px; max-width: 600px; margin: 40px auto;'>";
    echo "<h3 style='margin-top:0;'>Reset Error</h3>";
    echo htmlspecialchars($e->getMessage());
    echo "</div>";
}
?>
