<?php
// Test User::create directly without AuthService

require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "=== Testing User::create directly ===\n";

try {
    // Include required files
    require_once __DIR__ . '/src/Models/User.php';
    require_once __DIR__ . '/src/Utils/Database.php';
    
    // Test data
    $testData = [
        'username' => 'user_create_test',
        'email' => 'user_create_test@example.com',
        'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
        'display_name' => 'User Create Test',
        'name' => 'User Create Test', // Explicitly set name
        'email_verified' => 0
    ];
    
    echo "Test data:\n";
    print_r($testData);
    
    // Call User::create directly
    $result = \App\Models\User::create($testData);
    
    echo "User creation successful!\n";
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