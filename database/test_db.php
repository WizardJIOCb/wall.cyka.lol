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
    
    // Test if the new columns exist
    $stmt = Database::query("SHOW COLUMNS FROM posts LIKE 'title'");
    $result = $stmt->fetch();
    if ($result) {
        echo "Posts table has title column\n";
    } else {
        echo "Posts table does not have title column\n";
    }
    
    // Test if the FULLTEXT index exists
    $stmt = Database::query("SHOW INDEX FROM posts WHERE Key_name = 'idx_posts_search'");
    $result = $stmt->fetch();
    if ($result) {
        echo "Posts table has idx_posts_search FULLTEXT index\n";
    } else {
        echo "Posts table does not have idx_posts_search FULLTEXT index\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}