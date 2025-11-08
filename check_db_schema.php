<?php
// Simple script to check if view_count column exists in posts table

// Database configuration from .env.example
$host = 'localhost'; // Assuming local MySQL
$dbname = 'wall_social_platform';
$user = 'wall_user';
$pass = 'wall_secure_password_123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if view_count column exists
    $stmt = $pdo->prepare("SHOW COLUMNS FROM posts LIKE 'view_count'");
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result) {
        echo "view_count column EXISTS in posts table\n";
        echo "Column details: " . print_r($result, true) . "\n";
    } else {
        echo "view_count column DOES NOT EXIST in posts table\n";
    }
    
    // Check all columns in posts table
    echo "\nAll columns in posts table:\n";
    $stmt = $pdo->prepare("SHOW COLUMNS FROM posts");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    
} catch (Exception $e) {
    echo "Error connecting to database: " . $e->getMessage() . "\n";
    echo "Note: This script requires a local MySQL database with the correct credentials.\n";
}