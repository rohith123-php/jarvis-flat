<?php
require_once 'includes/db.php';

$email = 'john@example.com';
$stmt = $pdo->prepare("SELECT * FROM residents WHERE email = :email");
$stmt->execute(['email' => $email]);
$res = $stmt->fetch();

echo "<div style='font-family:sans-serif; padding: 20px; max-width: 600px; margin: 40px auto; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;'>";
echo "<h3>Database Password Verification Test</h3>";
if ($res) {
    echo "<p><strong>User:</strong> " . htmlspecialchars($res['email']) . "</p>";
    echo "<p><strong>Stored Hash in DB:</strong> <code style='background:#f1f5f9; padding:2px 6px;'>{$res['password']}</code></p>";
    echo "<p><strong>Hash Length:</strong> " . strlen($res['password']) . "</p>";
    
    $check_admin123 = password_verify('admin123', $res['password']);
    $check_resident123 = password_verify('resident123', $res['password']);
    
    echo "<p><strong>Does it match 'admin123'?</strong> " . ($check_admin123 ? "<span style='color:green;font-weight:bold;'>YES</span>" : "<span style='color:red;font-weight:bold;'>NO</span>") . "</p>";
    echo "<p><strong>Does it match 'resident123'?</strong> " . ($check_resident123 ? "<span style='color:green;font-weight:bold;'>YES</span>" : "<span style='color:red;font-weight:bold;'>NO</span>") . "</p>";
} else {
    echo "<p style='color:red;'>User john@example.com not found in database!</p>";
}
echo "</div>";
?>
