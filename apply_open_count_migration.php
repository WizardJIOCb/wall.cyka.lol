<?php
// Directly apply the open_count migration

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
    
    // Check if open_count column already exists
    $stmt = $pdo->prepare("SHOW COLUMNS FROM posts LIKE 'open_count'");
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result) {
        echo "✓ open_count column already exists in posts table\n";
    } else {
        // Add the open_count column
        $pdo->exec("ALTER TABLE posts ADD COLUMN open_count INT DEFAULT 0 NOT NULL AFTER view_count");
        echo "✓ Added open_count column to posts table\n";
        
        // Add index for performance
        $pdo->exec("ALTER TABLE posts ADD INDEX idx_open_count (open_count)");
        echo "✓ Added index for open_count column\n";
    }
    
    // Verify the column exists now
    $stmt = $pdo->prepare("SHOW COLUMNS FROM posts LIKE 'open_count'");
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result) {
        echo "✓ Verification successful: open_count column exists\n";
        print_r($result);
    } else {
        echo "✗ Verification failed: open_count column does not exist\n";
    }
    
} catch (PDOException $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>