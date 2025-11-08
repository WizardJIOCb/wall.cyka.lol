<?php
/**
 * Database Migration Runner
 * 
 * Executes all pending SQL migrations in order
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Database configuration (matching Database class)
$DB_HOST = getenv('DB_HOST') ?: 'mysql';
$DB_PORT = getenv('DB_PORT') ?: '3306';
$DB_NAME = getenv('DB_NAME') ?: 'wall_social_platform';
$DB_USER = getenv('DB_USER') ?: 'wall_user';
$DB_PASS = getenv('DB_PASSWORD') ?: 'wall_secure_password_123';

// Database connection
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
    
} catch (PDOException $e) {
    die("✗ Database connection failed: " . $e->getMessage() . "\n");
}

// Get all migration files
$migrationDir = __DIR__ . '/migrations';
$migrations = glob($migrationDir . '/*.sql');
sort($migrations); // Execute in order

if (empty($migrations)) {
    echo "No migrations found in {$migrationDir}\n";
    exit(0);
}

echo "Found " . count($migrations) . " migration(s):\n";

// Execute each migration
foreach ($migrations as $migrationFile) {
    $filename = basename($migrationFile);
    echo "\nExecuting: {$filename}... ";
    
    try {
        $sql = file_get_contents($migrationFile);
        
        // Check if migration uses DELIMITER (stored procedures/functions)
        if (preg_match('/DELIMITER/i', $sql)) {
            // For files with DELIMITER, we need special handling
            // Remove DELIMITER commands and convert $$ to ;
            $sql = preg_replace('/DELIMITER\s+\$\$/i', '', $sql);
            $sql = preg_replace('/DELIMITER\s+;/i', '', $sql);
            
            // Split on $$ which separates procedure definitions
            $blocks = preg_split('/\$\$/', $sql);
            
            foreach ($blocks as $block) {
                $block = trim($block);
                if (empty($block) || preg_match('/^\s*--/', $block)) {
                    continue;
                }
                
                // Execute each block
                if (preg_match('/^\s*(SHOW|DESCRIBE|SELECT)/i', $block)) {
                    $stmt = $pdo->query($block);
                    if ($stmt) {
                        $stmt->fetchAll();
                        $stmt->closeCursor();
                        unset($stmt);
                    }
                } else {
                    $pdo->exec($block);
                }
            }
        } else {
            // Original logic for simple migrations
            // Split by semicolon and execute each statement
            $statements = array_filter(
                array_map('trim', explode(';', $sql)),
                function($stmt) {
                    return !empty($stmt) && !preg_match('/^--/', $stmt);
                }
            );
            
            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    // Handle result-set queries (SHOW, DESCRIBE, SELECT) to avoid unbuffered query errors
                    if (preg_match('/^\s*(SHOW|DESCRIBE|SELECT)/i', $statement)) {
                        $stmt = $pdo->query($statement);
                        $stmt->fetchAll(); // Drain result set
                        $stmt->closeCursor(); // Explicitly close cursor
                        unset($stmt); // Free statement
                        continue;
                    }
                    $pdo->exec($statement);
                }
            }
        }
        
        echo "✓ Success\n";
        
    } catch (Exception $e) {
        echo "✗ Failed\n";
        echo "Error: " . $e->getMessage() . "\n";
        echo "Consider manually executing: {$migrationFile}\n";
    } catch (PDOException $e) {
        echo "✗ Failed\n";
        echo "Error: " . $e->getMessage() . "\n";
        echo "Consider manually executing: {$migrationFile}\n";
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Migration execution complete!\n";
echo str_repeat("=", 50) . "\n\n";

// Show table count
try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $stmt->closeCursor();
    unset($stmt);
    echo "Total tables in database: " . count($tables) . "\n";
    echo "Database name: {$DB_NAME}\n";
    
    // Show newly created tables
    $newTables = ['user_follows', 'notifications', 'user_preferences', 'conversations', 'conversation_participants', 'messages'];
    $existing = array_intersect($newTables, $tables);
    
    if (!empty($existing)) {
        echo "\nNew tables created:\n";
        foreach ($existing as $table) {
            echo "  - {$table}\n";
        }
    }
    
} catch (PDOException $e) {
    echo "Could not verify tables: " . $e->getMessage() . "\n";
}

echo "\nNext steps:\n";
echo "1. Verify tables were created successfully\n";
echo "2. Test API endpoints with frontend\n";
echo "3. Check error logs for any issues\n";
echo "4. Consider running database backups\n\n";