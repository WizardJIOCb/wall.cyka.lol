<?php
require_once '/var/www/html/src/Utils/Database.php';

use App\Utils\Database;

try {
    Database::query('ALTER TABLE users ADD FULLTEXT INDEX idx_users_search (display_name, bio, username)');
    echo "Added FULLTEXT index for users successfully\n";
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}