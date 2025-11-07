<?php
require_once '/var/www/html/src/Utils/Database.php';

use App\Utils\Database;

try {
    echo "Testing search functionality...\n";
    
    // Test with a more general term that might have results
    $searchTerm = 'wall';
    
    // Test posts search
    echo "\n--- Testing posts search with '$searchTerm' ---\n";
    $stmt = Database::query("SELECT post_id, title FROM posts WHERE MATCH(title, content_text) AGAINST(? IN NATURAL LANGUAGE MODE) LIMIT 5", [$searchTerm]);
    $results = $stmt->fetchAll();
    echo "Found " . count($results) . " posts matching '$searchTerm'\n";
    foreach ($results as $result) {
        echo "  Post ID: " . $result['post_id'] . ", Title: " . substr($result['title'], 0, 50) . "...\n";
    }
    
    // Test walls search
    echo "\n--- Testing walls search with '$searchTerm' ---\n";
    $stmt = Database::query("SELECT wall_id, name FROM walls WHERE MATCH(name, description) AGAINST(? IN NATURAL LANGUAGE MODE) LIMIT 5", [$searchTerm]);
    $results = $stmt->fetchAll();
    echo "Found " . count($results) . " walls matching '$searchTerm'\n";
    foreach ($results as $result) {
        echo "  Wall ID: " . $result['wall_id'] . ", Name: " . substr($result['name'], 0, 50) . "...\n";
    }
    
    // Test users search
    echo "\n--- Testing users search with '$searchTerm' ---\n";
    $stmt = Database::query("SELECT user_id, display_name FROM users WHERE MATCH(display_name, bio, username) AGAINST(? IN NATURAL LANGUAGE MODE) LIMIT 5", [$searchTerm]);
    $results = $stmt->fetchAll();
    echo "Found " . count($results) . " users matching '$searchTerm'\n";
    foreach ($results as $result) {
        echo "  User ID: " . $result['user_id'] . ", Name: " . substr($result['display_name'], 0, 50) . "...\n";
    }
    
    // Test ai_applications search
    echo "\n--- Testing ai_applications search with '$searchTerm' ---\n";
    $stmt = Database::query("SELECT app_id, title FROM ai_applications WHERE MATCH(title, description, tags) AGAINST(? IN NATURAL LANGUAGE MODE) LIMIT 5", [$searchTerm]);
    $results = $stmt->fetchAll();
    echo "Found " . count($results) . " AI applications matching '$searchTerm'\n";
    foreach ($results as $result) {
        echo "  App ID: " . $result['app_id'] . ", Title: " . substr($result['title'], 0, 50) . "...\n";
    }
    
    echo "\nAll search tests completed successfully!\n";
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}