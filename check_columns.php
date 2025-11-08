<?php
// Include the Database class directly
require_once 'src/Utils/Database.php';

try {
    echo "Checking if reaction columns exist in posts table...\n";
    
    // Use the Database class
    $db = new App\Utils\Database();
    
    // Check if reaction columns exist
    $stmt = $db::query("SHOW COLUMNS FROM posts LIKE '%reaction%'");
    $reactionColumns = $stmt->fetchAll();
    
    echo "Reaction-related columns in posts table: " . count($reactionColumns) . "\n";
    foreach ($reactionColumns as $col) {
        echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
    
    // Check if like columns exist
    $stmt = $db::query("SHOW COLUMNS FROM posts LIKE '%like%'");
    $likeColumns = $stmt->fetchAll();
    
    echo "\nLike-related columns in posts table: " . count($likeColumns) . "\n";
    foreach ($likeColumns as $col) {
        echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
    
    // Check if dislike columns exist
    $stmt = $db::query("SHOW COLUMNS FROM posts LIKE '%dislike%'");
    $dislikeColumns = $stmt->fetchAll();
    
    echo "\nDislike-related columns in posts table: " . count($dislikeColumns) . "\n";
    foreach ($dislikeColumns as $col) {
        echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
    
    // Also check comments table
    echo "\nChecking comments table...\n";
    $stmt = $db::query("SHOW COLUMNS FROM comments LIKE '%reaction%'");
    $reactionColumns = $stmt->fetchAll();
    
    echo "Reaction-related columns in comments table: " . count($reactionColumns) . "\n";
    foreach ($reactionColumns as $col) {
        echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}