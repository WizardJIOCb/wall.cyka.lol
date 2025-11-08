<?php
/**
 * Script to add missing reaction count columns to posts and comments tables
 */

require_once 'src/Utils/Database.php';

use App\Utils\Database;

try {
    echo "Applying reaction count columns migration...\n";
    
    // Check if columns exist in posts table
    echo "Checking posts table...\n";
    $stmt = Database::query("SHOW COLUMNS FROM posts LIKE 'reaction_count'");
    $reactionColumn = $stmt->fetch();
    
    if (!$reactionColumn) {
        echo "Adding reaction_count column to posts table...\n";
        Database::query("ALTER TABLE posts ADD COLUMN reaction_count INT DEFAULT 0 NOT NULL AFTER comment_count");
    } else {
        echo "reaction_count column already exists in posts table\n";
    }
    
    $stmt = Database::query("SHOW COLUMNS FROM posts LIKE 'like_count'");
    $likeColumn = $stmt->fetch();
    
    if (!$likeColumn) {
        echo "Adding like_count column to posts table...\n";
        Database::query("ALTER TABLE posts ADD COLUMN like_count INT DEFAULT 0 NOT NULL AFTER reaction_count");
    } else {
        echo "like_count column already exists in posts table\n";
    }
    
    $stmt = Database::query("SHOW COLUMNS FROM posts LIKE 'dislike_count'");
    $dislikeColumn = $stmt->fetch();
    
    if (!$dislikeColumn) {
        echo "Adding dislike_count column to posts table...\n";
        Database::query("ALTER TABLE posts ADD COLUMN dislike_count INT DEFAULT 0 NOT NULL AFTER like_count");
    } else {
        echo "dislike_count column already exists in posts table\n";
    }
    
    // Check if columns exist in comments table
    echo "Checking comments table...\n";
    $stmt = Database::query("SHOW COLUMNS FROM comments LIKE 'reaction_count'");
    $reactionColumn = $stmt->fetch();
    
    if (!$reactionColumn) {
        echo "Adding reaction_count column to comments table...\n";
        Database::query("ALTER TABLE comments ADD COLUMN reaction_count INT DEFAULT 0 NOT NULL AFTER reply_count");
    } else {
        echo "reaction_count column already exists in comments table\n";
    }
    
    $stmt = Database::query("SHOW COLUMNS FROM comments LIKE 'like_count'");
    $likeColumn = $stmt->fetch();
    
    if (!$likeColumn) {
        echo "Adding like_count column to comments table...\n";
        Database::query("ALTER TABLE comments ADD COLUMN like_count INT DEFAULT 0 NOT NULL AFTER reaction_count");
    } else {
        echo "like_count column already exists in comments table\n";
    }
    
    $stmt = Database::query("SHOW COLUMNS FROM comments LIKE 'dislike_count'");
    $dislikeColumn = $stmt->fetch();
    
    if (!$dislikeColumn) {
        echo "Adding dislike_count column to comments table...\n";
        Database::query("ALTER TABLE comments ADD COLUMN dislike_count INT DEFAULT 0 NOT NULL AFTER like_count");
    } else {
        echo "dislike_count column already exists in comments table\n";
    }
    
    echo "Migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error applying migration: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}