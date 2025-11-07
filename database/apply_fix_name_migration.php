#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Utils\Database;

// Load configuration
$config = require __DIR__ . '/../config/config.php';

try {
    // Read the migration file
    $migrationSql = file_get_contents(__DIR__ . '/migrations/018_fix_name_column_default.sql');
    
    // Execute the migration
    $pdo = Database::getPdo();
    $pdo->exec($migrationSql);
    
    echo "Migration 018_fix_name_column_default.sql applied successfully!\n";
    
} catch (Exception $e) {
    echo "Error applying migration: " . $e->getMessage() . "\n";
    exit(1);
}