<?php
if (function_exists('opcache_reset')) {
    @opcache_reset();
}
if (!ob_get_level()) {
    ob_start();
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$host = '127.0.0.1';
$db   = 'flat_management';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // 1. Connect to MySQL server directly without choosing database
    $pdo_init = new PDO("mysql:host=$host;charset=$charset", $user, $pass, $options);
    
    // 2. Ensure database exists
    $pdo_init->exec("CREATE DATABASE IF NOT EXISTS `$db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;");
    
    // 3. Connect to the actual database
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // 4. Self-Healing check: If table 'flats' does not exist, recreate database schema
    $tableExists = $pdo->query("SHOW TABLES LIKE 'flats'")->fetch();
    if (!$tableExists) {
        $pdo->exec("DROP DATABASE IF EXISTS `$db` ");
        $pdo_init->exec("CREATE DATABASE `$db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;");
        
        $pdo = new PDO($dsn, $user, $pass, $options);
        $sql_path = dirname(__DIR__) . '/database/flat_management.sql';
        if (file_exists($sql_path)) {
            $sql = file_get_contents($sql_path);
            $pdo->exec($sql);
        }
    }

    // 5. Ensure settings table exists
    $settingsExists = $pdo->query("SHOW TABLES LIKE 'settings'")->fetch();
    if (!$settingsExists) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `settings` (
                `key_name` VARCHAR(50) PRIMARY KEY,
                `key_value` TEXT NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            INSERT INTO `settings` (`key_name`, `key_value`) VALUES ('system_name', 'Jarvis')
            ON DUPLICATE KEY UPDATE `key_value`=`key_value`;
        ");
    }

    // 6. Ensure image_url is TEXT
    try {
        $pdo->exec("ALTER TABLE `flats` MODIFY COLUMN `image_url` TEXT NULL;");
    } catch (\Exception $e) {}

} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// Global configuration fetch helper
function get_setting($key, $default = '') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT key_value FROM settings WHERE key_name = :key");
        $stmt->execute(['key' => $key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? $val : $default;
    } catch (Exception $e) {
        return $default;
    }
}

// Global helper to parse 4-slot flat photos (exterior, interior, amenity, luxury)
function parse_flat_photos($raw) {
    $defaults = [
        'exterior' => 'images/std_gated_community.jpg',
        'interior' => 'images/std_living_2bhk.jpg',
        'amenity'  => 'images/std_society_pool.jpg',
        'luxury'   => 'images/grand_villa_exterior.jpg'
    ];
    if (empty($raw)) {
        return $defaults;
    }
    if (is_array($raw)) {
        return array_merge($defaults, array_filter($raw));
    }
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        return array_merge($defaults, array_filter($decoded));
    }
    // Backward compatible with legacy single string URL
    $defaults['exterior'] = trim($raw);
    return $defaults;
}
?>
