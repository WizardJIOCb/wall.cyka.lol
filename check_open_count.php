<?php
// Check if open_count column exists in posts table

// Database configuration
$DB_HOST = getenv('DB_HOST') ?: 'localhost';
$DB_PORT = getenv('DB_PORT') ?: '3308';
$DB_NAME = getenv('DB_NAME') ?: 'wall_social_platform';
$DB_USER = getenv('DB_USER') ?: 'wall_user';
$DB_PASS = getenv('DB_PASSWORD') ?: 'wall_secure_password_123';

try {
    // Database connection
    $pdo = new PDO(
        "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    echo "✓ Connected to MySQL server\n";
    
    // Check if open_count column exists
    $stmt = $pdo->prepare("SHOW COLUMNS FROM posts LIKE 'open_count'");
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result) {
        echo "✓ open_count column exists in posts table\n";
        echo "Column details:\n";
        print_r($result);
    } else {
        echo "✗ open_count column does not exist in posts table\n";
    }
    
} catch (PDOException $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>