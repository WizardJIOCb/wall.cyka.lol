<?php
require_once '/var/www/html/src/Utils/Database.php';

use App\Utils\Database;

try {
    // Check current columns
    $stmt = Database::query('DESCRIBE ai_applications');
    $columns = $stmt->fetchAll();
    echo "Current ai_applications columns:\n";
    foreach ($columns as $column) {
        echo $column['Field'] . ' (' . $column['Type'] . ")\n";
    }
    
    // Add missing columns
    Database::query('ALTER TABLE ai_applications ADD COLUMN title VARCHAR(255) NULL AFTER user_id');
    echo "Added title column successfully\n";
    
    Database::query('ALTER TABLE ai_applications ADD COLUMN description TEXT NULL AFTER title');
    echo "Added description column successfully\n";
    
    Database::query('ALTER TABLE ai_applications ADD COLUMN tags TEXT NULL AFTER description');
    echo "Added tags column successfully\n";
    
    Database::query('ALTER TABLE ai_applications ADD COLUMN view_count INT DEFAULT 0 NOT NULL AFTER fork_count');
    echo "Added view_count column successfully\n";
    
    // Update existing data
    Database::query("UPDATE ai_applications SET title = LEFT(COALESCE(user_prompt, 'Untitled'), 255) WHERE title IS NULL");
    echo "Updated title data successfully\n";
    
    Database::query("UPDATE ai_applications SET description = LEFT(COALESCE(user_prompt, ''), 500) WHERE description IS NULL");
    echo "Updated description data successfully\n";
    
    // Add FULLTEXT index
    Database::query("ALTER TABLE ai_applications ADD FULLTEXT INDEX idx_ai_apps_search (title, description, tags)");
    echo "Added FULLTEXT index successfully\n";
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}