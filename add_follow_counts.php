<?php
// Database configuration
$DB_HOST = 'mysql';
$DB_PORT = '3306';
$DB_NAME = 'wall_social_platform';
$DB_USER = 'wall_user';
$DB_PASS = 'wall_secure_password_123';

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
    
    echo "✓ Connected to MySQL server\n";
    
    // Use database
    $pdo->exec("USE `{$DB_NAME}`");
    echo "✓ Database '{$DB_NAME}' ready\n\n";
    
    // Add followers_count column
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN followers_count INT DEFAULT 0 NOT NULL AFTER reactions_given_count");
        echo "✓ Added followers_count column to users table\n";
    } catch (Exception $e) {
        echo "⚠ Warning: Could not add followers_count column: " . $e->getMessage() . "\n";
    }
    
    // Add following_count column
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN following_count INT DEFAULT 0 NOT NULL AFTER followers_count");
        echo "✓ Added following_count column to users table\n";
    } catch (Exception $e) {
        echo "⚠ Warning: Could not add following_count column: " . $e->getMessage() . "\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "Columns added successfully!\n";
    echo str_repeat("=", 50) . "\n";
    
} catch (PDOException $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}