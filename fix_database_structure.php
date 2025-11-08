<?php
/**
 * Database Structure Fix Script
 * 
 * This script adds missing columns to fix database structure issues
 * for the Wall Social Platform project.
 */

// Include the Database class
require_once 'src/Utils/Database.php';

use App\Utils\Database;

function addColumnIfNotExists($tableName, $columnName, $columnDefinition) {
    try {
        // Check if column exists - using direct query without parameter binding for SHOW COLUMNS
        $conn = Database::getConnection();
        $stmt = $conn->prepare("SHOW COLUMNS FROM `$tableName` LIKE '$columnName'");
        $stmt->execute();
        $column = $stmt->fetch();
        
        if (!$column) {
            echo "Adding column $columnName to $tableName...\n";
            Database::query("ALTER TABLE `$tableName` ADD COLUMN $columnName $columnDefinition");
            echo "✓ Column $columnName added successfully\n";
        } else {
            echo "✓ Column $columnName already exists in $tableName\n";
        }
    } catch (Exception $e) {
        echo "✗ Error adding column $columnName to $tableName: " . $e->getMessage() . "\n";
    }
}

function addIndexIfNotExists($tableName, $indexName, $columns) {
    try {
        // Check if index exists - using direct query without parameter binding
        $conn = Database::getConnection();
        $stmt = $conn->prepare("
            SELECT COUNT(*) as cnt 
            FROM INFORMATION_SCHEMA.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = '$tableName' 
            AND INDEX_NAME = '$indexName'
        ");
        
        $stmt->execute();
        $result = $stmt->fetch();
        
        if ($result['cnt'] == 0) {
            echo "Adding index $indexName to $tableName...\n";
            Database::query("ALTER TABLE `$tableName` ADD INDEX `$indexName` ($columns)");
            echo "✓ Index $indexName added successfully\n";
        } else {
            echo "✓ Index $indexName already exists in $tableName\n";
        }
    } catch (Exception $e) {
        echo "✗ Error adding index $indexName to $tableName: " . $e->getMessage() . "\n";
    }
}

try {
    echo "Wall Social Platform - Database Structure Fixer\n";
    echo "===============================================\n\n";
    
    // Try to verify database connection and charset
    try {
        $conn = Database::getConnection();
        if (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
            $charset = $conn->query("SELECT @@character_set_client, @@character_set_connection, @@character_set_results")->fetch();
            echo "[Database] Charset verification: " . json_encode($charset) . "\n";
        } else {
            echo "[Database] Unable to verify charset (PDO constant not defined)\n";
        }
    } catch (Exception $e) {
        echo "[Database] Connection failed: " . $e->getMessage() . "\n";
        echo "Please run this script inside the Docker container where the database is accessible.\n";
        exit(1);
    }
    
    // Check if tables exist first
    $tables = ['users', 'walls', 'posts', 'comments', 'reactions'];
    $existingTables = [];
    
    foreach ($tables as $table) {
        try {
            $stmt = $conn->prepare("SHOW TABLES LIKE '$table'");
            $stmt->execute();
            $result = $stmt->fetch();
            if ($result) {
                $existingTables[] = $table;
                echo "✓ Table $table exists\n";
            } else {
                echo "✗ Table $table does not exist\n";
            }
        } catch (Exception $e) {
            echo "✗ Error checking table $table: " . $e->getMessage() . "\n";
        }
    }
    
    // Only proceed with fixes if tables exist
    if (count($existingTables) > 0) {
        // Fix reactions table
        echo "\nFixing reactions table...\n";
        if (in_array('reactions', $existingTables)) {
            addColumnIfNotExists('reactions', 'updated_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at');
        }
        
        // Fix posts table
        echo "\nFixing posts table...\n";
        if (in_array('posts', $existingTables)) {
            addColumnIfNotExists('posts', 'reaction_count', 'INT DEFAULT 0 NOT NULL AFTER comment_count');
            addColumnIfNotExists('posts', 'like_count', 'INT DEFAULT 0 NOT NULL AFTER reaction_count');
            addColumnIfNotExists('posts', 'dislike_count', 'INT DEFAULT 0 NOT NULL AFTER like_count');
            
            // Add indexes for posts
            addIndexIfNotExists('posts', 'idx_reaction_count', 'reaction_count');
            addIndexIfNotExists('posts', 'idx_like_count', 'like_count');
            addIndexIfNotExists('posts', 'idx_dislike_count', 'dislike_count');
        }
        
        // Fix comments table
        echo "\nFixing comments table...\n";
        if (in_array('comments', $existingTables)) {
            addColumnIfNotExists('comments', 'reaction_count', 'INT DEFAULT 0 NOT NULL AFTER reply_count');
            addColumnIfNotExists('comments', 'like_count', 'INT DEFAULT 0 NOT NULL AFTER reaction_count');
            addColumnIfNotExists('comments', 'dislike_count', 'INT DEFAULT 0 NOT NULL AFTER like_count');
            
            // Add indexes for comments
            addIndexIfNotExists('comments', 'idx_comment_reaction_count', 'reaction_count');
            addIndexIfNotExists('comments', 'idx_comment_like_count', 'like_count');
            addIndexIfNotExists('comments', 'idx_comment_dislike_count', 'dislike_count');
        }
        
        echo "\nDatabase structure fix completed.\n";
        echo "Please restart your Docker containers to ensure changes take effect.\n";
    } else {
        echo "\nNo tables found. Please ensure the database is properly initialized first.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}