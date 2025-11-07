<?php
// Simple debug script to test SearchController

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting simple debug script...\n";

try {
    // Autoload classes
    require_once __DIR__ . '/../vendor/autoload.php';
    
    echo "Autoloader included successfully\n";
    
    // Define the jsonResponse function directly
    function jsonResponse(bool $success, $data = null, ?string $message = null, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        
        $response = ['success' => $success];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        if ($message !== null) {
            $response['message'] = $message;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    
    echo "jsonResponse function defined\n";
    
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