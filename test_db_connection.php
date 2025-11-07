<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Utils\Database;

try {
    // Test database connection
    $pdo = Database::getConnection();
    echo "Database connection successful!\n";
    
    // Check if the name column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'name'");
    $column = $stmt->fetch();
    
    if ($column) {
        echo "Name column exists: " . print_r($column, true) . "\n";
    } else {
        echo "Name column does not exist!\n";
    }
    
    // Check the current structure of the users table
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll();
    
    echo "Users table structure:\n";
    foreach ($columns as $column) {
        echo "  {$column['Field']}: {$column['Type']} " . 
             ($column['Null'] === 'YES' ? 'NULL' : 'NOT NULL') .
             (isset($column['Default']) ? " DEFAULT '{$column['Default']}'" : '') . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}