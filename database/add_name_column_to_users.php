<?php
require_once __DIR__ . '/../src/Utils/Database.php';

use App\Utils\Database;

try {
    // Add name column to users table
    Database::query('ALTER TABLE users ADD COLUMN name VARCHAR(100) NULL AFTER display_name');
    echo "Added name column to users table successfully\n";
    
    // Populate name column with display_name values where name is NULL
    Database::query("UPDATE users SET name = display_name WHERE name IS NULL");
    echo "Populated name column with display_name values successfully\n";
    
    // Make name column NOT NULL
    Database::query("ALTER TABLE users MODIFY COLUMN name VARCHAR(100) NOT NULL");
    echo "Made name column NOT NULL successfully\n";
    
    // Add index for name column
    Database::query("ALTER TABLE users ADD INDEX idx_name (name)");
    echo "Added index for name column successfully\n";
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}