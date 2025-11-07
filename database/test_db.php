<?php
require_once '/var/www/html/src/Utils/Database.php';

use App\Utils\Database;

try {
    $conn = Database::getConnection();
    echo "Database connection successful\n";
    
    // Test a simple query
    $stmt = Database::query("SELECT 1 as test");
    $result = $stmt->fetch();
    echo "Simple query result: " . $result['test'] . "\n";
    
    // Check if the new columns exist in posts table
    echo "\n--- Checking posts table columns ---\n";
    $stmt = Database::query("DESCRIBE posts");
    $columns = $stmt->fetchAll();
    foreach ($columns as $column) {
        echo $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    
    // Check all indexes on posts table
    echo "\n--- Checking all posts table indexes ---\n";
    $stmt = Database::query("SHOW INDEX FROM posts");
    $indexes = $stmt->fetchAll();
    foreach ($indexes as $index) {
        echo "Index: " . $index['Key_name'] . " (" . $index['Index_type'] . ") on column " . $index['Column_name'] . "\n";
    }
    
    // Try to manually add the FULLTEXT index
    echo "\n--- Trying to add FULLTEXT index manually ---\n";
    try {
        Database::query("ALTER TABLE posts ADD FULLTEXT INDEX idx_posts_search (title, content_text)");
        echo "Successfully added idx_posts_search FULLTEXT index\n";
    } catch (Exception $e) {
        echo "Failed to add idx_posts_search FULLTEXT index: " . $e->getMessage() . "\n";
    }
    
    // Check indexes again
    echo "\n--- Checking all posts table indexes again ---\n";
    $stmt = Database::query("SHOW INDEX FROM posts");
    $indexes = $stmt->fetchAll();
    foreach ($indexes as $index) {
        echo "Index: " . $index['Key_name'] . " (" . $index['Index_type'] . ") on column " . $index['Column_name'] . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}