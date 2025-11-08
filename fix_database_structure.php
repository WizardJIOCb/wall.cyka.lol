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
        // Check if column exists
        $stmt = Database::query("SHOW COLUMNS FROM `$tableName` LIKE ?", [$columnName]);
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
        // Check if index exists
        $stmt = Database::query("
            SELECT COUNT(*) as cnt 
            FROM INFORMATION_SCHEMA.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ? 
            AND INDEX_NAME = ?
        ", [$tableName, $indexName]);
        
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
    
    // Fix reactions table
    echo "Fixing reactions table...\n";
    addColumnIfNotExists('reactions', 'updated_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at');
    
    // Fix posts table
    echo "\nFixing posts table...\n";
    addColumnIfNotExists('posts', 'reaction_count', 'INT DEFAULT 0 NOT NULL AFTER comment_count');
    addColumnIfNotExists('posts', 'like_count', 'INT DEFAULT 0 NOT NULL AFTER reaction_count');
    addColumnIfNotExists('posts', 'dislike_count', 'INT DEFAULT 0 NOT NULL AFTER like_count');
    
    // Add indexes for posts
    addIndexIfNotExists('posts', 'idx_reaction_count', 'reaction_count');
    addIndexIfNotExists('posts', 'idx_like_count', 'like_count');
    addIndexIfNotExists('posts', 'idx_dislike_count', 'dislike_count');
    
    // Fix comments table
    echo "\nFixing comments table...\n";
    addColumnIfNotExists('comments', 'reaction_count', 'INT DEFAULT 0 NOT NULL AFTER reply_count');
    addColumnIfNotExists('comments', 'like_count', 'INT DEFAULT 0 NOT NULL AFTER reaction_count');
    addColumnIfNotExists('comments', 'dislike_count', 'INT DEFAULT 0 NOT NULL AFTER like_count');
    
    // Add indexes for comments
    addIndexIfNotExists('comments', 'idx_comment_reaction_count', 'reaction_count');
    addIndexIfNotExists('comments', 'idx_comment_like_count', 'like_count');
    addIndexIfNotExists('comments', 'idx_comment_dislike_count', 'dislike_count');
    
    echo "\nDatabase structure fix completed.\n";
    echo "Please restart your Docker containers to ensure changes take effect.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}