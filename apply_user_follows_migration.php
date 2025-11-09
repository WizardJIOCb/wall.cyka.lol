<?php
/**
 * Manual migration script to create user_follows table
 * This script bypasses the normal migration system to directly apply the missing table
 */

// Database configuration (matching the config)
$DB_HOST = getenv('DB_HOST') ?: 'localhost';
$DB_PORT = getenv('DB_PORT') ?: '3306';
$DB_NAME = getenv('DB_NAME') ?: 'wall_social_platform';
$DB_USER = getenv('DB_USER') ?: 'wall_user';
$DB_PASS = getenv('DB_PASSWORD') ?: 'wall_secure_password';

echo "Applying user_follows migration...\n";

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
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$DB_NAME}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$DB_NAME}`");
    echo "✓ Database '{$DB_NAME}' ready\n\n";
    
    // Create user_follows table
    $sql = "
    CREATE TABLE IF NOT EXISTS user_follows (
      follow_id INT AUTO_INCREMENT PRIMARY KEY,
      follower_id INT NOT NULL,
      following_id INT NOT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      UNIQUE KEY unique_follow (follower_id, following_id),
      FOREIGN KEY (follower_id) REFERENCES users(user_id) ON DELETE CASCADE,
      FOREIGN KEY (following_id) REFERENCES users(user_id) ON DELETE CASCADE,
      INDEX idx_follower (follower_id),
      INDEX idx_following (following_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($sql);
    echo "✓ Created user_follows table\n";
    
    // Add follower/following counts to users table
    try {
        $alterSql = "
        ALTER TABLE users 
        ADD COLUMN IF NOT EXISTS followers_count INT DEFAULT 0 NOT NULL AFTER reactions_given_count,
        ADD COLUMN IF NOT EXISTS following_count INT DEFAULT 0 NOT NULL AFTER followers_count;
        ";
        $pdo->exec($alterSql);
        echo "✓ Added followers_count and following_count columns to users table\n";
    } catch (Exception $e) {
        echo "⚠ Warning: Could not add columns to users table: " . $e->getMessage() . "\n";
        echo "  This might be because they already exist.\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "Migration applied successfully!\n";
    echo str_repeat("=", 50) . "\n";
    
} catch (PDOException $e) {
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}