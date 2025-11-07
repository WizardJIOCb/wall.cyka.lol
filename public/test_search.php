<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../public/api.php';

// Test the jsonResponse function directly
try {
    echo "Testing jsonResponse function...\n";
    jsonResponse(true, ['test' => 'data'], 'Test message', 200);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}