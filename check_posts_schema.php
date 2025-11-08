<?php
require 'config/config.php';

try {
    $pdo = new PDO('mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME'), getenv('DB_USER'), getenv('DB_PASS'));
    $stmt = $pdo->query('DESCRIBE posts');
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Posts table columns:\n";
    foreach ($columns as $column) {
        echo "- " . $column . "\n";
    }
    
    // Check if open_count column exists
    if (in_array('open_count', $columns)) {
        echo "\n✓ open_count column exists\n";
    } else {
        echo "\n✗ open_count column does not exist\n";
    }
    
    // Check if view_count column exists
    if (in_array('view_count', $columns)) {
        echo "✓ view_count column exists\n";
    } else {
        echo "✗ view_count column does not exist\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}