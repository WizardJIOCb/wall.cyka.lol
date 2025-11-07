<?php
require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "=== Detailed Debug Test ===\n";

// Test the User::create method directly with the same data as registration
try {
    // Include the User model
    require_once __DIR__ . '/src/Models/User.php';
    require_once __DIR__ . '/src/Utils/Database.php';
    require_once __DIR__ . '/src/Services/AuthService.php';
    
    // Test data similar to what would come from registration
    $testData = [
        'username' => 'debug_test_user',
        'email' => 'debug_test@example.com',
        'password' => 'password123',
        'password_confirm' => 'password123'
    ];
    
    echo "Test 1: Calling AuthService::register directly\n";
    
    // Hash the password first (as AuthService would do)
    $testData['password_hash'] = password_hash($testData['password'], PASSWORD_DEFAULT);
    
    // Prepare user data as AuthService would
    $userData = [
        'username' => $testData['username'],
        'email' => $testData['email'],
        'password_hash' => $testData['password_hash'],
        'display_name' => $testData['username'],
        'name' => $testData['username'], // Explicitly set name
        'email_verified' => 0
    ];
    
    echo "User data being passed to User::create:\n";
    print_r($userData);
    
    // Call User::create directly
    $result = \App\Models\User::create($userData);
    echo "User::create result: SUCCESS\n";
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

echo "\nTest 2: Calling AuthService::register\n";

try {
    // Test the full registration flow
    $registerData = [
        'username' => 'debug_auth_test',
        'email' => 'debug_auth@example.com',
        'password' => 'password123',
        'password_confirm' => 'password123'
    ];
    
    echo "Registration data:\n";
    print_r($registerData);
    
    $result = \App\Services\AuthService::register($registerData);
    echo "AuthService::register result: SUCCESS\n";
    print_r($result);
    
    // Clean up
    $conn = \App\Utils\Database::getConnection();
    $stmt = $conn->prepare("DELETE FROM users WHERE username = ?");
    $stmt->execute([$registerData['username']]);
    echo "Cleaned up test user\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    
    // Try to clean up even if there was an error
    try {
        $conn = \App\Utils\Database::getConnection();
        $stmt = $conn->prepare("DELETE FROM users WHERE username = ?");
        $stmt->execute([$registerData['username']]);
        echo "Cleaned up test user\n";
    } catch (Exception $cleanupError) {
        echo "Cleanup error: " . $cleanupError->getMessage() . "\n";
    }
}
?>