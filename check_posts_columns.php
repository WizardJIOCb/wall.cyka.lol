<?php
require_once 'vendor/autoload.php';
require_once 'config/config.php';

use App\Utils\Database;

try {
    $db = Database::getConnection();
    $stmt = $db->prepare('DESCRIBE posts');
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Posts table columns:\n";
    foreach ($columns as $column) {
        echo $column['Field'] . ' (' . $column['Type'] . ")\n";
    }
    
    // Check if view_count column exists
    $hasViewCount = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'view_count') {
            $hasViewCount = true;
            break;
        }
    }
    
    echo "\nView count column exists: " . ($hasViewCount ? 'YES' : 'NO') . "\n";
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}