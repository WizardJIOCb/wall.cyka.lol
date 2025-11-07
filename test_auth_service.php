<?php
// Test AuthService::register directly

require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "=== Testing AuthService::register directly ===\n";

try {
    // Include required files
    require_once __DIR__ . '/src/Services/AuthService.php';
    require_once __DIR__ . '/src/Models/User.php';
    require_once __DIR__ . '/src/Utils/Database.php';
    
    // Test data
    $testData = [
        'username' => 'auth_service_test',
        'email' => 'auth_service_test@example.com',
        'password' => 'password123',
        'password_confirm' => 'password123'
    ];
    
    echo "Test data:\n";
    print_r($testData);
    
    // Call AuthService::register directly
    $result = \App\Services\AuthService::register($testData);
    
    echo "Registration successful!\n";
    print_r($result);
    
    // Clean up
    $conn = \App\Utils\Database::getConnection();
    $stmt = $conn->prepare("DELETE FROM users WHERE username = ?");
    $stmt->execute([$testData['username']]);
    echo "Cleaned up test user\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    
    // Try to clean up even if there was an error
    try {
        $conn = \App\Utils\Database::getConnection();
        $stmt = $conn->prepare("DELETE FROM users WHERE username = ?");
        $stmt->execute([$testData['username']]);
        echo "Cleaned up test user\n";
    } catch (Exception $cleanupError) {
        echo "Cleanup error: " . $cleanupError->getMessage() . "\n";
    }
}
?>