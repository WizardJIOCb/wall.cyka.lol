<?php
/**
 * Test script to verify SearchController fix
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/config.php';

use App\Utils\Database;

try {
    // Test database connection
    $pdo = Database::getConnection();
    echo "Database connection successful\n";
    
    // Test if required tables exist
    $tables = ['posts', 'walls', 'users', 'ai_applications'];
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        $result = $stmt->fetch();
        if ($result) {
            echo "Table $table exists\n";
        } else {
            echo "Table $table does not exist\n";
        }
    }
    
    // Test if required columns exist in posts table
    $requiredPostColumns = ['post_id', 'wall_id', 'author_id', 'content_text', 'is_deleted'];
    foreach ($requiredPostColumns as $column) {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM posts LIKE ?");
        $stmt->execute([$column]);
        $result = $stmt->fetch();
        if ($result) {
            echo "Column posts.$column exists\n";
        } else {
            echo "Column posts.$column does not exist\n";
        }
    }
    
    // Test if required columns exist in walls table
    $requiredWallColumns = ['wall_id', 'user_id', 'name', 'description', 'privacy_level'];
    foreach ($requiredWallColumns as $column) {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM walls LIKE ?");
        $stmt->execute([$column]);
        $result = $stmt->fetch();
        if ($result) {
            echo "Column walls.$column exists\n";
        } else {
            echo "Column walls.$column does not exist\n";
        }
    }
    
    // Test if required columns exist in users table
    $requiredUserColumns = ['user_id', 'username', 'display_name', 'bio'];
    foreach ($requiredUserColumns as $column) {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE ?");
        $stmt->execute([$column]);
        $result = $stmt->fetch();
        if ($result) {
            echo "Column users.$column exists\n";
        } else {
            echo "Column users.$column does not exist\n";
        }
    }
    
    // Test if required columns exist in ai_applications table
    $requiredAIAppColumns = ['app_id', 'post_id', 'user_prompt', 'status'];
    foreach ($requiredAIAppColumns as $column) {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM ai_applications LIKE ?");
        $stmt->execute([$column]);
        $result = $stmt->fetch();
        if ($result) {
            echo "Column ai_applications.$column exists\n";
        } else {
            echo "Column ai_applications.$column does not exist\n";
        }
    }
    
    echo "SearchController fix verification completed\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}