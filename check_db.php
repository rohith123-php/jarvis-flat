<?php
require_once 'includes/db.php';

try {
    $stmt = $pdo->query("SELECT id, name, email, password FROM residents");
    $residents = $stmt->fetchAll();
    
    echo "<div style='font-family:sans-serif; padding: 20px; max-width: 800px; margin: 40px auto; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px;'>";
    echo "<h3 style='margin-top:0; color:#0f172a;'>Registered Residents in Database:</h3>";
    
    if (count($residents) === 0) {
        echo "<p style='color:#e11d48;'>No residents found in the database table.</p>";
    } else {
        echo "<table style='width:100%; border-collapse: collapse;'>";
        echo "<thead><tr style='border-bottom:2px solid #cbd5e1;'><th style='text-align:left; padding:8px;'>ID</th><th style='text-align:left; padding:8px;'>Name</th><th style='text-align:left; padding:8px;'>Email</th><th style='text-align:left; padding:8px;'>Hash Length</th><th style='text-align:left; padding:8px;'>Hash Value</th></tr></thead>";
        echo "<tbody>";
        foreach ($residents as $r) {
            $len = strlen($r['password']);
            echo "<tr style='border-bottom:1px solid #e2e8f0;'>";
            echo "<td style='padding:8px;'>{$r['id']}</td>";
            echo "<td style='padding:8px;'>{$r['name']}</td>";
            echo "<td style='padding:8px;'>{$r['email']}</td>";
            echo "<td style='padding:8px;'>{$len}</td>";
            echo "<td style='padding:8px; font-family:monospace;'>{$r['password']}</td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
    }
    echo "</div>";
} catch (PDOException $e) {
    echo "<div style='font-family:sans-serif; padding: 20px; background: #fee2e2; color: #b91c1c; border-radius: 8px; max-width: 600px; margin: 40px auto;'>";
    echo "<h3 style='margin-top:0;'>Database Connection Error</h3>";
    echo htmlspecialchars($e->getMessage());
    echo "</div>";
}
?>
