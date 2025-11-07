<?php
// Debug script to test SearchController

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/var/www/html/storage/logs/php_errors.log');

echo "Starting debug script...\n";

try {
    // Include the main API file to get the jsonResponse function
    require_once __DIR__ . '/api.php';
    
    echo "API file included successfully\n";
    
    // Include the SearchController
    require_once __DIR__ . '/../src/Controllers/SearchController.php';
    
    echo "SearchController included successfully\n";
    
    // Test the SearchController class
    if (class_exists('App\Controllers\SearchController')) {
        echo "SearchController class exists\n";
    } else {
        echo "SearchController class does not exist\n";
        exit(1);
    }
    
    // Set up test parameters
    $_GET['params'] = [
        'q' => 'test',
        'type' => 'all',
        'sort' => 'relevance',
        'limit' => 5
    ];
    
    echo "Calling SearchController::unifiedSearch()...\n";
    
    // Call the search method
    \App\Controllers\SearchController::unifiedSearch();
    
} catch (Exception $e) {
    echo "Exception caught: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "Error caught: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}