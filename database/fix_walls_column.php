<?php
require_once '/var/www/html/src/Utils/Database.php';

use App\Utils\Database;

try {
    Database::query('ALTER TABLE walls ADD COLUMN subscribers_count INT DEFAULT 0 NOT NULL AFTER posts_count');
    echo "Added subscribers_count column to walls successfully\n";
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}