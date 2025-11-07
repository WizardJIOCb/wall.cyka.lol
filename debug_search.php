<?php
/**
 * Debug script to check search functionality
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/config.php';

use App\Utils\Database;

try {
    // Test database connection
    $pdo = Database::getConnection();
    echo "Database connection successful\n";
    
    // Check if required tables exist
    $tables = ['posts', 'walls', 'users', 'ai_applications'];
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        $result = $stmt->fetch();
        if ($result) {
            echo "✓ Table $table exists\n";
        } else {
            echo "✗ Table $table does not exist\n";
        }
    }
    
    // Check posts table structure
    echo "\n--- Posts Table Structure ---\n";
    $stmt = $pdo->query("DESCRIBE posts");
    $columns = $stmt->fetchAll();
    foreach ($columns as $column) {
        echo "Column: {$column['Field']} - Type: {$column['Type']}\n";
    }
    
    // Check if FULLTEXT index exists on posts
    echo "\n--- Posts Indexes ---\n";
    $stmt = $pdo->query("SHOW INDEX FROM posts WHERE Index_type = 'FULLTEXT'");
    $indexes = $stmt->fetchAll();
    if (count($indexes) > 0) {
        foreach ($indexes as $index) {
            echo "FULLTEXT Index: {$index['Key_name']} on column(s): {$index['Column_name']}\n";
        }
    } else {
        echo "No FULLTEXT indexes found on posts table\n";
    }
    
    // Check walls table structure
    echo "\n--- Walls Table Structure ---\n";
    $stmt = $pdo->query("DESCRIBE walls");
    $columns = $stmt->fetchAll();
    foreach ($columns as $column) {
        echo "Column: {$column['Field']} - Type: {$column['Type']}\n";
    }
    
    // Check if FULLTEXT index exists on walls
    echo "\n--- Walls Indexes ---\n";
    $stmt = $pdo->query("SHOW INDEX FROM walls WHERE Index_type = 'FULLTEXT'");
    $indexes = $stmt->fetchAll();
    if (count($indexes) > 0) {
        foreach ($indexes as $index) {
            echo "FULLTEXT Index: {$index['Key_name']} on column(s): {$index['Column_name']}\n";
        }
    } else {
        echo "No FULLTEXT indexes found on walls table\n";
    }
    
    // Check users table structure
    echo "\n--- Users Table Structure ---\n";
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll();
    foreach ($columns as $column) {
        echo "Column: {$column['Field']} - Type: {$column['Type']}\n";
    }
    
    // Check if FULLTEXT index exists on users
    echo "\n--- Users Indexes ---\n";
    $stmt = $pdo->query("SHOW INDEX FROM users WHERE Index_type = 'FULLTEXT'");
    $indexes = $stmt->fetchAll();
    if (count($indexes) > 0) {
        foreach ($indexes as $index) {
            echo "FULLTEXT Index: {$index['Key_name']} on column(s): {$index['Column_name']}\n";
        }
    } else {
        echo "No FULLTEXT indexes found on users table\n";
    }
    
    // Check ai_applications table structure
    echo "\n--- AI Applications Table Structure ---\n";
    $stmt = $pdo->query("DESCRIBE ai_applications");
    $columns = $stmt->fetchAll();
    foreach ($columns as $column) {
        echo "Column: {$column['Field']} - Type: {$column['Type']}\n";
    }
    
    // Check if FULLTEXT index exists on ai_applications
    echo "\n--- AI Applications Indexes ---\n";
    $stmt = $pdo->query("SHOW INDEX FROM ai_applications WHERE Index_type = 'FULLTEXT'");
    $indexes = $stmt->fetchAll();
    if (count($indexes) > 0) {
        foreach ($indexes as $index) {
            echo "FULLTEXT Index: {$index['Key_name']} on column(s): {$index['Column_name']}\n";
        }
    } else {
        echo "No FULLTEXT indexes found on ai_applications table\n";
    }
    
    // Test a simple search query
    echo "\n--- Testing Simple Search ---\n";
    try {
        // Test posts search with existing index
        $stmt = $pdo->prepare("SELECT * FROM posts WHERE MATCH(content_text) AGAINST(? IN NATURAL LANGUAGE MODE) LIMIT 1");
        $stmt->execute(['Привет']);
        $result = $stmt->fetch();
        if ($result) {
            echo "✓ Posts search works with content_text\n";
        } else {
            echo "? No matching posts found (this might be expected)\n";
        }
    } catch (Exception $e) {
        echo "✗ Posts search failed: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}