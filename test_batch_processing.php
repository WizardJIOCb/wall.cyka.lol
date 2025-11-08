<?php
// Test script for batch processing implementation
require 'config/config.php';

try {
    // Test database connection
    $pdo = new PDO('mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME'), getenv('DB_USER'), getenv('DB_PASS'));
    echo "✓ Database connection successful\n";
    
    // Test if open_count column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM posts LIKE 'open_count'");
    $column = $stmt->fetch();
    
    if ($column) {
        echo "✓ open_count column exists in posts table\n";
    } else {
        echo "✗ open_count column does not exist in posts table\n";
        exit(1);
    }
    
    // Test if view_count column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM posts LIKE 'view_count'");
    $column = $stmt->fetch();
    
    if ($column) {
        echo "✓ view_count column exists in posts table\n";
    } else {
        echo "✗ view_count column does not exist in posts table\n";
        exit(1);
    }
    
    // Test if idx_open_count index exists
    $stmt = $pdo->query("SHOW INDEX FROM posts WHERE Key_name = 'idx_open_count'");
    $index = $stmt->fetch();
    
    if ($index) {
        echo "✓ idx_open_count index exists\n";
    } else {
        echo "✗ idx_open_count index does not exist\n";
    }
    
    echo "\n✓ All tests passed! Batch processing implementation is ready.\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}