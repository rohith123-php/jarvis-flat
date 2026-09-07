<?php
$host = '127.0.0.1';
$user = 'root';
$pass = '';

try {
    // Connect to MySQL server directly without choosing database
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Drop old database to start completely fresh
    $pdo->exec("DROP DATABASE IF EXISTS `flat_management`");
    
    // Load database schema script
    $sql_file = __DIR__ . '/database/flat_management.sql';
    if (!file_exists($sql_file)) {
        throw new Exception("flat_management.sql script not found.");
    }
    
    $sql = file_get_contents($sql_file);
    
    // Execute full database setup
    $pdo->exec($sql);
    
    echo "<div style='font-family:sans-serif; padding: 20px; background: #d1fae5; color: #065f46; border-radius: 8px; max-width: 600px; margin: 40px auto;'>";
    echo "<h3 style='margin-top:0;'>Casagrand Real Estate Database Rebuild Successful!</h3>";
    echo "Database flat_management has been dropped, recreated, and successfully seeded with Casagrand multi-city flat listings.";
    echo "<br><br><a href='index.php' style='color:#059669; font-weight:bold;'>Go to Real Estate Home</a>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div style='font-family:sans-serif; padding: 20px; background: #fee2e2; color: #b91c1c; border-radius: 8px; max-width: 600px; margin: 40px auto;'>";
    echo "<h3 style='margin-top:0;'>Setup Error</h3>";
    echo "Could not set up database: " . htmlspecialchars($e->getMessage());
    echo "</div>";
}
?>
