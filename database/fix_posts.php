<?php
require_once '/var/www/html/src/Utils/Database.php';

use App\Utils\Database;

try {
    Database::query('ALTER TABLE posts ADD COLUMN title VARCHAR(255) NULL AFTER post_id');
    echo "Added title column to posts successfully\n";
    
    Database::query('ALTER TABLE posts ADD FULLTEXT INDEX idx_posts_search (title, content_text)');
    echo "Added FULLTEXT index to posts successfully\n";
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}