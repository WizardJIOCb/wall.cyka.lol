<?php
require_once 'vendor/autoload.php';
require_once 'config/database.php';

try {
    $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if view_count column exists in posts table
    $stmt = $db->prepare("DESCRIBE posts");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Posts table columns:\n";
    foreach ($columns as $column) {
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    
    // Check if view_count column exists
    $viewCountExists = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'view_count') {
            $viewCountExists = true;
            break;
        }
    }
    
    echo "\nView count column exists: " . ($viewCountExists ? "YES" : "NO") . "\n";
    
    // Check a sample post
    $stmt = $db->prepare("SELECT post_id, view_count FROM posts LIMIT 1");
    $stmt->execute();
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($post) {
        echo "\nSample post view_count: " . $post['view_count'] . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}