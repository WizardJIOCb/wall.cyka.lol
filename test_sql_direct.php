<?php
// Test SQL query directly

require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "=== Testing SQL query directly ===\n";

try {
    // Include required files
    require_once __DIR__ . '/src/Utils/Database.php';
    
    // Test data
    $username = 'sql_direct_test';
    $email = 'sql_direct_test@example.com';
    $password_hash = password_hash('password123', PASSWORD_DEFAULT);
    $display_name = 'SQL Direct Test';
    $name = 'SQL Direct Test'; // Explicitly set name
    
    echo "Test data:\n";
    echo "Username: $username\n";
    echo "Email: $email\n";
    echo "Display name: $display_name\n";
    echo "Name: $name\n";
    
    // SQL query from User::create method
    $sql = "INSERT INTO users (
        username, email, password_hash, display_name, name,
        bricks_balance, theme_preference, email_verified, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $config = require __DIR__ . '/config/config.php';
    
    $params = [
        $username,
        $email,
        $password_hash,
        $display_name,
        $name, // This is the key field we're testing
        $config['bricks']['starting_balance'],
        'light',
        0
    ];
    
    echo "SQL: $sql\n";
    echo "Params: " . json_encode($params) . "\n";
    
    // Execute query directly
    $stmt = \App\Utils\Database::query($sql, $params);
    
    echo "Query executed successfully!\n";
    
    // Get the inserted user ID
    $userId = \App\Utils\Database::lastInsertId();
    echo "Inserted user ID: $userId\n";
    
    // Clean up
    $conn = \App\Utils\Database::getConnection();
    $stmt = $conn->prepare("DELETE FROM users WHERE username = ?");
    $stmt->execute([$username]);
    echo "Cleaned up test user\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    
    // Try to clean up even if there was an error
    try {
        $conn = \App\Utils\Database::getConnection();
        $stmt = $conn->prepare("DELETE FROM users WHERE username = ?");
        $stmt->execute([$username]);
        echo "Cleaned up test user\n";
    } catch (Exception $cleanupError) {
        echo "Cleanup error: " . $cleanupError->getMessage() . "\n";
    }
}
?>