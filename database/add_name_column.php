<?php
require_once '/var/www/html/src/Utils/Database.php';

use App\Utils\Database;

try {
    Database::query('ALTER TABLE walls ADD COLUMN name VARCHAR(100) NULL AFTER user_id');
    echo "Added name column successfully\n";
    
    Database::query("UPDATE walls SET name = display_name WHERE name IS NULL");
    echo "Updated existing walls successfully\n";
    
    Database::query("ALTER TABLE walls MODIFY COLUMN name VARCHAR(100) NOT NULL");
    echo "Made name column NOT NULL successfully\n";
    
    Database::query("ALTER TABLE walls ADD FULLTEXT INDEX idx_walls_search (name, description)");
    echo "Added FULLTEXT index successfully\n";
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}