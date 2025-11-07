<?php
// Test script to directly call SearchController methods

require_once __DIR__ . '/../vendor/autoload.php';

// Set up test environment
$_GET['params'] = [
    'q' => 'test',
    'type' => 'all',
    'sort' => 'relevance',
    'limit' => 20
];

// Include the api.php file to get the jsonResponse function
require_once __DIR__ . '/api.php';

use App\Controllers\SearchController;

echo "Testing SearchController::unifiedSearch()...\n";

try {
    // This should output JSON directly
    SearchController::unifiedSearch();
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}