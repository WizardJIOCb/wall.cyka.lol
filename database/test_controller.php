<?php
// Test the SearchController directly
// Set up the autoloader
spl_autoload_register(function ($class) {
    // Convert namespace to file path
    $prefix = 'App\\';
    $baseDir = '/var/www/html/src/';
    
    // Does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    // Get the relative class name
    $relativeClass = substr($class, $len);
    
    // Replace namespace separators with directory separators
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    // If the file exists, require it
    if (file_exists($file)) {
        require_once $file;
    }
});

try {
    echo "Testing SearchController...\n";
    
    // Mock the $_GET parameters
    $_GET = [
        'params' => [
            'q' => 'wall',
            'type' => 'all',
            'sort' => 'relevance',
            'limit' => 5
        ]
    ];
    
    // Call the unifiedSearch method
    \App\Controllers\SearchController::unifiedSearch();
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
    echo 'Trace: ' . $e->getTraceAsString() . "\n";
}