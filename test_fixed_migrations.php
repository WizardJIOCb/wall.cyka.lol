<?php
// Database configuration (matching Database class)
$DB_HOST = getenv('DB_HOST') ?: 'mysql';
$DB_PORT = getenv('DB_PORT') ?: '3306';
$DB_NAME = getenv('DB_NAME') ?: 'wall_social_platform';
$DB_USER = getenv('DB_USER') ?: 'wall_user';
$DB_PASS = getenv('DB_PASSWORD') ?: 'wall_secure_password_123';

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};port={$DB_PORT};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    // Set charset separately
    $pdo->exec("SET NAMES utf8mb4");
    
    // Select the database
    $pdo->exec("USE `{$DB_NAME}`");
    
    echo "✓ Connected to database successfully\n";
    
    // Test if the open_count column exists in posts table
    $stmt = $pdo->prepare("SHOW COLUMNS FROM posts LIKE 'open_count'");
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result) {
        echo "✓ open_count column exists in posts table\n";
    } else {
        echo "✗ open_count column does not exist in posts table\n";
    }
    
    // Test if the like_count column exists in posts table
    $stmt = $pdo->prepare("SHOW COLUMNS FROM posts LIKE 'like_count'");
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result) {
        echo "✓ like_count column exists in posts table\n";
    } else {
        echo "✗ like_count column does not exist in posts table\n";
    }
    
    // Test if the idx_posts_search index exists
    $stmt = $pdo->prepare("SHOW INDEX FROM posts WHERE Key_name = 'idx_posts_search'");
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result) {
        echo "✓ idx_posts_search index exists in posts table\n";
    } else {
        echo "✗ idx_posts_search index does not exist in posts table\n";
    }
    
    // Test if the idx_walls_search index exists
    $stmt = $pdo->prepare("SHOW INDEX FROM walls WHERE Key_name = 'idx_walls_search'");
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result) {
        echo "✓ idx_walls_search index exists in walls table\n";
    } else {
        echo "✗ idx_walls_search index does not exist in walls table\n";
    }
    
    echo "Test completed successfully!\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>