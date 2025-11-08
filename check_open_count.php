<?php
// Check if open_count column exists in posts table

// Database configuration - connecting to Docker container from host
$DB_HOST = 'localhost';  // Connect to localhost since we're on the host
$DB_PORT = '3308';       // Exposed port from docker-compose.yml
$DB_NAME = 'wall_social_platform';
$DB_USER = 'wall_user';
$DB_PASS = 'wall_secure_password_123';

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