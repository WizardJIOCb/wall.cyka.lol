<?php
/**
 * Database Structure Checker Script
 * 
 * This script checks the database structure and identifies missing columns
 * for the Wall Social Platform project.
 */

// Include the Database class
require_once 'src/Utils/Database.php';

use App\Utils\Database;

function checkTableStructure($tableName) {
    echo "Checking table: $tableName\n";
    echo str_repeat("-", 50) . "\n";
    
    try {
        $stmt = Database::query("SHOW COLUMNS FROM $tableName");
        $columns = $stmt->fetchAll();
        
        echo "Columns in $tableName:\n";
        foreach ($columns as $column) {
            echo "  - {$column['Field']} ({$column['Type']})";
            if ($column['Null'] === 'NO') {
                echo " NOT NULL";
            }
            if ($column['Key'] === 'PRI') {
                echo " PRIMARY KEY";
            }
            if ($column['Extra']) {
                echo " {$column['Extra']}";
            }
            echo "\n";
        }
        echo "\n";
    } catch (Exception $e) {
        echo "Error checking $tableName: " . $e->getMessage() . "\n\n";
    }
}

function checkTableExists($tableName) {
    try {
        // Use direct query without parameter binding for SHOW TABLES LIKE
        $conn = Database::getConnection();
        $stmt = $conn->prepare("SHOW TABLES LIKE '$tableName'");
        $stmt->execute();
        return $stmt->fetch() !== false;
    } catch (Exception $e) {
        return false;
    }
}

function checkMissingColumns($tableName, $requiredColumns) {
    echo "Checking for missing columns in $tableName:\n";
    
    try {
        $stmt = Database::query("SHOW COLUMNS FROM $tableName");
        $existingColumns = $stmt->fetchAll();
        $existingColumnNames = array_column($existingColumns, 'Field');
        
        $missingColumns = array_diff($requiredColumns, $existingColumnNames);
        
        if (empty($missingColumns)) {
            echo "  ✓ All required columns present\n";
        } else {
            echo "  ✗ Missing columns:\n";
            foreach ($missingColumns as $column) {
                echo "    - $column\n";
            }
        }
        echo "\n";
    } catch (Exception $e) {
        echo "Error checking columns: " . $e->getMessage() . "\n\n";
    }
}

try {
    echo "Wall Social Platform - Database Structure Checker\n";
    echo "==================================================\n\n";
    
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
    
    // Check if required tables exist
    $requiredTables = ['users', 'walls', 'posts', 'comments', 'reactions'];
    
    $existingTables = [];
    foreach ($requiredTables as $table) {
        if (checkTableExists($table)) {
            echo "✓ Table $table exists\n";
            $existingTables[] = $table;
        } else {
            echo "✗ Table $table does not exist\n";
        }
    }
    echo "\n";
    
    // Only check table structures if tables exist
    if (count($existingTables) > 0) {
        // Check reactions table structure
        if (in_array('reactions', $existingTables)) {
            checkTableStructure('reactions');
            
            // Check for required columns in reactions table
            $requiredReactionColumns = [
                'reaction_id',
                'user_id',
                'target_type',
                'target_id',
                'reaction_type',
                'created_at',
                'updated_at'  // This is likely missing based on the error
            ];
            checkMissingColumns('reactions', $requiredReactionColumns);
        }
        
        // Check posts table structure
        if (in_array('posts', $existingTables)) {
            checkTableStructure('posts');
            
            // Check for required columns in posts table
            $requiredPostColumns = [
                'post_id',
                'wall_id',
                'author_id',
                'post_type',
                'content_text',
                'reaction_count',
                'like_count',
                'dislike_count',
                'created_at',
                'updated_at'
            ];
            checkMissingColumns('posts', $requiredPostColumns);
        }
        
        // Check comments table structure
        if (in_array('comments', $existingTables)) {
            checkTableStructure('comments');
            
            // Check for required columns in comments table
            $requiredCommentColumns = [
                'comment_id',
                'post_id',
                'author_id',
                'parent_comment_id',
                'content_text',
                'reply_count',
                'reaction_count',
                'like_count',
                'dislike_count',
                'depth_level',
                'is_hidden',
                'is_edited',
                'is_deleted',
                'created_at',
                'updated_at'
            ];
            checkMissingColumns('comments', $requiredCommentColumns);
        }
    } else {
        echo "No tables found in the database. Please ensure the database is properly initialized.\n";
    }
    
    echo "Database structure check completed.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}