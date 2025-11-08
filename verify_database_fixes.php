<?php
/**
 * Script to verify and apply database fixes for view_count, comment_count, and reaction count columns
 * 
 * This script checks if the view_count, comment_count, and reaction count columns exist in the posts and comments tables,
 * and if not, applies the necessary migrations.
 */

// This script should be run from the server command line
// php verify_database_fixes.php

echo "Checking database schema for view_count, comment_count, and reaction count columns...\n";

// Include the database utility
require_once __DIR__ . '/src/Utils/Database.php';

use App\Utils\Database;

try {
    // Check if view_count column exists
    echo "\nChecking view_count column...\n";
    $checkSql = "SHOW COLUMNS FROM posts LIKE 'view_count'";
    $result = Database::fetchOne($checkSql);
    
    if ($result) {
        echo "✓ view_count column already exists in posts table\n";
    } else {
        echo "✗ view_count column does not exist in posts table\n";
        echo "Applying migration to add view_count column...\n";
        
        // Apply the migration
        $migrationSql = "ALTER TABLE posts ADD COLUMN view_count INT DEFAULT 0 NOT NULL AFTER is_deleted";
        Database::query($migrationSql, []);
        
        // Add index
        $indexSql = "ALTER TABLE posts ADD INDEX idx_view_count (view_count)";
        Database::query($indexSql, []);
        
        echo "✓ view_count column added successfully\n";
    }
    
    // Check if comment_count column exists
    echo "\nChecking comment_count column...\n";
    $checkSql = "SHOW COLUMNS FROM posts LIKE 'comment_count'";
    $result = Database::fetchOne($checkSql);
    
    if ($result) {
        echo "✓ comment_count column already exists in posts table\n";
    } else {
        echo "✗ comment_count column does not exist in posts table\n";
        echo "Applying migration to add comment_count column...\n";
        
        // Apply the migration
        $migrationSql = "ALTER TABLE posts ADD COLUMN comment_count INT DEFAULT 0 NOT NULL AFTER view_count";
        Database::query($migrationSql, []);
        
        // Add index
        $indexSql = "ALTER TABLE posts ADD INDEX idx_comment_count (comment_count)";
        Database::query($indexSql, []);
        
        echo "✓ comment_count column added successfully\n";
    }
    
    // Check if reaction count columns exist in posts table
    echo "\nChecking reaction count columns in posts table...\n";
    $columnsToCheck = ['reaction_count', 'like_count', 'dislike_count'];
    
    foreach ($columnsToCheck as $column) {
        $checkSql = "SHOW COLUMNS FROM posts LIKE '$column'";
        $result = Database::fetchOne($checkSql);
        
        if ($result) {
            echo "✓ $column column already exists in posts table\n";
        } else {
            echo "✗ $column column does not exist in posts table\n";
            echo "Applying migration to add $column column...\n";
            
            // Apply the migration
            $afterColumn = $column === 'reaction_count' ? 'comment_count' : 
                          ($column === 'like_count' ? 'reaction_count' : 'like_count');
            $migrationSql = "ALTER TABLE posts ADD COLUMN $column INT DEFAULT 0 NOT NULL AFTER $afterColumn";
            Database::query($migrationSql, []);
            
            // Add index
            $indexSql = "ALTER TABLE posts ADD INDEX idx_$column ($column)";
            Database::query($indexSql, []);
            
            echo "✓ $column column added successfully\n";
        }
    }
    
    // Check if reaction count columns exist in comments table
    echo "\nChecking reaction count columns in comments table...\n";
    
    foreach ($columnsToCheck as $column) {
        $checkSql = "SHOW COLUMNS FROM comments LIKE '$column'";
        $result = Database::fetchOne($checkSql);
        
        if ($result) {
            echo "✓ $column column already exists in comments table\n";
        } else {
            echo "✗ $column column does not exist in comments table\n";
            echo "Applying migration to add $column column...\n";
            
            // Apply the migration
            $afterColumn = $column === 'reaction_count' ? 'reply_count' : 
                          ($column === 'like_count' ? 'reaction_count' : 'like_count');
            $migrationSql = "ALTER TABLE comments ADD COLUMN $column INT DEFAULT 0 NOT NULL AFTER $afterColumn";
            Database::query($migrationSql, []);
            
            // Add index
            $indexSql = "ALTER TABLE comments ADD INDEX idx_comment_$column ($column)";
            Database::query($indexSql, []);
            
            echo "✓ $column column added successfully\n";
        }
    }
    
    // Test by selecting a post with all columns
    $testSql = "SELECT post_id, view_count, comment_count, reaction_count, like_count, dislike_count FROM posts LIMIT 1";
    $post = Database::fetchOne($testSql);
    
    if ($post) {
        echo "\n✓ Successfully retrieved post with:\n";
        echo "  - view_count: " . $post['view_count'] . "\n";
        echo "  - comment_count: " . $post['comment_count'] . "\n";
        echo "  - reaction_count: " . $post['reaction_count'] . "\n";
        echo "  - like_count: " . $post['like_count'] . "\n";
        echo "  - dislike_count: " . $post['dislike_count'] . "\n";
    }
    
    echo "\nDatabase verification complete!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Note: This script requires proper database configuration.\n";
}