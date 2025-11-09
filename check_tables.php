<?php
// Database configuration
$DB_HOST = 'localhost';
$DB_PORT = '3306';
$DB_NAME = 'wall_social_platform';
$DB_USER = 'wall_user';
$DB_PASS = 'wall_secure_password';

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
    
    // Show tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $stmt->closeCursor();
    
    echo "Total tables in database: " . count($tables) . "\n";
    echo "Tables:\n";
    foreach ($tables as $table) {
        echo "  - {$table}\n";
    }
    
    // Check for specific tables
    $requiredTables = ['user_follows', 'notifications', 'user_preferences', 'conversations', 'conversation_participants', 'messages'];
    echo "\nChecking required tables:\n";
    foreach ($requiredTables as $table) {
        if (in_array($table, $tables)) {
            echo "  ✓ {$table} exists\n";
        } else {
            echo "  ✗ {$table} is missing\n";
        }
    }
    
} catch (PDOException $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}