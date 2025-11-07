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
    
    // Add missing columns one by one to avoid issues
    try {
        Database::query('ALTER TABLE ai_applications ADD COLUMN title VARCHAR(255) NULL AFTER post_id');
        echo "Added title column successfully\n";
    } catch (Exception $e) {
        echo "Title column may already exist: " . $e->getMessage() . "\n";
    }
    
    try {
        Database::query('ALTER TABLE ai_applications ADD COLUMN description TEXT NULL AFTER title');
        echo "Added description column successfully\n";
    } catch (Exception $e) {
        echo "Description column may already exist: " . $e->getMessage() . "\n";
    }
    
    try {
        Database::query('ALTER TABLE ai_applications ADD COLUMN tags TEXT NULL AFTER description');
        echo "Added tags column successfully\n";
    } catch (Exception $e) {
        echo "Tags column may already exist: " . $e->getMessage() . "\n";
    }
    
    try {
        Database::query('ALTER TABLE ai_applications ADD COLUMN view_count INT DEFAULT 0 NOT NULL AFTER remix_count');
        echo "Added view_count column successfully\n";
    } catch (Exception $e) {
        echo "View_count column may already exist: " . $e->getMessage() . "\n";
    }
    
    // Update existing data
    try {
        Database::query("UPDATE ai_applications SET title = LEFT(COALESCE(user_prompt, 'Untitled'), 255) WHERE title IS NULL");
        echo "Updated title data successfully\n";
    } catch (Exception $e) {
        echo "Error updating title data: " . $e->getMessage() . "\n";
    }
    
    try {
        Database::query("UPDATE ai_applications SET description = LEFT(COALESCE(user_prompt, ''), 500) WHERE description IS NULL");
        echo "Updated description data successfully\n";
    } catch (Exception $e) {
        echo "Error updating description data: " . $e->getMessage() . "\n";
    }
    
    // Add FULLTEXT index
    try {
        Database::query("ALTER TABLE ai_applications ADD FULLTEXT INDEX idx_ai_apps_search (title, description, tags)");
        echo "Added FULLTEXT index successfully\n";
    } catch (Exception $e) {
        echo "Error adding FULLTEXT index: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}